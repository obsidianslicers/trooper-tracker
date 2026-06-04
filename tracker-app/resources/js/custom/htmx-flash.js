/** FLASH AFTER SWAP / ERROR **/
function resolveFlashContainer(event) {
    const targetSelector = event.detail.elt?.dataset?.flashTarget;

    if (targetSelector) {
        const target = document.querySelector(targetSelector);

        if (target) {
            return target;
        }
    }

    return document.getElementById('flash-messages');
}

function appendFlashMessage(response, messagesContainer) {
    if (response && response.message && response.type) {
        if (!messagesContainer) {
            console.error('Flash message container not found.');
            return;
        }

        // Create the alert div
        const messageDiv = document.createElement('div');
        messageDiv.className = `alert alert-${response.type} alert-dismissible fade show mt-2`;

        // Create the close button
        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'btn-close float-end';
        closeButton.setAttribute('data-bs-dismiss', 'alert');
        messageDiv.appendChild(closeButton);

        // Create the strong message element
        const strong = document.createElement('strong');
        strong.textContent = response.message;
        messageDiv.appendChild(strong);

        // Append and fade in
        messagesContainer.appendChild(messageDiv);

        requestAnimationFrame(() => {
            messageDiv.style.transition = 'opacity 0.3s ease-in-out';
            messageDiv.style.opacity = '1';
        });

        // Scroll into view if needed
        const rect = messagesContainer.getBoundingClientRect();
        const isOutOfView = rect.top < 0 || rect.bottom > window.innerHeight;
        if (isOutOfView && response.focus) {
            messagesContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        const fadeOut = response.fadeOut || 2000;

        // Fade out and remove after fadeOut duration
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            messageDiv.style.transition = 'opacity 0.5s ease-in-out';
            setTimeout(() => {
                messageDiv.remove();
            }, 500);
        }, fadeOut);
    }
}

function showHtmxFlash(event) {
    try {
        const flashMessageJson = event.detail.xhr.getResponseHeader('X-Flash-Message');
        if (!flashMessageJson) {
            return;
        }

        appendFlashMessage(JSON.parse(flashMessageJson), resolveFlashContainer(event));
    } catch (e) {
        console.error("Error parsing JSON or displaying flash message:", e);
    }
}

function showHtmxErrorFallback(event) {
    if (event.detail.xhr.getResponseHeader('X-Flash-Message')) {
        return;
    }

    if (event.detail.xhr.status === 413) {
        appendFlashMessage({
            message: 'That upload is too large for the server. Please upload JPG, PNG, or WEBP images that are 12MB or smaller.',
            type: 'danger',
            fadeOut: 5000,
        }, resolveFlashContainer(event));
    }
}

document.body.addEventListener('htmx:afterSwap', showHtmxFlash);
document.body.addEventListener('htmx:responseError', function (event) {
    showHtmxFlash(event);
    showHtmxErrorFallback(event);
});
