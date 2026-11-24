import { t } from '../i18n.js';
/** SCOPED BUTTON DISABLE **/
document.addEventListener('DOMContentLoaded', () => {  // Attach listener to every form on the page
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', () => {
            // Target only the primary submit button (data-action="disable-on-submit"), not
            // secondary buttons like Remove that use formaction for a different endpoint.
            const button = form.querySelector('button[data-action="disable-on-submit"]');

            if (!button) return;

            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> ' + t('Submitting ...');
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

    bindPreventEnterSubmit(document);
});
