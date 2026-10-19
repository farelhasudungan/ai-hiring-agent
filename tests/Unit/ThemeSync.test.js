import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const template = readFileSync(new URL('../../resources/views/components/theme-script.blade.php', import.meta.url), 'utf8');
const source = template.match(/<script[^>]*>([\s\S]*)<\/script>/)[1];
const filamentSource = readFileSync(new URL('../../vendor/filament/filament/resources/js/dark-mode.js', import.meta.url), 'utf8');

function browser(saved = {}) {
    const storage = new Map(Object.entries(saved));
    const pages = [];
    const pending = [];

    function open({ filament = false, dark = false, blockedStorage = false } = {}) {
        const documentEvents = new Map();
        const windowEvents = new Map();
        const mediaEvents = [];
        const classes = new Set();
        const attributes = new Map();
        const styles = new Map();
        const effects = [];
        let alpineTheme;

        const listen = (events, name, handler) => events.set(name, [...(events.get(name) ?? []), handler]);
        const emit = (events, name, event = {}) => (events.get(name) ?? []).forEach((handler) => handler(event));
        const root = {
            dataset: {},
            classList: {
                add: (name) => classes.add(name),
                remove: (name) => classes.delete(name),
                toggle: (name, enabled) => enabled ? classes.add(name) : classes.delete(name),
            },
            style: { setProperty: (name, value) => styles.set(name, value) },
        };
        const toggle = {
            setAttribute: (name, value) => attributes.set(name, value),
            getAttribute: (name) => attributes.get(name),
        };
        const media = {
            matches: dark,
            addEventListener: (name, handler) => mediaEvents.push(handler),
        };
        const localStorage = {
            getItem(key) {
                if (blockedStorage) throw new Error('Storage unavailable');
                return storage.get(key) ?? null;
            },
            setItem(key, value) {
                if (blockedStorage) throw new Error('Storage unavailable');
                if (storage.get(key) === value) return;
                storage.set(key, value);
                for (const page of pages) {
                    if (page.root !== root) pending.push(() => page.emitWindow('storage', { key, newValue: value }));
                }
            },
        };
        const context = vm.createContext({
            localStorage,
            getComputedStyle: () => ({ getPropertyValue: (name) => styles.get(name) ?? '' }),
            document: {
                documentElement: root,
                querySelector: () => ({ setAttribute() {} }),
                querySelectorAll: () => [toggle],
                addEventListener: (name, handler) => listen(documentEvents, name, handler),
            },
            window: {
                matchMedia: () => media,
                addEventListener: (name, handler) => listen(windowEvents, name, handler),
            },
        });

        vm.runInContext(source, context);

        if (filament) {
            context.window.Alpine = {
                store(name, value) {
                    if (value !== undefined && alpineTheme !== value) {
                        alpineTheme = value;
                        effects.forEach((effect) => effect());
                    }
                    return alpineTheme;
                },
                effect(handler) {
                    effects.push(handler);
                    handler();
                },
            };
            vm.runInContext(filamentSource, context);
            emit(documentEvents, 'alpine:init');
            emit(documentEvents, 'alpine:initialized');
        }

        emit(documentEvents, 'DOMContentLoaded');

        const page = {
            root,
            attributes,
            classes,
            get alpineTheme() { return alpineTheme; },
            emitWindow: (name, event) => emit(windowEvents, name, event),
            click: () => emit(documentEvents, 'click', { target: { closest: () => toggle } }),
            selectPanelMode: (mode) => emit(windowEvents, 'theme-changed', { detail: mode }),
            navigate: () => emit(documentEvents, 'livewire:navigated'),
            rerun: () => vm.runInContext(source, context),
            setSystemDark(value) {
                media.matches = value;
                mediaEvents.forEach((handler) => handler({ matches: value }));
            },
        };
        pages.push(page);
        return page;
    }

    return {
        storage,
        open,
        flush() {
            let count = 0;
            while (pending.length) {
                assert.ok(count++ < 100, 'Theme synchronization must not loop between tabs');
                pending.shift()();
            }
        },
    };
}

