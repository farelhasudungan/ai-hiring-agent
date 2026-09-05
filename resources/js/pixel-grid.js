/**
 * Pixel Grid
 *
 * Adapted from React PixelGrid component.
 * Animates a grid of pixels that flicker with random opacities,
 * optionally confined to a breathing sine-wave shape.
 * DPR-aware and crisp; honors prefers-reduced-motion.
 */

/**
 * Initialize a pixel grid animation on the given canvas element.
 *
 * @param {HTMLCanvasElement} canvas  — target canvas
 * @param {Object}           options — configuration
 * @returns {Function}       cleanup — call to stop animation
 */
export function initPixelGrid(canvas, options = {}) {
    const {
        size = 360,
        pixelSize = 4,
        gap = 4,
        color = 'hsl(0 0% 90%)',
        density = 0.2,
        shape = 'circle',
    } = options;

    const ctx = canvas.getContext('2d');
    if (!ctx) return () => {};

    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    canvas.width = size * dpr;
    canvas.height = size * dpr;
    canvas.style.width = size + 'px';
    canvas.style.height = size + 'px';
    canvas.style.maxWidth = '100%';
    ctx.scale(dpr, dpr);

    const step = pixelSize + gap;
    const grid = Math.floor(size / step);
    const count = grid * grid;
    const op = new Float32Array(count);
    const center = size / 2;
    const minR = size * 0.2;
    const maxR = size * 0.5;
    const amp = (maxR - minR) / 2;
    const baseR = minR + amp;

    /** Render current opacity buffer to canvas. */
    const draw = () => {
        ctx.clearRect(0, 0, size, size);
        ctx.fillStyle = color;
        for (let y = 0; y < grid; y++) {
            for (let x = 0; x < grid; x++) {
                const a = op[y * grid + x];
                if (a <= 0) continue;
                ctx.globalAlpha = a;
                ctx.fillRect(x * step, y * step, pixelSize, pixelSize);
            }
        }
        ctx.globalAlpha = 1;
    };

    const band = size * 0.06;

    /** Check if a pixel coordinate is inside the active shape. */
    const inside = (dx, dy, r) => {
        switch (shape) {
            case 'full':
                return true;
            case 'square':
                return Math.max(Math.abs(dx), Math.abs(dy)) <= r;
            case 'diamond':
                return Math.abs(dx) + Math.abs(dy) <= r;
            case 'ring':
                return Math.abs(Math.hypot(dx, dy) - r) <= band;
            default: // circle
                return dx * dx + dy * dy <= r * r;
        }
    };

    /** Randomly update a fraction of pixels based on current radius. */
    const refresh = (radius) => {
        const updates = Math.floor(count * density);
        for (let i = 0; i < updates; i++) {
            const x = Math.floor(Math.random() * grid);
            const y = Math.floor(Math.random() * grid);
            const idx = y * grid + x;
            const dx = x * step + pixelSize / 2 - center;
            const dy = y * step + pixelSize / 2 - center;
            op[idx] = inside(dx, dy, radius)
                ? Math.random() * 0.6 + 0.2
                : 0;
        }
    };

    // Honor prefers-reduced-motion
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduced) {
        refresh(baseR);
        draw();
        return () => {};
    }

    let raf = 0;
    let frame = 0;
    const animate = () => {
        frame++;
        const radius = baseR + amp * Math.sin(frame * 0.01);
        if (frame % 5 === 0) refresh(radius);
        draw();
        raf = requestAnimationFrame(animate);
    };
    animate();

    return () => cancelAnimationFrame(raf);
}
