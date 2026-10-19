/**
 * Dither Image
 *
 * Adapted from React Dither component.
 * Applies Bayer 4×4 ordered dithering with optional CRT mask animation.
 * Renders to a responsive canvas inside a container element.
 */

// 4×4 Bayer matrix (ordered dithering)
const BAYER_4X4 = [0, 8, 2, 10, 12, 4, 14, 6, 3, 11, 1, 9, 15, 7, 13, 5];

// Pre-compute threshold lookup as normalized offsets in range (−0.5 .. 0.5)
const BAYER_THRESHOLDS = BAYER_4X4.map((v) => (v + 0.5) / 16 - 0.5);

/**
 * Bayer 4×4 ordered dithering, in place.
 * Monochrome 1-bit, or per-channel to N levels.
 */
function applyBayer4x4Dither(imageData, options = {}) {
    const { monochrome = false, levels = 4 } = options;
    const w = imageData.width;
    const h = imageData.height;
    const data = imageData.data;
    const L = Math.max(2, levels);
    const maxLevelIndex = L - 1;
    const invLevelsMinus1 = 1 / maxLevelIndex;

    for (let y = 0; y < h; y++) {
        for (let x = 0; x < w; x++) {
            const idx = (y * w + x) * 4;
            const bayerIndex = (y & 3) * 4 + (x & 3);
            const thresholdOffset = BAYER_THRESHOLDS[bayerIndex];

            if (monochrome) {
                const r = data[idx];
                const g = data[idx + 1];
                const b = data[idx + 2];
                const luminance = 0.299 * r + 0.587 * g + 0.114 * b;
                const normalized = luminance / 255 + thresholdOffset;
                const color = (normalized < 0.5 ? 0 : 1) * 255;
                data[idx] = color;
                data[idx + 1] = color;
                data[idx + 2] = color;
            } else {
                for (let c = 0; c < 3; c++) {
                    const val = data[idx + c];
                    const normalized = val / 255 + thresholdOffset;
                    const quantLevel = Math.min(
                        maxLevelIndex,
                        Math.max(0, Math.round(normalized * maxLevelIndex))
                    );
                    data[idx + c] = Math.round(
                        quantLevel * (255 * invLevelsMinus1)
                    );
                }
            }
        }
    }
    return imageData;
}

/**
 * RGB subpixel + scanline CRT mask with per-pixel jitter, in place.
 */
function applyCRTMask(imageData, frame = 0, options = {}) {
    const { data, width, height } = imageData;
    const strength = Math.min(1, Math.max(0, options.strength ?? 0.6));
    const jitterAmp = Math.min(1, Math.max(0, options.jitter ?? 0.15));
    const baseInactive = 1 - strength;

    for (let y = 0; y < height; y++) {
        const rowShift = y & 1;
        for (let x = 0; x < width; x++) {
            const idx = (y * width + x) * 4;
            const subPixel = (x + rowShift + frame) % 3;
            let r = data[idx];
            let g = data[idx + 1];
            let b = data[idx + 2];

            const randSeed =
                ((x * 374761393 + y * 668265263 + frame * 12345) >>> 0) &
                0xffff;
            const noise = (randSeed / 0xffff - 0.5) * jitterAmp;
            const dim = Math.min(1, Math.max(0, baseInactive + noise));

            switch (subPixel) {
                case 0:
                    g = Math.round(g * dim);
                    b = Math.round(b * dim);
                    break;
                case 1:
                    r = Math.round(r * dim);
                    b = Math.round(b * dim);
                    break;
                case 2:
                    r = Math.round(r * dim);
                    g = Math.round(g * dim);
                    break;
            }
            data[idx] = r;
            data[idx + 1] = g;
            data[idx + 2] = b;
        }
    }
    return imageData;
}

/**
 * Initialize a dithered image inside a container element.
 *
 * @param {HTMLElement} container — parent container
 * @param {Object}      options  — { src, alt, height, animate, flickerFps, ... }
 * @returns {Function}  cleanup
 */
export function initDither(container, options = {}) {
    const {
        src,
        alt = '',
        height: h,
        animate = false,
        flickerFps = 60,
        crtStrength,
        crtJitter,
        monochrome,
        levels,
    } = options;

    const canvas = document.createElement('canvas');
    canvas.className = 'w-full h-full object-cover';
    canvas.setAttribute('role', 'img');
    canvas.setAttribute('aria-label', alt);
    container.appendChild(canvas);

    let rafId;
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.src = src;

    const draw = (containerWidth) => {
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        if (!ctx) return;

        const aspectRatio = img.naturalHeight / img.naturalWidth;
        const w = containerWidth;
        const ch = h || Math.round(containerWidth * aspectRatio);
        canvas.width = w;
        canvas.height = ch;

        ctx.drawImage(img, 0, 0, w, ch);
        const imgData = ctx.getImageData(0, 0, w, ch);
        const base = applyBayer4x4Dither(imgData, { monochrome, levels });
        ctx.putImageData(base, 0, 0);

        if (animate) {
            let frame = 0;
            let last = performance.now();
            const loop = () => {
                const now = performance.now();
                const interval = 1000 / flickerFps;
                if (now - last >= interval) {
                    last = now - ((now - last) % interval);
                    const copy = new ImageData(
                        new Uint8ClampedArray(base.data),
                        base.width,
                        base.height
                    );
                    applyCRTMask(copy, frame++, {
                        strength: crtStrength,
                        jitter: crtJitter,
                    });
                    ctx.putImageData(copy, 0, 0);
                }
                rafId = requestAnimationFrame(loop);
            };
            rafId = requestAnimationFrame(loop);
        }
    };

    img.onload = () => {
        draw(container.offsetWidth);
        const ro = new ResizeObserver((entries) => {
            for (const entry of entries) {
                const newWidth = entry.contentRect.width;
                if (newWidth > 0) {
                    if (rafId !== undefined) {
                        cancelAnimationFrame(rafId);
                        rafId = undefined;
                    }
                    draw(newWidth);
                }
            }
        });
        ro.observe(container);
    };

    return () => {
        if (rafId !== undefined) cancelAnimationFrame(rafId);
    };
}
