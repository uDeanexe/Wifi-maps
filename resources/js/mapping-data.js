function showDataToast(message, tone = 'default') {
    let host = document.querySelector('[data-ui-toast-host]');
    if (!host) {
        host = document.createElement('div');
        host.dataset.uiToastHost = '1';
        host.className = 'ui-toast-host';
        document.body.appendChild(host);
    }

    const toast = document.createElement('div');
    toast.className = `ui-toast ui-toast-${tone}`;
    toast.setAttribute('role', 'status');
    toast.textContent = message;
    host.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('is-visible'));
    setTimeout(() => {
        toast.classList.remove('is-visible');
        setTimeout(() => toast.remove(), 220);
    }, 2800);
}

function syncSearchClear(input) {
    const button = document.querySelector(`[data-clear-search="${input.dataset.tableSearch}"]`);
    button?.classList.toggle('hidden', input.value.trim() === '');
}

function syncPagination(table, resetPage = false) {
    const controls = document.querySelector(`[data-table-pagination="#${table.id}"]`);
    if (!controls) return;
    const state = table._pagination || { page: 1, size: 10 };
    if (resetPage) state.page = 1;
    table._pagination = state;
    const rows = [...table.querySelectorAll('tbody tr:not([data-empty-row]):not(.table-no-results)')];
    const available = rows.filter((row) => !row.classList.contains('is-filtered-out'));
    const pages = Math.max(1, Math.ceil(available.length / state.size));
    state.page = Math.min(state.page, pages);
    const start = (state.page - 1) * state.size;
    const visibleRows = new Set(available.slice(start, start + state.size));
    rows.forEach((row) => row.classList.toggle('is-paginated-out', !row.classList.contains('is-filtered-out') && !visibleRows.has(row)));
    controls.innerHTML = `<div class="table-pagination-summary">${available.length ? `${start + 1}-${Math.min(start + state.size, available.length)} dari ${available.length}` : '0 data'}</div><label class="table-page-size">Tampilkan <select data-page-size aria-label="Jumlah data per halaman">${[10, 25, 50].map((size) => `<option value="${size}" ${state.size === size ? 'selected' : ''}>${size}</option>`).join('')}</select></label><div class="table-page-actions"><button type="button" data-page-prev ${state.page <= 1 ? 'disabled' : ''}>Sebelumnya</button><span>${state.page} / ${pages}</span><button type="button" data-page-next ${state.page >= pages ? 'disabled' : ''}>Berikutnya</button></div>`;
}

document.addEventListener('input', (event) => {
    const input = event.target.closest('[data-table-search]');
    if (input) {
        syncSearchClear(input);
        const table = document.querySelector(input.dataset.tableSearch);
        if (table) queueMicrotask(() => syncPagination(table, true));
    }
});

document.addEventListener('click', (event) => {
    const copy = event.target.closest('[data-copy-text]');
    if (copy) {
        const value = copy.dataset.copyText || '';
        const label = copy.dataset.copyLabel || 'Data';
        const fallbackCopy = () => {
            const area = document.createElement('textarea');
            area.value = value;
            area.style.position = 'fixed';
            area.style.opacity = '0';
            document.body.appendChild(area);
            area.select();
            document.execCommand('copy');
            area.remove();
        };
        if (navigator.clipboard?.writeText) navigator.clipboard.writeText(value).catch(fallbackCopy);
        else fallbackCopy();
        showDataToast(`${label} berhasil disalin.`, 'success');
        return;
    }

    const sorter = event.target.closest('[data-sort-table]');
    if (sorter) {
        const table = document.querySelector(sorter.dataset.sortTable);
        const body = table?.tBodies[0];
        if (!body) return;
        const column = Number(sorter.dataset.sortColumn);
        const direction = sorter.dataset.sortDirection === 'asc' ? 'desc' : 'asc';
        const numeric = sorter.dataset.sortType === 'number';
        const rows = [...body.querySelectorAll('tr:not([data-empty-row]):not(.table-no-results)')];
        rows.sort((a, b) => {
            const left = a.cells[column]?.textContent.trim() || '';
            const right = b.cells[column]?.textContent.trim() || '';
            const result = numeric
                ? (Number.parseFloat(left) || 0) - (Number.parseFloat(right) || 0)
                : left.localeCompare(right, 'id', { numeric: true, sensitivity: 'base' });
            return direction === 'asc' ? result : -result;
        });
        rows.forEach((row) => body.appendChild(row));
        table.querySelectorAll('[data-sort-table]').forEach((button) => {
            button.removeAttribute('data-sort-direction');
            button.removeAttribute('aria-sort');
            const icon = button.querySelector('span');
            if (icon) icon.textContent = '↕';
        });
        sorter.dataset.sortDirection = direction;
        sorter.setAttribute('aria-sort', direction === 'asc' ? 'ascending' : 'descending');
        const icon = sorter.querySelector('span');
        if (icon) icon.textContent = direction === 'asc' ? '↑' : '↓';
        syncPagination(table);
        showDataToast(`Data diurutkan ${direction === 'asc' ? 'menaik' : 'menurun'}.`);
        return;
    }

    const paginationButton = event.target.closest('[data-page-prev], [data-page-next]');
    if (paginationButton) {
        const controls = paginationButton.closest('[data-table-pagination]');
        const table = document.querySelector(controls?.dataset.tablePagination);
        if (!table) return;
        table._pagination.page += paginationButton.matches('[data-page-next]') ? 1 : -1;
        syncPagination(table);
        table.scrollIntoView({ behavior: 'smooth', block: 'start' });
        return;
    }

    const clear = event.target.closest('[data-clear-search]');
    if (clear) {
        const input = document.querySelector(`[data-table-search="${clear.dataset.clearSearch}"]`);
        if (input) {
            input.value = '';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.focus();
        }
        return;
    }

    const download = event.target.closest('[data-file-download]');
    if (download) {
        showDataToast(download.dataset.fileDownload || 'File sedang disiapkan.', 'info');
    }
});

