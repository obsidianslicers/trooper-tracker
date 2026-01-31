export default function mapSelector(events) {
    return {
        map: null,
        markers: [],

        init() {
            this.map = new google.maps.Map(this.$el, {
                center: { lat: 28.5, lng: -81.3 },
                zoom: 8,
            });

            events.forEach(event => {
                const marker = new google.maps.Marker({
                    position: { lat: event.lat, lng: event.lng },
                    map: this.map,
                    title: event.name,
                });

                marker.addListener('click', () => {
                    this.showBootstrapPopup(event, marker);
                });

                this.markers.push(marker);
            });
        },

        showBootstrapPopup(event, marker) {
            const content = `
                <div class="card shadow-sm" style="width: 16rem;">
                    <div class="card-body p-2">
                        <h6 class="card-title mb-1">
                            <a href="${event.url}" class="text-decoration-none fw-bold">
                                ${event.name}
                            </a>
                        </h6>
                        <div class="small text-muted">
                            ${event.date_range}
                        </div>
                    </div>
                </div>
            `;

            const info = new google.maps.InfoWindow({
                content: content
            });

            info.open(this.map, marker);
        }
    }
}
