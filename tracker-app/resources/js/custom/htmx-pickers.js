/** PICKER PICKED **/
document.addEventListener('DOMContentLoaded', () => {
    function bindModalPickers() {
        // Attach to all modals with the class .picker-modal
        document.querySelectorAll('.modal-picker').forEach(function (modalEl) {
            modalEl.addEventListener('click', function (evt) {
                const record = evt.target.closest("[data-property][data-id][data-name]");
                if (!record) return;

                const property = record.dataset.property;
                const id = record.dataset.id;
                const name = record.dataset.name;

                // Find the matching picker container
                const container = document.getElementById(`picker-container-${property}`);
                if (!container) return;

                // Update hidden input
                const hiddenInput = container.querySelector(`input[type="hidden"][name="${property}"]`);
                if (hiddenInput) hiddenInput.value = id;

                // Update text input
                const textInput = container.querySelector(`input[type="text"][name="picker-${property}"]`);
                if (textInput) textInput.value = name;

                // Close the modal
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            });
        });
    }
    bindModalPickers();
    document.body.addEventListener('htmx:afterSettle', bindModalPickers);
});