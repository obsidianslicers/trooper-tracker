/** FLASH AFTER SWAP **/
document.body.addEventListener('htmx:afterSwap', function (event) {
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
            if (isOutOfView) {
                messagesContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            // Fade out and remove after 2 seconds
            setTimeout(() => {
                messageDiv.style.opacity = '0';
                messageDiv.style.transition = 'opacity 0.5s ease-in-out';
                setTimeout(() => {
                    messageDiv.remove();
                }, 500);
            }, 2000);
        }
    } catch (e) {
        console.error("Error parsing JSON or displaying flash message:", e);
    }
});