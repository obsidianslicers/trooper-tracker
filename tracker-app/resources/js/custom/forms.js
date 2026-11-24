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
