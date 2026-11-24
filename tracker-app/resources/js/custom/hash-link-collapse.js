import { Collapse } from 'bootstrap';

// A link to "#id" does nothing when that element sits in a collapsed accordion: it has no box to
// scroll to. Open the sections first, then scroll. data-expand="<selector>" on the link opens
// more sections, e.g. "Sign up" opens the shifts that take sign-ups. Delegated, so it works for
// links added after the page loads.

function closedAncestors(target) {
    const closed = [];
    let el = target.closest('.collapse:not(.show)');
    while (el) {
        closed.push(el);
        el = el.parentElement?.closest('.collapse:not(.show)');
    }
    return closed;
}

// Returns false when nothing needed opening, so the browser follows the link as usual.
function reveal(target, extra = []) {
    const closed = [...new Set([...closedAncestors(target), ...extra])]
        .filter((el) => !el.classList.contains('show'));
    if (closed.length === 0) {
        return false;
    }

    let pending = closed.length;
    const scroll = () => target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    closed.forEach((el) => {
        el.addEventListener('shown.bs.collapse', () => {
            pending -= 1;
            if (pending === 0) {
                scroll();
            }
        }, { once: true });
        Collapse.getOrCreateInstance(el, { toggle: false }).show();
    });
    return true;
}

function targetOf(hash) {
    if (!hash || hash.length < 2) {
        return null;
    }
    try {
        return document.getElementById(decodeURIComponent(hash.slice(1)));
    } catch {
        return null;
    }
}

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href^="#"]');
    const target = link ? targetOf(link.getAttribute('href')) : null;
    if (!target) {
        return;
    }

    const extra = link.dataset.expand ? [...document.querySelectorAll(link.dataset.expand)] : [];
    if (reveal(target, extra)) {
        event.preventDefault();
        history.pushState(null, '', link.getAttribute('href'));
    }
});

// Arriving with a hash, e.g. a reload after following one of those links
document.addEventListener('DOMContentLoaded', () => {
    const target = targetOf(location.hash);
    if (target) {
        reveal(target);
    }
});
