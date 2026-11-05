import './form-builder';

document.querySelectorAll('[data-copy-link]').forEach((button) => {
    button.addEventListener('click', async () => {
        const target = document.getElementById(button.dataset.copyTarget);

        if (!target) {
            return;
        }

        const value = 'value' in target ? target.value : target.textContent;

        try {
            await navigator.clipboard.writeText(value);
        } catch {
            target.select?.();
            document.execCommand('copy');
        }

        const originalLabel = button.textContent;
        button.textContent = 'Copied';
        window.setTimeout(() => {
            button.textContent = originalLabel;
        }, 2000);
    });
});

const publicApplicationForm = document.querySelector('[data-public-application]');

if (publicApplicationForm) {
    const firstError = publicApplicationForm.querySelector('[data-field-error]');

    if (firstError) {
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstError.querySelector('input, select, textarea, button')?.focus({ preventScroll: true });
    }
}

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