document.addEventListener('submit', (event) => {
    const linkForm = event.target.closest('[data-link-form]');
    if (linkForm) {
        const source = linkForm.querySelector('[data-link-source]');
        const target = linkForm.querySelector('[data-link-target]');
        if (source?.value && source.value === target?.value) {
            event.preventDefault();
            target.setCustomValidity('Node tujuan harus berbeda dari node sumber.');
            target.reportValidity();
            showDataToast('Node sumber dan tujuan tidak boleh sama.', 'danger');
            return;
        }
    }

    const form = event.target.closest('[data-confirm-form]');
    if (!form) return;
    const message = form.dataset.confirmForm || 'Lanjutkan tindakan ini?';
    if (!window.confirm(message)) event.preventDefault();
});

function syncLinkPair(form) {
    const source = form.querySelector('[data-link-source]');
    const target = form.querySelector('[data-link-target]');
    const status = form.querySelector('[data-link-pair-status]');
    const same = source?.value && source.value === target?.value;
    target?.setCustomValidity(same ? 'Node tujuan harus berbeda dari node sumber.' : '');
    if (!status) return;
    if (same) status.textContent = 'Pilih node tujuan yang berbeda.';
    else if (source?.value && target?.value) status.textContent = `${source.selectedOptions[0].text} → ${target.selectedOptions[0].text}`;
    else status.textContent = 'Pilih node sumber dan tujuan yang berbeda.';
    status.classList.toggle('text-rose-700', Boolean(same));
}

document.addEventListener('change', (event) => {
    const pageSize = event.target.closest('[data-page-size]');
    if (pageSize) {
        const controls = pageSize.closest('[data-table-pagination]');
        const table = document.querySelector(controls?.dataset.tablePagination);
        if (!table) return;
        table._pagination.size = Number(pageSize.value);
        syncPagination(table, true);
        return;
    }
    const form = event.target.closest('[data-link-form]');
    if (form && event.target.matches('[data-link-source], [data-link-target]')) syncLinkPair(form);
});

document.addEventListener('click', (event) => {
    const swap = event.target.closest('[data-swap-link-nodes]');
    if (swap) {
        const form = swap.closest('[data-link-form]');
        const source = form?.querySelector('[data-link-source]');
        const target = form?.querySelector('[data-link-target]');
        if (!source || !target) return;
        [source.value, target.value] = [target.value, source.value];
        syncLinkPair(form);
        source.focus();
        return;
    }

});

document.addEventListener('keydown', (event) => {
    const editing = event.target instanceof HTMLInputElement || event.target instanceof HTMLTextAreaElement || event.target instanceof HTMLSelectElement || event.target?.isContentEditable;
    if (event.key === '/' && !editing) {
        const search = document.querySelector('[data-table-search], input[name="q"]');
        if (search) { event.preventDefault(); search.focus(); search.select?.(); }
    }
    if (event.key.toLowerCase() === 'n' && !editing && !event.ctrlKey && !event.metaKey && !event.altKey) {
        const create = document.querySelector('[data-primary-create]');
        if (create) { event.preventDefault(); create.click(); }
    }
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-table-search]').forEach(syncSearchClear);
    document.querySelectorAll('[data-table-pagination]').forEach((controls) => {
        const table = document.querySelector(controls.dataset.tablePagination);
        if (table) syncPagination(table, true);
    });
});

window.DataUI = { toast: showDataToast };
