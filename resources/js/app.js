import './form-builder';

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
