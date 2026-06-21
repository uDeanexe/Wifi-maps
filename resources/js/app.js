import './bootstrap';

const sidebar = document.querySelector('[data-sidebar]');
const sidebarBackdrop = document.querySelector('[data-sidebar-backdrop]');
const sidebarToggle = document.querySelector('[data-sidebar-toggle]');

function notifyLayoutChanged() {
    window.dispatchEvent(new Event('layout:changed'));
    // Sidebar open/close uses transitions; invalidate a couple times to be safe.
    setTimeout(() => window.dispatchEvent(new Event('layout:changed')), 120);
    setTimeout(() => window.dispatchEvent(new Event('layout:changed')), 320);
}

function toggleSidebar(force) {
    if (!sidebar) return;

    const shouldOpen = typeof force === 'boolean' ? force : !sidebar.classList.contains('active');
    sidebar.classList.toggle('active', shouldOpen);
    sidebarBackdrop?.classList.toggle('active', shouldOpen);
    sidebarToggle?.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
    document.body.classList.toggle('sidebar-open', shouldOpen);
    notifyLayoutChanged();
}

window.toggleSidebar = toggleSidebar;

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-sidebar-toggle]')) {
        toggleSidebar();
        return;
    }

    if (event.target.closest('[data-sidebar-close]') || event.target.closest('[data-sidebar-backdrop]')) {
        toggleSidebar(false);
        return;
    }

    if (event.target.closest('[data-sidebar] a') && window.matchMedia('(max-width: 1023px)').matches) {
        toggleSidebar(false);
    }

    const opener = event.target.closest('[data-modal-open]');
    if (opener) {
        const modal = document.querySelector(opener.dataset.modalOpen);
        if (modal) {
            modal.showModal();
            document.body.classList.add('overflow-hidden');
            notifyLayoutChanged();
        }
        return;
    }

    const closer = event.target.closest('[data-modal-close]');
    if (closer) {
        closer.closest('dialog')?.close();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        toggleSidebar(false);
    }
});

document.addEventListener('input', (event) => {
    const reviewInput = event.target.closest('[data-review-input]');
    if (reviewInput) {
        document.querySelectorAll(`[data-review-output="${reviewInput.dataset.reviewInput}"]`).forEach((output) => {
            output.textContent = reviewInput.value.trim() || '-';
        });
    }

    const input = event.target.closest('[data-table-search]');
    if (!input) return;

    const table = document.querySelector(input.dataset.tableSearch);
    if (!table) return;

    const query = input.value.trim().toLowerCase();
    table.querySelectorAll('tbody tr').forEach((row) => {
        if (row.dataset.emptyRow === '1') return;
        row.hidden = query !== '' && !row.textContent.toLowerCase().includes(query);
    });
});

document.addEventListener('click', (event) => {
    if (event.target instanceof HTMLDialogElement) {
        event.target.close();
    }
});

document.addEventListener('close', (event) => {
    if (event.target instanceof HTMLDialogElement) {
        document.body.classList.toggle('overflow-hidden', document.querySelectorAll('dialog[open]').length > 0);
        notifyLayoutChanged();
    }
}, true);

