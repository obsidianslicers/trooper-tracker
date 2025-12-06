/** HTMX CSRF TOKEN **/
document.addEventListener('htmx:configRequest', function (event) {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    event.detail.headers['X-CSRF-TOKEN'] = token;
});