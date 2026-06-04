/** FLASH AFTER SWAP / ERROR **/
function showHtmxFlash(event) {
    try {
        const flashMessageJson = event.detail.xhr.getResponseHeader('X-Flash-Message');
        if (!flashMessageJson) {
            return;
        }
        const response = JSON.parse(flashMessageJson);

        if (response && response.message && response.type) {
            const messagesContainer = document.getElementById('flash-messages');
            if (!messagesContainer) {
                console.error('Flash message container not found.');
                return; // Important: Stop if container is missing
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
    } catch (e) {
        console.error("Error parsing JSON or displaying flash message:", e);
    }
}

document.body.addEventListener('htmx:afterSwap', showHtmxFlash);
document.body.addEventListener('htmx:responseError', showHtmxFlash);
