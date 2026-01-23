export default function cardNavigator() {
    return {
        navigate(event) {
            const card = event.target.closest('[data-route]');
            if (!card) {
                return;

            }

            const route = card.dataset.route;
            if (route) {
                window.location.href = route;
            }
        }
    };
}
