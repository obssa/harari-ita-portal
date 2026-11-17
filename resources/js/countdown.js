// The time left to a challenge deadline (ADR-0022). The server writes the figures, but public pages are cached at
// the edge, so this corrects them on load and once a minute. At the deadline it reloads once to show the next phase.
const pad = (n) => String(n).padStart(2, '0');

for (const root of document.querySelectorAll('[data-countdown]')) {
    const until = Date.parse(root.dataset.until);
    if (Number.isNaN(until)) {
        continue;
    }
    const parts = {
        days: root.querySelector('[data-part="days"]'),
        hours: root.querySelector('[data-part="hours"]'),
        minutes: root.querySelector('[data-part="minutes"]'),
    };
    let timer = null;
    const tick = () => {
        const left = Math.max(0, Math.floor((until - Date.now()) / 1000));
        parts.days.textContent = pad(Math.floor(left / 86400));
        parts.hours.textContent = pad(Math.floor((left % 86400) / 3600));
        parts.minutes.textContent = pad(Math.floor((left % 3600) / 60));
        if (left === 0) {
            clearInterval(timer);
            const key = `countdown:${root.dataset.until}`;
            try {
                if (!sessionStorage.getItem(key)) {
                    sessionStorage.setItem(key, '1');
                    window.location.reload();
                }
            } catch {
                // Storage blocked: stay on the page rather than risk reloading in a loop.
            }
        }
    };
    tick();
    timer = setInterval(tick, 60000);
}
