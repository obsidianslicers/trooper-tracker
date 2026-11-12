export default function mapSelector(events) {
    return {
        map: null,
        markers: [],
        popup: null,

        init() {
            this.map = new google.maps.Map(this.$el, {
                center: { lat: 28.5, lng: -81.3 },
                zoom: 8,
            });

            this.centerOnUser();

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

        //  one popup for the whole map: clicking another pin moves it there, so only one
        //  event is open at a time
        showBootstrapPopup(event, marker) {
            if (!this.popup) {
                this.popup = new google.maps.InfoWindow();

                //  a click on the map itself closes it
                this.map.addListener('click', () => this.popup.close());
            }

            this.popup.setContent(this.popupContent(event));
            this.popup.open({ map: this.map, anchor: marker });
        },

        //  built from elements so the event's name is shown as text, never read as HTML
        popupContent(event) {
            const card = document.createElement('div');
            card.className = 'card shadow-sm';
            card.style.width = '16rem';
            card.innerHTML = `
                <div class="card-body p-2">
                    <h6 class="card-title mb-1">
                        <a class="text-decoration-none fw-bold"></a>
                    </h6>
                    <div class="small text-muted"></div>
                </div>
            `;

            const link = card.querySelector('a');
            link.href = event.url;
            link.textContent = event.name;
            card.querySelector('.text-muted').textContent = event.date_range;

            return card;
        },

        centerOnUser() {
            if (!navigator.geolocation) {
                console.warn("Geolocation not supported");
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const { latitude, longitude } = pos.coords;

                    this.map.setCenter({ lat: latitude, lng: longitude });
                    this.map.setZoom(12);

                    // Optional: drop a marker for the user
                    new google.maps.Marker({
                        position: { lat: latitude, lng: longitude },
                        map: this.map,
                        title: "You are here",
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 6,
                            fillColor: "#0d6efd",
                            fillOpacity: 1,
                            strokeColor: "#fff",
                            strokeWeight: 2,
                        },
                    });
                },
                (err) => {
                    console.warn("User denied geolocation", err);
                }
            );
        }

    }
}
