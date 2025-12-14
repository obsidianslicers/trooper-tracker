document.addEventListener('DOMContentLoaded', function () {
    // Find all collapse elements inside cards
    document.querySelectorAll('.card .collapse').forEach(collapseEl => {
        const icon = collapseEl.closest('.card').querySelector('.collapse-icon');
        if (!icon) return; // skip if no icon

        collapseEl.addEventListener('show.bs.collapse', () => {
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        });

        collapseEl.addEventListener('hide.bs.collapse', () => {
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        });
    });
});