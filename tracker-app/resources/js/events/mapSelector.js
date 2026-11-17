import { t } from '../i18n.js';
//  closest the first view zooms in, so events at one venue still show their surroundings
const MAX_FIT_ZOOM = 12;

//  "Use Current Location": the user's own area, a town or two across
const USER_ZOOM = 11;

export default function mapSelector(events) {
    return {
        map: null,
        markers: [],
        popup: null,
        userMarker: null,
        canLocate: 'geolocation' in navigator,
        locating: false,
        locationError: '',

        init() {
            this.map = new google.maps.Map(this.$refs.map);

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

        //  Only on the button: the browser asks for permission the first time
        centerOnUser() {
            this.locating = true;
            this.locationError = '';

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const position = { lat: pos.coords.latitude, lng: pos.coords.longitude };

                    this.locating = false;
                    this.map.setCenter(position);
                    this.map.setZoom(USER_ZOOM);
                    this.showUser(position);
                },
                (err) => {
                    this.locating = false;
                    this.locationError = err.code === err.PERMISSION_DENIED
                        ? t('Location access is turned off for this site in your browser.')
                        : t("Couldn't find your location. Try again in a moment.");
                },
                //  a fix from the last 10 minutes is close enough and comes back at once
                { maximumAge: 10 * 60 * 1000, timeout: 10 * 1000 }
            );
        },

        //  a blue dot, moved rather than added again on another click
        showUser(position) {
            if (this.userMarker) {
                this.userMarker.setPosition(position);
                return;
            }

            this.userMarker = new google.maps.Marker({
                position,
                map: this.map,
                title: t('You are here'),
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 6,
                    fillColor: '#0d6efd',
                    fillOpacity: 1,
                    strokeColor: '#fff',
                    strokeWeight: 2,
                },
            });
        },
    }
}
