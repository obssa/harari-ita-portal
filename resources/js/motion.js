// ADR-0051: the small amount of script behind the site's motion. Everything here is an extra: without it the page
// shows the final numbers and still cards, and nothing runs for people who ask for reduced motion.
const still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// Animations pause while their band is off screen, so a long page never paints what no one sees.
const bands = document.querySelectorAll('[data-motion]');
if ('IntersectionObserver' in window && bands.length) {
    const watch = new IntersectionObserver((entries) => {
        entries.forEach((entry) => entry.target.toggleAttribute('data-offscreen', !entry.isIntersecting));
    });
    bands.forEach((band) => watch.observe(band));
}

// Figures count up from zero the first time they come into view; the server already wrote the final value.
const counters = document.querySelectorAll('[data-count-to]');
if (!still && 'IntersectionObserver' in window && counters.length) {
    const format = new Intl.NumberFormat('en');
    const run = (el) => {
        const target = Number(el.dataset.countTo);
        if (!Number.isFinite(target) || target <= 0) {
            return;
        }
        const final = el.textContent;
        const start = performance.now();
        const step = (now) => {
            const t = Math.min(1, (now - start) / 1400);
            el.textContent = t < 1 ? format.format(Math.round(target * (1 - (1 - t) ** 3))) : final;
            if (t < 1) {
                requestAnimationFrame(step);
            }
        };
        requestAnimationFrame(step);
    };
    const watch = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                watch.unobserve(entry.target);
                run(entry.target);
            }
        });
    }, { threshold: 0.6 });
    counters.forEach((el) => watch.observe(el));
}

// A soft light follows the pointer across cards (mouse and pen only; touch has no hover).
if (!still) {
    document.addEventListener('pointermove', (event) => {
        if (event.pointerType === 'touch') {
            return;
        }
        const card = event.target instanceof Element ? event.target.closest('.glow-card') : null;
        if (card) {
            const box = card.getBoundingClientRect();
            card.style.setProperty('--gx', `${event.clientX - box.left}px`);
            card.style.setProperty('--gy', `${event.clientY - box.top}px`);
        }
    }, { passive: true });
}
