// The +/- on an accordion card's header follows its section. Delegated (Bootstrap's collapse
// events bubble), so cards HTMX swaps in and sections opened from script flip it too.
function setIcon(collapseEl, open) {
    const icon = collapseEl.closest('.card')?.querySelector('.collapse-icon');
    if (!icon) return;
    icon.classList.toggle('fa-minus', open);
    icon.classList.toggle('fa-plus', !open);
}

document.addEventListener('show.bs.collapse', (event) => setIcon(event.target, true));
document.addEventListener('hide.bs.collapse', (event) => setIcon(event.target, false));
