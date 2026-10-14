export function initCandidateDemo() {
    const demo = document.querySelector('[data-candidate-demo]');
    if (!demo) return;

    const candidates = [...demo.querySelectorAll('[data-candidate]')];
    const list = demo.querySelector('[data-candidate-list]');
    const criteria = demo.querySelectorAll('[data-criterion]');
    const feedback = demo.querySelector('[data-demo-feedback]');
    const defaultFeedback = feedback.textContent;
    let selected = candidates[0];
    let prioritized = null;

    const selectCandidate = (candidate, announce = true) => {
        selected = candidate;

        candidates.forEach((row) => {
            row.classList.toggle('is-selected', row === candidate);
            row.setAttribute('aria-pressed', String(row === candidate));
        });

        ['name', 'role', 'score', 'summary'].forEach((field) => {
            demo.querySelector(`[data-candidate-${field}]`).textContent = candidate.dataset[field];
        });

        demo.querySelector('[data-candidate-status]').textContent = candidate === prioritized
            ? 'Prioritas Anda · Simulasi tinjauan HR'
            : candidate.dataset.status;
        demo.querySelector('[data-candidate-ring]').setAttribute('stroke-dashoffset', 100 - Number(candidate.dataset.score));

        JSON.parse(candidate.dataset.criteria).forEach((criterion, index) => {
            const row = criteria[index];
            row.querySelector('[data-criterion-score]').textContent = `${criterion.score}/100`;
            row.querySelector('[data-criterion-reason]').textContent = criterion.reason;
            row.querySelector('[role="progressbar"]').setAttribute('aria-valuenow', criterion.score);
            row.querySelector('.score-fill').style.setProperty('--score', criterion.score / 100);
        });

        if (announce) {
            feedback.textContent = `${candidate.dataset.name} dipilih. Kecocokan ${candidate.dataset.score}%. ${candidate.dataset.summary}`;
        }
    };

    const updateRanks = () => {
        list.querySelectorAll('[data-candidate-rank]').forEach((rank, index) => {
            rank.textContent = String(index + 1).padStart(2, '0');
        });
    };

    candidates.forEach((candidate) => {
        candidate.addEventListener('click', () => selectCandidate(candidate));
    });

    demo.querySelector('[data-demo-actions]').hidden = false;
    demo.querySelector('[data-prioritize]').addEventListener('click', () => {
        prioritized = selected;
        list.prepend(selected.closest('li'));
        updateRanks();
        selectCandidate(selected, false);
        feedback.textContent = `${selected.dataset.name} menjadi prioritas Anda. Urutan demo diubah oleh recruiter; skor AI tetap sama.`;
    });

    demo.querySelector('[data-demo-reset]').addEventListener('click', () => {
        candidates.forEach((candidate) => list.append(candidate.closest('li')));
        prioritized = null;
        updateRanks();
        selectCandidate(candidates[0], false);
        feedback.textContent = `Demo direset ke ranking awal. ${defaultFeedback}`;
    });
}

export function initScrollAnimations() {
    const revealElements = [...document.querySelectorAll('[data-reveal]')];
    if (!revealElements.length || !('IntersectionObserver' in window)) return;

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const desktop = window.matchMedia('(min-width: 1024px)');
    const hero = document.querySelector('[data-parallax-scene]');
    const artifacts = [...document.querySelectorAll('[data-parallax]')];
    const workflow = document.querySelector('[data-workflow-steps]');
    const steps = [...document.querySelectorAll('[data-workflow-step]')];
    const caption = document.querySelector('[data-workflow-caption]');
    const activeScenes = new Set();
    let frame = null;
    let activeStep = -1;

    document.querySelectorAll('[data-reveal-group]').forEach((group) => {
        group.querySelectorAll('[data-reveal]').forEach((element, index) => {
            element.style.setProperty('--reveal-delay', `${index * 100}ms`);
        });
    });

    const reveal = (element) => {
        element.classList.add('is-revealed');
        element.classList.remove('reveal-pending');
    };

    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            reveal(entry.target);
            revealObserver.unobserve(entry.target);
        });
    }, { threshold: 0, rootMargin: '0px 0px -32px 0px' });

    revealElements.forEach((element) => {
        // Visible content stays visible on load, on deep links, and without JavaScript.
        if (reducedMotion.matches || element.getBoundingClientRect().top < window.innerHeight) {
            reveal(element);
        } else {
            element.classList.add('reveal-pending');
            revealObserver.observe(element);
        }
    });

    const render = () => {
        frame = null;
        if (reducedMotion.matches) return;

        if (hero && activeScenes.has(hero) && desktop.matches) {
            const distance = Math.max(0, -hero.getBoundingClientRect().top);
            artifacts.forEach((artifact) => {
                artifact.style.setProperty('--parallax-y', `${distance * Number(artifact.dataset.parallax)}px`);
            });
        }

        if (workflow && activeScenes.has(workflow)) {
            const bounds = workflow.getBoundingClientRect();
            const readingLine = window.innerHeight * 0.6;
            const trackHeight = bounds.height - 60;
            const progress = Math.max(0, Math.min(1, (readingLine - bounds.top - 20) / trackHeight));
            workflow.style.setProperty('--workflow-progress', progress);

            const current = Math.max(0, steps.filter((step) => step.getBoundingClientRect().top + 20 <= readingLine).length - 1);
            if (current !== activeStep) {
                activeStep = current;
                steps.forEach((step, index) => step.classList.toggle('is-active', index <= current));
                if (caption) caption.textContent = steps[current].dataset.stage;
            }
        }
    };

    const schedule = () => {
        if (frame === null && !reducedMotion.matches && activeScenes.size) {
            frame = window.requestAnimationFrame(render);
        }
    };

    const sceneObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) activeScenes.add(entry.target);
            else activeScenes.delete(entry.target);
        });
        schedule();
    });

    [hero, workflow].filter(Boolean).forEach((scene) => sceneObserver.observe(scene));
    window.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('resize', schedule, { passive: true });

    const resetParallax = () => artifacts.forEach((artifact) => artifact.style.removeProperty('--parallax-y'));

    desktop.addEventListener('change', () => {
        resetParallax();
        schedule();
    });

    const applyMotionPreference = () => {
        if (!reducedMotion.matches) {
            schedule();
            return;
        }

        if (frame !== null) window.cancelAnimationFrame(frame);
        frame = null;
        revealObserver.disconnect();
        revealElements.forEach(reveal);
        resetParallax();
        workflow?.style.setProperty('--workflow-progress', 1);
        steps.forEach((step) => step.classList.add('is-active'));
        if (caption) caption.textContent = '05 langkah / Anda tetap memutuskan';
        activeStep = -1;
    };

    applyMotionPreference();
    reducedMotion.addEventListener('change', applyMotionPreference);

    // Keyboard navigation must never land on an unrevealed control.
    document.addEventListener('focusin', (event) => {
        const element = event.target.closest('[data-reveal]');
        if (element) {
            reveal(element);
            revealObserver.unobserve(element);
        }
    });
}
