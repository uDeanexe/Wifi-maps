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
