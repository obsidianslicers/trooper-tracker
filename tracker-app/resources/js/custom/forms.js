/** SCOPED BUTTON DISABLE FOR HTMX **/
document.body.addEventListener('htmx:configRequest', function (event) {
    const form = event.detail.elt;
    if (!form || form.tagName !== 'FORM') return;

    // Find the submit button that was clicked
    const activeElement = document.activeElement;
    const button = form.querySelector('button[data-action="htmx-disable"]');

    // Confirm it's the one that triggered the form
    if (button && (button === activeElement || activeElement.form === form)) {
        button.disabled = true;
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Transmitting ...';

        const restore = () => {
            button.disabled = false;
            if (button.dataset.originalText) {
                button.innerHTML = button.dataset.originalText;
                delete button.dataset.originalText;
            }
        };

        document.body.addEventListener('htmx:afterRequest', restore, { once: true });
        document.body.addEventListener('htmx:responseError', restore, { once: true });
    }
});

/** SCOPED BUTTON DISABLE FOR HTML **/
document.addEventListener('DOMContentLoaded', () => {  // Attach listener to every form on the page
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', () => {
            // Target only the primary submit button (data-action="htmx-disable"), not
            // secondary buttons like Remove that use formaction for a different endpoint.
            const button = form.querySelector('button[data-action="htmx-disable"]');

            if (!button) return;

            // If the form or button has HTMX attributes,
            // abort this listener and let the HTMX-specific logic handle it.
            const isHtmx = form.hasAttribute('hx-post') ||
                form.hasAttribute('hx-put') ||
                button.hasAttribute('hx-post');

            if (isHtmx) return;

            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Submitting ...';
        });
    });
});

/** RESET SUBMIT BUTTONS ON BFCACHE RESTORE **/
window.addEventListener('pageshow', (event) => {
    if (!event.persisted) return;

    document.querySelectorAll('button').forEach(button => {
        if (button.dataset.originalText) {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText;
            delete button.dataset.originalText;
        }
    });
});

/** DISALLOW ENTER KEY SUBMISSION **/
document.addEventListener('DOMContentLoaded', () => {
    function bindPreventEnterSubmit(root) {
        root.querySelectorAll('form').forEach(form => {
            // Only bind once per form
            // Skip prevention if this form explicitly allows Enter
            const allowsEnter = form.classList.contains('allow-enter-keypress');
            if (allowsEnter) return;

            if (!form.dataset.preventEnterBound) {
                form.addEventListener('keydown', event => {
                    if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') {
                        event.preventDefault();
                    }
                });
                form.dataset.preventEnterBound = 'true';
            }
        });
    }

    // Bind for initial page load
    bindPreventEnterSubmit(document);

    // Bind for any new content HTMX swaps in
    document.body.addEventListener('htmx:afterSettle', evt => {
        bindPreventEnterSubmit(evt.target);
    });
});
