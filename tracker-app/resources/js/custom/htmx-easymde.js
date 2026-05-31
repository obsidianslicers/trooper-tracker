// Initialize editor on a textarea
document.addEventListener("DOMContentLoaded", () => {
    function bindSimpleMDE() {
        document.querySelectorAll(".markdown-editor").forEach((textarea) => {
            // Avoid re-initializing if already bound
            if (!textarea.dataset.simplemdeBound) {
                textarea._easyMDE = new EasyMDE({ element: textarea });
                textarea.dataset.simplemdeBound = "true";

                const panel = document.getElementById('smilies-panel-' + textarea.id);
                const editorToolbar = textarea._easyMDE.gui?.toolbar;
                if (panel && editorToolbar) {
                    const sep = document.createElement('i');
                    sep.className = 'separator';
                    editorToolbar.appendChild(sep);

                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.title = 'Smilies';
                    btn.innerHTML = '<i class="far fa-smile"></i>';
                    btn.addEventListener('click', () =>
                        window.dispatchEvent(new CustomEvent('smilies-toggle-' + textarea.id))
                    );
                    editorToolbar.appendChild(btn);
                }
            }
        });
    }

    bindSimpleMDE();
    document.body.addEventListener('htmx:afterSettle', bindSimpleMDE);
});
