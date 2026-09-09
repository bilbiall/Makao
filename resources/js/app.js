import './bootstrap';

import Alpine from 'alpinejs';
import { Chart, registerables } from 'chart.js';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

// Leaflet's default marker icon is loaded via relative paths that don't survive a
// bundler - without this, every marker renders as a broken image.
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const OSM_TILE_URL = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
const OSM_ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
const KENYA_CENTER = [0.0236, 37.9062];

// Registered once, globally, regardless of which Alpine instance ends up running (see
// below) - dashboard charts across every role's app-shell reference window.Chart.
Chart.register(...registerables);
window.Chart = Chart;

// Livewire v3 bundles and auto-starts its own Alpine instance whenever a page includes
// @livewireScripts. Also starting a second, separately-imported Alpine on the same page
// causes two competing instances - Livewire's own component hydration then silently
// fails (its wire:id elements never get registered, so every wire:click/wire:model
// becomes inert with no console error beyond a stray "multiple instances of Alpine
// running" warning). The app-shell layout marks pages that already include Livewire via
// data-has-livewire on <body>; only start our own Alpine when that's absent (the
// marketing site, which has no Livewire component of its own but still needs Alpine
// for its mobile nav toggle/FAQ accordion).
// Registered unconditionally (not gated by data-has-livewire) since "alpine:init"
// fires from whichever Alpine instance ends up starting - Livewire's own bundled one
// on app-shell pages, or the one started below on marketing pages. Using window.Alpine
// (not the `Alpine` import) inside the listener is what makes that work either way:
// by the time this fires, window.Alpine has already been pointed at the real instance.
document.addEventListener('alpine:init', () => {
    // Optional exact pin for a property, set by clicking/dragging a marker - never
    // by typing coordinates. Used on the "add/edit property" form; $wire.set syncs
    // the picked point back into the Livewire component's latitude/longitude.
    window.Alpine.data('locationPinPicker', (initialLat, initialLng) => ({
        lat: initialLat,
        lng: initialLng,
        map: null,
        marker: null,
        init() {
            const startLat = this.lat ?? -1.2921;
            const startLng = this.lng ?? 36.8219;

            this.map = L.map(this.$refs.map).setView([startLat, startLng], this.lat ? 15 : 12);
            L.tileLayer(OSM_TILE_URL, { attribution: OSM_ATTRIBUTION, maxZoom: 19 }).addTo(this.map);

            if (this.lat !== null && this.lng !== null) {
                this.placeMarker(this.lat, this.lng);
            }

            this.map.on('click', (e) => this.placeMarker(e.latlng.lat, e.latlng.lng));
        },
        placeMarker(lat, lng) {
            this.lat = lat;
            this.lng = lng;

            if (this.marker) {
                this.marker.setLatLng([lat, lng]);
            } else {
                this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);
                this.marker.on('dragend', () => {
                    const pos = this.marker.getLatLng();
                    this.placeMarker(pos.lat, pos.lng);
                });
            }

            this.$wire.set('latitude', lat);
            this.$wire.set('longitude', lng);
        },
        clearPin() {
            this.lat = null;
            this.lng = null;
            if (this.marker) {
                this.map.removeLayer(this.marker);
                this.marker = null;
            }
            this.$wire.set('latitude', null);
            this.$wire.set('longitude', null);
        },
    }));

    // Map view for a public search-results page - list view stays the default;
    // this only ever renders when the visitor explicitly toggles it on. `pins` is
    // the current page's results, each { lat, lng, title, price, url } (lat/lng
    // may be null for a listing whose area hasn't been geocoded, and is filtered
    // out below rather than guessed).
    window.Alpine.data('resultsMap', (pins) => ({
        pins,
        map: null,
        showMap: false,
        toggle() {
            this.showMap = !this.showMap;
            if (this.showMap) {
                this.$nextTick(() => this.render());
            }
        },
        render() {
            if (this.map) {
                this.map.remove();
                this.map = null;
            }

            this.map = L.map(this.$refs.map);
            L.tileLayer(OSM_TILE_URL, { attribution: OSM_ATTRIBUTION, maxZoom: 19 }).addTo(this.map);

            const points = this.pins.filter((p) => p.lat !== null && p.lng !== null);

            if (points.length === 0) {
                this.map.setView(KENYA_CENTER, 6);
                return;
            }

            const markers = points.map((p) => {
                const marker = L.marker([p.lat, p.lng]).addTo(this.map);
                marker.bindPopup(
                    `<a href="${p.url}" class="font-semibold">${p.title}</a><br>${p.price}`
                );
                return marker;
            });

            this.map.fitBounds(L.featureGroup(markers).getBounds().pad(0.2));
        },
    }));
});

if (!document.body.hasAttribute('data-has-livewire')) {
    window.Alpine = Alpine;
    Alpine.start();
}

// Global "is anything loading" signal for every Livewire request on the page
// (wire:click, wire:model.live, form saves, etc.) - a request with no visible
// feedback otherwise looks identical to a hung page. Livewire.hook('request', ...)
// fires for every component's round trip regardless of which one triggered it, so a
// single top-of-page progress bar (see components/layouts/app.blade.php) covers the
// whole app-shell without needing a wire:loading directive on every single button.
document.addEventListener('livewire:init', () => {
    Livewire.hook('request', ({ succeed, fail }) => {
        document.dispatchEvent(new CustomEvent('app:loading-start'));
        const done = () => document.dispatchEvent(new CustomEvent('app:loading-end'));
        succeed(done);
        fail(done);
    });
});
