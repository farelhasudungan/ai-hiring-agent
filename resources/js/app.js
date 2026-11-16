import './chat';

const workspaceMenu = document.querySelector('#workspace-menu');
const openWorkspaceMenu = document.querySelector('[data-open-workspace-menu]');

if (workspaceMenu && openWorkspaceMenu) {
    openWorkspaceMenu.addEventListener('click', () => {
        workspaceMenu.showModal();
        openWorkspaceMenu.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    });

    workspaceMenu.addEventListener('close', () => {
        openWorkspaceMenu.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    });

    workspaceMenu.querySelector('[data-close-workspace-menu]').addEventListener('click', () => {
        workspaceMenu.close();
    });

    workspaceMenu.addEventListener('click', (event) => {
        if (event.target === workspaceMenu) {
            const bounds = workspaceMenu.getBoundingClientRect();

            if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) {
                workspaceMenu.close();
            }
        }

        if (event.target.closest('a')) {
            workspaceMenu.close();
        }
    });

    window.matchMedia('(min-width: 64rem)').addEventListener('change', (event) => {
        if (event.matches && workspaceMenu.open) {
            workspaceMenu.close();
        }
    });
}

document.querySelectorAll('[data-open-dialog]').forEach((trigger) => {
    const dialog = document.getElementById(trigger.dataset.openDialog);

    if (!dialog) {
        return;
    }

    trigger.addEventListener('click', () => dialog.showModal());

    dialog.querySelectorAll('[data-close-dialog]').forEach((button) => {
        button.addEventListener('click', () => dialog.close());
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    if (dialog.hasAttribute('data-open-on-load')) {
        dialog.showModal();
    }
});

const toastStyles = {
    success: { classes: 'border-green-700 bg-green-50 text-green-900', icon: '✓', role: 'status' },
    error: { classes: 'border-red-700 bg-red-50 text-red-900', icon: '!', role: 'alert' },
};

/**
 * Shows a toast notification. Reusable from any script: window.toast.success('Saved').
 */
function showToast(type, message, duration = 5000) {
    const region = document.getElementById('toast-region');
    const style = toastStyles[type];

    if (!region || !style || !message) {
        return;
    }

    const toast = document.createElement('div');
    toast.setAttribute('role', style.role);
    toast.className = `pointer-events-auto flex w-full items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-lg transition duration-200 ${style.classes}`;

    const icon = document.createElement('span');
    icon.setAttribute('aria-hidden', 'true');
    icon.className = 'font-semibold';
    icon.textContent = style.icon;

    const text = document.createElement('p');
    text.className = 'min-w-0 flex-1 wrap-anywhere';
    text.textContent = message;

    const close = document.createElement('button');
    close.type = 'button';
    close.setAttribute('aria-label', 'Dismiss notification');
    close.className = 'shrink-0 leading-none';
    close.textContent = '×';

    const dismiss = () => {
        toast.classList.add('opacity-0', 'translate-x-4');
        setTimeout(() => toast.remove(), 200);
    };

    close.addEventListener('click', dismiss);
    toast.append(icon, text, close);
    region.append(toast);

    if (duration > 0) {
        setTimeout(dismiss, duration);
    }
}

window.toast = {
    success: (message, duration) => showToast('success', message, duration),
    error: (message, duration) => showToast('error', message, duration),
};

const toastRegion = document.getElementById('toast-region');

if (toastRegion) {
    JSON.parse(toastRegion.dataset.toasts || '[]').forEach(({ type, message }) => showToast(type, message));
}

document.querySelectorAll('[data-dropzone]').forEach((zone) => {
    const input = zone.querySelector('input[type="file"]');
    const summary = zone.querySelector('[data-dropzone-summary]');
    const highlight = ['border-ink', 'bg-zinc-50'];
    const allowed = /\.(pdf|zip)$/i;

    const updateSummary = () => {
        const names = Array.from(input.files).map((file) => file.name);
        summary.textContent = names.length === 0
            ? ''
            : names.length <= 3 ? names.join(', ') : `${names.length} files selected`;
    };

    input.addEventListener('change', updateSummary);

    ['dragenter', 'dragover'].forEach((type) => {
        zone.addEventListener(type, (event) => {
            event.preventDefault();
            zone.classList.add(...highlight);
        });
    });

    ['dragleave', 'dragend'].forEach((type) => {
        zone.addEventListener(type, () => zone.classList.remove(...highlight));
    });

    zone.addEventListener('drop', (event) => {
        event.preventDefault();
        zone.classList.remove(...highlight);

        const dropped = Array.from(event.dataTransfer.files);
        const accepted = dropped.filter((file) => allowed.test(file.name));
        const transfer = new DataTransfer();

        accepted.forEach((file) => transfer.items.add(file));
        input.files = transfer.files;
        updateSummary();

        if (accepted.length < dropped.length) {
            summary.textContent = `${summary.textContent} (${dropped.length - accepted.length} non-PDF/ZIP file(s) ignored)`.trim();
        }
    });
});

['dragover', 'drop'].forEach((type) => {
    window.addEventListener(type, (event) => {
        if (!event.target.closest('[data-dropzone]')) {
            event.preventDefault();
        }
    });
});
