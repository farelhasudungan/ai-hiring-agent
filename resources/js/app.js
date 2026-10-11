const themeToggle = document.querySelector('[data-theme-toggle]');
const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');
let preferredTheme;

try { preferredTheme = localStorage.getItem('theme'); } catch {}

const setTheme = (theme) => {
    document.documentElement.dataset.theme = theme;
    if (themeToggle) {
        themeToggle.setAttribute('aria-pressed', String(theme === 'dark'));
        themeToggle.setAttribute('aria-label', theme === 'dark' ? 'Aktifkan light mode' : 'Aktifkan dark mode');
        themeToggle.title = themeToggle.getAttribute('aria-label');
    }
};

setTheme(document.documentElement.dataset.theme || (systemTheme.matches ? 'dark' : 'light'));

themeToggle?.addEventListener('click', () => {
    preferredTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    setTheme(preferredTheme);
    try { localStorage.setItem('theme', preferredTheme); } catch {}
});

systemTheme.addEventListener('change', (event) => {
    if (preferredTheme !== 'dark' && preferredTheme !== 'light') {
        setTheme(event.matches ? 'dark' : 'light');
    }
});

const menuToggle = document.querySelector('[data-menu-toggle]');
const candidateDemo = document.querySelector('[data-candidate-demo]');

if (candidateDemo) {
    const candidates = candidateDemo.querySelectorAll('[data-candidate]');
    candidates.forEach((candidate) => {
        candidate.addEventListener('click', () => {
            candidates.forEach((row) => {
                row.classList.toggle('is-selected', row === candidate);
                row.setAttribute('aria-pressed', String(row === candidate));
            });
            candidateDemo.querySelector('[data-candidate-name]').textContent = candidate.dataset.name;
            candidateDemo.querySelector('[data-candidate-summary]').textContent = candidate.dataset.summary;
            candidateDemo.querySelector('[data-candidate-status]').textContent = candidate.dataset.status;
        });
    });
}

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

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && menuToggle.getAttribute('aria-expanded') === 'true') {
            setMenuOpen(false);
            menuToggle.focus();
        }
    });
}
