document.querySelectorAll('.upload-zone').forEach(zone => {

    const input = zone.querySelector('.image-input');

    // Click → open file picker
    zone.addEventListener('click', () => input.click());

    // File picker → submit
    input.addEventListener('change', () => {
        zone.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    });

    // Drag-over styling
    zone.addEventListener('dragover', e => {
        e.preventDefault();
        zone.classList.add('bg-white');
    });

    zone.addEventListener('dragleave', () => {
        zone.classList.remove('bg-white');
    });

    // Drop → assign files → submit
    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('bg-white');
        input.files = e.dataTransfer.files;
        zone.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    });
});
