//  closest the first view zooms in, so events at one venue still show their surroundings
const MAX_FIT_ZOOM = 12;

export default function mapSelector(events) {
    return {
        map: null,
        markers: [],
        popup: null,

        init() {
            this.map = new google.maps.Map(this.$el);

            this.showEvents();

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

        //  The first view: every upcoming event, or the continental US when there are none
        showEvents() {
            if (events.length === 0) {
                this.map.setCenter({ lat: 39.8, lng: -98.6 });
                this.map.setZoom(4);
                return;
            }

            const bounds = new google.maps.LatLngBounds();
            events.forEach(event => bounds.extend({ lat: event.lat, lng: event.lng }));
            this.map.fitBounds(bounds);

            //  fitBounds zooms all the way in on a single event or a single venue
            google.maps.event.addListenerOnce(this.map, 'idle', () => {
                if (this.map.getZoom() > MAX_FIT_ZOOM) {
                    this.map.setZoom(MAX_FIT_ZOOM);
                }
            });
        },
    }
}
