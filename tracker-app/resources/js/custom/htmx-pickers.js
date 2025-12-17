document.addEventListener('click', function (evt) {
    const record = evt.target.closest('.modal-picker [data-property][data-id][data-name]');
    if (!record) return;

    const property = record.dataset.property;
    const id = record.dataset.id;
    const name = record.dataset.name;
    const eventName = record.dataset.event;

    if (eventName) {
        htmx.trigger(document, eventName, { property, id, name });
    } else {
        const container = document.getElementById(`picker-container-${property}`);
        if (!container) return;

        const hiddenInput = container.querySelector(`input[type="hidden"][name="${property}"]`);
        if (hiddenInput) hiddenInput.value = id;

        const textInput = container.querySelector(`input[type="text"][name="picker-${property}"]`);
        if (textInput) textInput.value = name;
    }

    const modalEl = record.closest('.modal-picker');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
});