test('home, authentication and native Filament theme changes synchronize both ways', () => {
    const session = browser();
    const home = session.open();
    const auth = session.open({ filament: true });
    const dashboard = session.open({ filament: true });

    home.click();
    session.flush();

    for (const page of [home, auth, dashboard]) {
        assert.equal(page.root.dataset.theme, 'dark');
        assert.ok(page.classes.has('dark'));
    }
    assert.equal(auth.alpineTheme, 'dark');
    assert.equal(dashboard.alpineTheme, 'dark');

    dashboard.selectPanelMode('light');
    session.flush();

    for (const page of [home, auth, dashboard]) assert.equal(page.root.dataset.theme, 'light');
    assert.equal(session.storage.get('theme'), 'light');
    assert.equal(session.storage.get('filament:theme:mode'), 'light');

    auth.click();
    session.flush();
    assert.equal(home.root.dataset.theme, 'dark');
    assert.equal(dashboard.alpineTheme, 'dark');
});

test('legacy Filament preferences are adopted when the shared preference is absent', () => {
    const session = browser({ 'filament:theme:mode': 'dark' });
    const home = session.open();

    assert.equal(home.root.dataset.theme, 'dark');
    assert.equal(session.storage.get('theme'), 'dark');
});

test('the shared preference wins when old stored values conflict', () => {
    const session = browser({ theme: 'light', 'filament:theme:mode': 'dark' });
    const dashboard = session.open({ filament: true, dark: true });

    assert.equal(dashboard.root.dataset.theme, 'light');
    assert.equal(dashboard.alpineTheme, 'light');
    assert.equal(session.storage.get('filament:theme:mode'), 'light');
});

test('System mode remains System when the OS changes its resolved theme', () => {
    const session = browser();
    const home = session.open({ dark: true });
    const dashboard = session.open({ filament: true, dark: true });

    assert.equal(home.root.dataset.theme, 'dark');
    assert.equal(dashboard.root.dataset.theme, 'dark');

    home.setSystemDark(false);
    dashboard.setSystemDark(false);

    assert.equal(home.root.dataset.theme, 'light');
    assert.equal(dashboard.alpineTheme, 'light');
    assert.equal(session.storage.get('theme'), 'system');
    assert.equal(session.storage.get('filament:theme:mode'), 'system');
});

test('selecting System in Filament propagates without storing the resolved light or dark value', () => {
    const session = browser({ theme: 'light' });
    const home = session.open({ dark: true });
    const dashboard = session.open({ filament: true, dark: true });

    dashboard.selectPanelMode('system');
    session.flush();

    assert.equal(home.root.dataset.theme, 'dark');
    assert.equal(dashboard.alpineTheme, 'dark');
    assert.equal(session.storage.get('theme'), 'system');
});

test('explicit choices ignore subsequent OS theme changes and update accessible toggle labels', () => {
    const page = browser().open({ filament: true });

    page.click();
    page.setSystemDark(false);

    assert.equal(page.root.dataset.theme, 'dark');
    assert.equal(page.attributes.get('aria-pressed'), 'true');
    assert.equal(page.attributes.get('aria-label'), 'Aktifkan light mode');

    page.click();
    assert.equal(page.root.dataset.theme, 'light');
    assert.equal(page.attributes.get('aria-pressed'), 'false');
});

test('stale queued storage events do not restore an older preference', () => {
    const session = browser();
    const home = session.open();
    const dashboard = session.open({ filament: true });

    home.click();
    home.click();
    session.flush();

    assert.equal(home.root.dataset.theme, 'light');
    assert.equal(dashboard.root.dataset.theme, 'light');
    assert.equal(session.storage.get('theme'), 'light');
});

test('back-forward cache and Livewire navigation refresh the current preference', () => {
    const session = browser();
    const page = session.open({ filament: true });

    session.storage.set('theme', 'dark');
    page.emitWindow('pageshow');
    assert.equal(page.root.dataset.theme, 'dark');

    session.storage.set('theme', 'light');
    page.navigate();
    assert.equal(page.alpineTheme, 'light');
});

test('executing the shared bootstrap twice does not duplicate toggle listeners', () => {
    const page = browser().open({ filament: true });

    page.rerun();
    page.click();

    assert.equal(page.root.dataset.theme, 'dark');
});

test('unavailable browser storage does not prevent toggling on the public site', () => {
    const page = browser().open({ blockedStorage: true });

    page.click();

    assert.equal(page.root.dataset.theme, 'dark');
});
