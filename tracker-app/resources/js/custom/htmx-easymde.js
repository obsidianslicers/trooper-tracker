// Initialize editor on a textarea
document.addEventListener("DOMContentLoaded", () => {
    function bindSimpleMDE() {
        document.querySelectorAll(".markdown-editor").forEach((textarea) => {
            // Avoid re-initializing if already bound
            if (!textarea.dataset.simplemdeBound) {
                new EasyMDE({ element: textarea });
                textarea.dataset.simplemdeBound = "true";
            }
        });
    }

    bindSimpleMDE();
    document.body.addEventListener('htmx:afterSettle', bindSimpleMDE);
});
