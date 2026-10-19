import { initCandidateDemo, initScrollAnimations } from './landing';

const menuToggle = document.querySelector('[data-menu-toggle]');
const navigation = document.getElementById('main-navigation');

if (menuToggle && navigation) {
    const setMenuOpen = (open) => {
        menuToggle.setAttribute('aria-expanded', String(open));
        menuToggle.setAttribute('aria-label', open ? 'Tutup menu navigasi' : 'Buka menu navigasi');
        navigation.classList.toggle('hidden', !open);
        navigation.classList.toggle('flex', open);
    };

    menuToggle.addEventListener('click', () => {
        setMenuOpen(menuToggle.getAttribute('aria-expanded') !== 'true');
    });

    navigation.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setMenuOpen(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && menuToggle.getAttribute('aria-expanded') === 'true') {
            setMenuOpen(false);
            menuToggle.focus();
        }
    });

    window.matchMedia('(min-width: 768px)').addEventListener('change', () => setMenuOpen(false));
}

initCandidateDemo();
initScrollAnimations();
