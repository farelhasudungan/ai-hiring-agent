<script data-roku-theme-script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    (() => {
        if (window.RokuTheme) {
            window.RokuTheme.refresh();
            return;
        }

        const root = document.documentElement;
        const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');
        const validModes = ['light', 'dark', 'system'];
        const storageKey = 'theme';
        const panelStorageKey = 'filament:theme:mode';

        const readMode = () => {
            try {
                const mode = localStorage.getItem(storageKey);
                const legacyMode = localStorage.getItem(panelStorageKey);
                if (validModes.includes(mode)) return mode;
                if (validModes.includes(legacyMode)) return legacyMode;
            } catch {}
            return 'system';
        };

        let preference = readMode();

        const resolveTheme = () => preference === 'system' ? (systemTheme.matches ? 'dark' : 'light') : preference;
        const applyTheme = (theme) => {
            const isDark = theme === 'dark';
            root.dataset.theme = theme;
            root.classList.toggle('dark', isDark);
            root.style.colorScheme = theme;
            document.querySelector('meta[name="theme-color"]')?.setAttribute('content', isDark ? '#17191c' : '#ffffff');

            document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
                toggle.setAttribute('aria-pressed', String(isDark));
                toggle.setAttribute('aria-label', isDark ? 'Aktifkan light mode' : 'Aktifkan dark mode');
                toggle.title = toggle.getAttribute('aria-label');
            });
        };

        const setMode = (mode) => {
            if (! validModes.includes(mode)) return;
            preference = mode;

            try {
                // `theme` is canonical; mirror it for Filament's native theme switcher.
                for (const key of [storageKey, panelStorageKey]) {
                    if (localStorage.getItem(key) !== mode) localStorage.setItem(key, mode);
                }
            } catch {}

            root.style.setProperty('--default-theme-mode', mode);
            const theme = resolveTheme();
            applyTheme(theme);

            if (window.Alpine && window.Alpine.store('theme') !== undefined) {
                window.Alpine.store('theme', theme);
            }
        };

        const refresh = () => setMode(readMode());
        window.RokuTheme = { setMode, refresh };
        setMode(preference);

        document.addEventListener('alpine:initialized', () => {
            window.Alpine.store('theme', resolveTheme());
            window.Alpine.effect(() => {
                const theme = window.Alpine.store('theme');
                if (theme === 'light' || theme === 'dark') applyTheme(theme);
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            applyTheme(resolveTheme());
        });

        document.addEventListener('click', (event) => {
            if (event.target.closest?.('[data-theme-toggle]')) {
                setMode(root.dataset.theme === 'dark' ? 'light' : 'dark');
            }
        });

        window.addEventListener('theme-changed', (event) => setMode(event.detail));
        window.addEventListener('storage', (event) => {
            if (event.key === storageKey || event.key === null) refresh();
        });
        window.addEventListener('pageshow', refresh);
        document.addEventListener('livewire:navigated', refresh);

        systemTheme.addEventListener('change', () => {
            if (preference === 'system') setMode('system');
        });
    })();
</script>
