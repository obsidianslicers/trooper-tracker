import { t } from "$lib/i18n";
import type { LatLng } from "../EventMapPins";
import type { MapEvent, MapPageData } from "../types";
import { EventFiltersViewModel } from "./EventFiltersViewModel.svelte";

export class MapViewModel extends EventFiltersViewModel {
    events: MapEvent[] = [];

    can_locate: boolean = typeof navigator !== "undefined" && "geolocation" in navigator;
    locating: boolean = $state(false);
    location_error: string = $state("");
    user_position: LatLng | null = $state(null);

    constructor(page_data: Partial<MapPageData>) {
        super(page_data);

        this.events = page_data.events ?? [];
    }

    filtered: MapEvent[] = $derived.by(() => this.events.filter((event) => this.matches(event)));

    //  Only on the button: the browser asks for permission the first time.
    locate = () => {
        this.locating = true;
        this.location_error = "";

        navigator.geolocation.getCurrentPosition(
            (position) => {
                this.locating = false;
                this.user_position = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };
            },
            (error) => {
                this.locating = false;
                this.location_error = error.code === error.PERMISSION_DENIED
                    ? t("Location access is turned off for this site in your browser.")
                    : t("Couldn't find your location. Try again in a moment.");
            },
            //  a fix from the last 10 minutes is close enough and comes back at once
            { maximumAge: 10 * 60 * 1000, timeout: 10 * 1000 },
        );
    };
}
