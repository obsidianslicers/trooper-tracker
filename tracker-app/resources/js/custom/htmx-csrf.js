/** HTMX CSRF TOKEN **/
document.addEventListener('htmx:configRequest', function (event) {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    event.detail.headers['X-CSRF-TOKEN'] = token;
});
document.body.addEventListener('htmx:responseError', function (event) {
    if (event.detail.xhr.status === 419) {
        // Flash a message (Bootstrap example)
        alert('Your session has expired. Reloading ...');

        // Force a full reload so CSRF + session reset
        window.location.reload();
    }
});