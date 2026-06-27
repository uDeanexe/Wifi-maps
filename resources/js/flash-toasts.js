function toastHost() {
    let host = document.querySelector('[data-toast-host]');
    if (host) return host;

    host = document.createElement('div');
    host.className = 'ui-toast-host';
    host.dataset.toastHost = '1';
    document.body.appendChild(host);

    return host;
}

function normalizeType(type) {
    if (type === 'error') return 'danger';
    if (type === 'warning') return 'info';
    return type || 'info';
}

export function showToast(message, type = 'info', timeout = 4500) {
    const text = String(message || '').trim();
    if (!text) return null;

    const toast = document.createElement('div');
    const normalizedType = normalizeType(type);
    toast.className = `ui-toast ui-toast-${normalizedType}`;
    toast.setAttribute('role', normalizedType === 'danger' ? 'alert' : 'status');
    toast.setAttribute('aria-live', normalizedType === 'danger' ? 'assertive' : 'polite');
    toast.textContent = text;

    toastHost().appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('is-visible'));

    const dismiss = () => {
        toast.classList.remove('is-visible');
        setTimeout(() => toast.remove(), 240);
    };

    toast.addEventListener('click', dismiss);
    setTimeout(dismiss, Number(timeout) || 4500);

    return toast;
}

function initFlashToasts() {
    document.querySelectorAll('[data-flash-toast]').forEach((element) => {
        const message = element.dataset.toastMessage || element.textContent || '';
        const type = element.dataset.toastType || 'info';
        const timeout = element.dataset.toastTimeout || 4500;

        showToast(message, type, timeout);
        element.remove();
    });
}

window.appShowToast = showToast;
document.addEventListener('app:toast', (event) => {
    showToast(event.detail?.message, event.detail?.type || 'info', event.detail?.timeout || 4500);
});

document.addEventListener('DOMContentLoaded', initFlashToasts);
