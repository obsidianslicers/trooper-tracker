import { t } from '../i18n.js';
import toastState from './toasts.js';

//  An HTMX response's X-Flash-Message ({ message, type, fadeOut? }) shows as a toast, like every
//  other FlashType message.
function showToast(response) {
    if (response && response.message && response.type) {
        toastState.show(response.message, { type: response.type, delay: response.fadeOut || 4000 });
    }
}

function showHtmxFlash(event) {
    try {
        const flashMessageJson = event.detail.xhr.getResponseHeader('X-Flash-Message');
        if (!flashMessageJson) {
            return;
        }

        showToast(JSON.parse(flashMessageJson));
    } catch (e) {
        console.error("Error parsing JSON or displaying flash message:", e);
    }
}

function showHtmxErrorFallback(event) {
    if (event.detail.xhr.getResponseHeader('X-Flash-Message')) {
        return;
    }

    if (event.detail.xhr.status === 413) {
        showToast({
            message: t('That upload is too large for the server. Please upload JPG, PNG, or WEBP images that are 50MB or smaller.'),
            type: 'danger',
            fadeOut: 5000,
        });
    }
}

//  htmx fires afterSwap on every element a response swapped in, out-of-band ones included (e.g.
//  the event page's "Your sign-up" summary), with detail.elt set to that element. Only the main
//  swap shows the message, or it would appear twice.
const swappedOutOfBand = new WeakSet();

document.body.addEventListener('htmx:oobAfterSwap', (event) => swappedOutOfBand.add(event.target));
document.body.addEventListener('htmx:afterSwap', (event) => {
    if (swappedOutOfBand.has(event.target)) {
        return;
    }

    showHtmxFlash(event);
});
document.body.addEventListener('htmx:responseError', function (event) {
    showHtmxFlash(event);
    showHtmxErrorFallback(event);
});
