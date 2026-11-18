// ADR-0047: the header menus are <details> elements, which work without script. This closes an open menu when the
// visitor clicks or taps outside it, or presses Escape (focus returns to the menu's button).
const openMenus = () => document.querySelectorAll('details[name="site-nav"][open]');

document.addEventListener('click', (event) => {
    openMenus().forEach((menu) => {
        if (!menu.contains(event.target)) {
            menu.removeAttribute('open');
        }
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }
    openMenus().forEach((menu) => {
        menu.removeAttribute('open');
        menu.querySelector('summary')?.focus();
    });
});
