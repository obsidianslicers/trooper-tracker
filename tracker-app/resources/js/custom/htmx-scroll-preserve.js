/** PRESERVE SCROLL POSITION ACROSS HTMX SWAPS **/
//
// hx-target/hx-select often swap a large container (e.g. an entire shift's
// trooper list) for a change to a single row. If the swapped-in content is a
// different height than before, the page appears to "jump" even though the
// user's scroll offset never moved. This keeps the swapped target pinned to
// the same on-screen position it had right before the swap.

const pendingSwaps = new WeakMap();

document.body.addEventListener('htmx:beforeSwap', function (event) {
    const target = event.detail.target;

    if (!target || !target.id || event.detail.boosted) {
        return;
    }

    pendingSwaps.set(event.detail.xhr, {
        id: target.id,
        top: target.getBoundingClientRect().top,
    });
});

document.body.addEventListener('htmx:afterSettle', function (event) {
    const swap = pendingSwaps.get(event.detail.xhr);

    if (!swap) {
        return;
    }

    pendingSwaps.delete(event.detail.xhr);

    const settledTarget = document.getElementById(swap.id);

    if (!settledTarget) {
        return;
    }

    const delta = settledTarget.getBoundingClientRect().top - swap.top;

    if (delta !== 0) {
        window.scrollBy(0, delta);
    }
});
