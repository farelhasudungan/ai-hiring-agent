/**
 * App - Main JavaScript entry point
 *
 * Auto-initializes all interactive components on DOMContentLoaded.
 * Components are discovered via data-* attributes on DOM elements.
 */

import { initPixelGrid } from './pixel-grid';
import { initDither } from './dither';

document.addEventListener('DOMContentLoaded', () => {
    // Theme Switcher Helper
    const applyTheme = (dark) => {
        if (dark) {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        }

        document.querySelectorAll('.neu-toggle').forEach((toggle) => {
            const opts = toggle.querySelectorAll('.neu-toggle__opt');
            const activeIdx = dark ? 1 : 0;
            toggle.dataset.index = activeIdx;
            opts.forEach((o, i) => {
                o.dataset.active = i === activeIdx ? 'true' : 'false';
            });
        });
    };

    // Initialize state from localStorage or document class
    const savedTheme = localStorage.getItem('theme');
    const isDark = savedTheme ? savedTheme === 'dark' : document.documentElement.classList.contains('dark');
    applyTheme(isDark);

    // Attach click listeners to all toggle options
    document.querySelectorAll('.neu-toggle').forEach((toggle) => {
        const opts = toggle.querySelectorAll('.neu-toggle__opt');
        opts.forEach((opt, idx) => {
            opt.addEventListener('click', (e) => {
                e.preventDefault();
                applyTheme(idx === 1);
            });
        });
    });

    // Pixel Grid initialization
    document.querySelectorAll('[data-pixel-grid]').forEach((canvas) => {
        const opts = {
            size: parseInt(canvas.dataset.size || '360'),
            pixelSize: parseInt(canvas.dataset.pixelSize || '4'),
            gap: parseInt(canvas.dataset.gap || '4'),
            color: canvas.dataset.color || 'hsl(217 50% 60%)',
            density: parseFloat(canvas.dataset.density || '0.2'),
            shape: canvas.dataset.shape || 'circle',
        };
        initPixelGrid(canvas, opts);
    });

    // Dither Image initialization
    document.querySelectorAll('[data-dither]').forEach((container) => {
        const opts = {
            src: container.dataset.src,
            alt: container.dataset.alt || '',
            animate: container.dataset.animate !== undefined,
            monochrome: container.dataset.monochrome !== undefined,
            levels: parseInt(container.dataset.levels || '4'),
        };
        if (opts.src) initDither(container, opts);
    });

    // Skeleton Reveal auto-play
    document.querySelectorAll('.reveal').forEach((reveal) => {
        reveal.classList.add('reveal--loading');
        setTimeout(() => {
            reveal.classList.remove('reveal--loading');
            reveal.classList.add('reveal--revealing');
        }, 800);
        setTimeout(() => {
            reveal.classList.remove('reveal--revealing');
            reveal.classList.add('reveal--done');
        }, 1800);
    });

    // Scroll-triggered fade-in
    const fadeEls = document.querySelectorAll('.fade-in-up');
    if (fadeEls.length > 0) {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.1 }
        );
        fadeEls.forEach((el) => observer.observe(el));
    }
});
