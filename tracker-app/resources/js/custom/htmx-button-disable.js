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
            const button = form.querySelector('button[type=submit]');
            if (button) {
                button.disabled = true;
                button.dataset.originalText = button.innerHTML;
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Submitting ...';
            }
        });
    });
});