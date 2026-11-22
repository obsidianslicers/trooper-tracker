import { t } from "$lib/i18n";
import type { MapEvent } from "./types";

//  Google's script has no types in this project; these are the few parts the map uses.
declare const google: any;

export type LatLng = { lat: number; lng: number };

//  closest the first view zooms in, so events at one venue still show their surroundings
const MAX_FIT_ZOOM = 12;

//  "Use Current Location": the user's own area, a town or two across
const USER_ZOOM = 11;

export function hasGoogleMaps(): boolean {
    return typeof google !== "undefined" && !!google?.maps;
}

/**
 * The pins on the events map: one per event, one popup at a time, and a blue dot for the
 * trooper once they've asked for their location.
 */
export class EventMapPins {
    map: any;
    markers: any[] = [];
    popup: any = null;
    user_marker: any = null;

    private fitted = false;

    constructor(element: HTMLElement) {
        this.map = new google.maps.Map(element);
    }

    //  Called again as the filters change; only the first call moves the map.
    showEvents(events: MapEvent[]): void {
        this.markers.forEach((marker) => marker.setMap?.(null));
        this.popup?.close();

        this.markers = events.map((event) => {
            const marker = new google.maps.Marker({
                position: { lat: event.lat, lng: event.lng },
                map: this.map,
                title: event.name,
            });

            marker.addListener("click", () => this.openPopup(event, marker));

            return marker;
        });

        if (!this.fitted) {
            this.fitted = true;
            this.fit(events);
        }
    }

    //  one popup for the whole map: clicking another pin moves it there
    openPopup(event: MapEvent, marker: any): void {
        if (!this.popup) {
            this.popup = new google.maps.InfoWindow();

            //  a click on the map itself closes it
            this.map.addListener("click", () => this.popup.close());
        }

        this.popup.setContent(popupContent(event));
        this.popup.open({ map: this.map, anchor: marker });
    }

    //  a blue dot, moved rather than added again on another click
    showUser(position: LatLng): void {
        this.map.setCenter(position);
        this.map.setZoom(USER_ZOOM);

        if (this.user_marker) {
            this.user_marker.setPosition(position);
            return;
        }

        this.user_marker = new google.maps.Marker({
            position,
            map: this.map,
            title: t("You are here"),
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 6,
                fillColor: "#0d6efd",
                fillOpacity: 1,
                strokeColor: "#fff",
                strokeWeight: 2,
            },
        });
    }

    //  The first view: every upcoming event, or the continental US when there are none
    private fit(events: MapEvent[]): void {
        if (events.length === 0) {
            this.map.setCenter({ lat: 39.8, lng: -98.6 });
            this.map.setZoom(4);
            return;
        }

        const bounds = new google.maps.LatLngBounds();
        events.forEach((event) => bounds.extend({ lat: event.lat, lng: event.lng }));
        this.map.fitBounds(bounds);

        //  fitBounds zooms all the way in on a single event or a single venue
        google.maps.event.addListenerOnce(this.map, "idle", () => {
            if (this.map.getZoom() > MAX_FIT_ZOOM) {
                this.map.setZoom(MAX_FIT_ZOOM);
            }
        });
    }
}

//  built from elements so the event's name is shown as text, never read as HTML
function popupContent(event: MapEvent): HTMLElement {
    const card = document.createElement("div");
    card.className = "card shadow-sm";
    card.style.width = "16rem";

    const body = document.createElement("div");
    body.className = "card-body p-2";

    const title = document.createElement("h6");
    title.className = "card-title mb-1";

    const link = document.createElement("a");
    link.className = "text-decoration-none fw-bold";
    link.href = event.url;
    link.textContent = event.name;

    const when = document.createElement("div");
    when.className = "small text-muted";
    when.textContent = event.date_range;

    title.append(link);
    body.append(title, when);
    card.append(body);

    return card;
}