function initDropzones(root = document) {
    root.querySelectorAll('[data-dropzone]').forEach((zone) => {
        if (zone.dataset.dropzoneInit === '1') return;
        zone.dataset.dropzoneInit = '1';

        const input = zone.querySelector('input[type="file"][data-dropzone-input]');
        const preview = zone.querySelector('[data-dropzone-preview]');
        const meta = zone.querySelector('[data-dropzone-meta]');
        const clearBtn = zone.querySelector('[data-dropzone-clear]');
        const pickBtn = zone.querySelector('[data-dropzone-pick]');

        if (!input) return;

        const initialSrc = (zone.dataset.dropzoneInitialSrc || '').trim() || null;
        const initialLabel = (zone.dataset.dropzoneInitialLabel || '').trim() || 'Foto saat ini.';
        let dragDepth = 0;
        let objectUrl = null;

        const setPreviewFromFile = (file) => {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }

            if (!file) {
                if (initialSrc && preview) {
                    preview.src = initialSrc;
                    preview.classList.remove('hidden');
                    if (meta) meta.textContent = initialLabel;
                } else {
                    preview?.classList.add('hidden');
                    if (meta) meta.textContent = 'Belum ada file dipilih.';
                }
                clearBtn?.classList.add('hidden');
                return;
            }

            if (meta) meta.textContent = `${file.name} • ${(file.size / 1024 / 1024).toFixed(2)} MB`;
            if (preview && file.type?.startsWith('image/')) {
                objectUrl = URL.createObjectURL(file);
                preview.src = objectUrl;
                preview.classList.remove('hidden');
            }
            clearBtn?.classList.remove('hidden');
        };

        const setFiles = (files) => {
            const file = files && files[0] ? files[0] : null;
            if (!file) return;
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        };

        input.addEventListener('change', () => setPreviewFromFile(input.files?.[0] ?? null));
        clearBtn?.addEventListener('click', () => {
            input.value = '';
            setPreviewFromFile(null);
        });
        pickBtn?.addEventListener('click', () => input.click());
        zone.addEventListener('click', (event) => {
            if (event.target.closest('[data-dropzone-pick]') || event.target.closest('[data-dropzone-clear]')) return;
            if (event.target instanceof HTMLInputElement || event.target instanceof HTMLButtonElement) return;
            input.click();
        });

        const stop = (event) => {
            event.preventDefault();
            event.stopPropagation();
        };

        ['dragenter', 'dragover'].forEach((type) => {
            zone.addEventListener(type, (event) => {
                stop(event);
                if (type === 'dragenter') dragDepth += 1;
                zone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach((type) => {
            zone.addEventListener(type, (event) => {
                stop(event);
                if (type === 'dragleave') {
                    dragDepth = Math.max(0, dragDepth - 1);
                    if (dragDepth !== 0) return;
                }
                zone.classList.remove('is-dragover');
            });
        });

        zone.addEventListener('drop', (event) => {
            dragDepth = 0;
            const files = event.dataTransfer?.files;
            if (!files?.length) return;
            setFiles(files);
        });

        // initial state (in case browser restores form state)
        setPreviewFromFile(input.files?.[0] ?? null);
    });
}

document.addEventListener('DOMContentLoaded', () => initDropzones());

function initReportCenter() {
    const form = document.querySelector('[data-report-filter]');
    if (!form) return;

    const reset = document.querySelector('[data-report-reset]');
    const status = document.querySelector('[data-report-filter-status]');
    const progress = document.querySelector('[data-report-progress]');

    const sync = () => {
        const params = new URLSearchParams(new FormData(form));
        [...params.keys()].forEach((key) => { if (!params.get(key)) params.delete(key); });
        const active = [...params.keys()].length;
        reset?.classList.toggle('hidden', active === 0);
        if (status) status.textContent = active ? `${active} filter aktif. Report hanya memuat data yang sesuai.` : 'Semua data akan disertakan.';

        document.querySelectorAll('[data-report-download]').forEach((link) => {
            const url = new URL(link.dataset.reportBaseUrl, window.location.origin);
            if (link.dataset.reportUseFilter === '1') params.forEach((value, key) => url.searchParams.set(key, value));
            link.href = url.toString();
        });
    };

    form.addEventListener('input', sync);
    form.addEventListener('change', sync);
    reset?.addEventListener('click', () => { form.reset(); sync(); form.querySelector('input, select')?.focus(); });
    document.querySelectorAll('[data-report-download]').forEach((link) => link.addEventListener('click', () => {
        if (progress) progress.hidden = false;
        link.setAttribute('aria-busy', 'true');
        setTimeout(() => { if (progress) progress.hidden = true; link.removeAttribute('aria-busy'); }, 4500);
    }));
    sync();
}

document.addEventListener('DOMContentLoaded', initReportCenter);
