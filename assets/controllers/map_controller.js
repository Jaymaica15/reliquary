import { Controller } from '@hotwired/stimulus';
import L from 'leaflet';

export default class extends Controller {
    static targets = ['container'];
    static values = {
        relics: Array,
        radius: Number
    };

    connect() {
        if (!this.hasContainerTarget) {
            console.error('Map container target not found');
            return;
        }

        // Load Leaflet CSS
        if (!document.querySelector('link[href*="leaflet.css"]')) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            link.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
            link.crossOrigin = '';
            document.head.appendChild(link);
        }

        // Initialize the map
        this.initializeMap();
    }

    initializeMap() {
        // Default view centered on Vatican City if no relics with coordinates
        let defaultLat = 41.9022;
        let defaultLng = 12.4539;
        let defaultZoom = 13;

        // Create the map
        this.map = L.map(this.containerTarget).setView([defaultLat, defaultLng], defaultZoom);

        // Add the OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(this.map);

        // Add a circle to represent the search radius
        if (this.hasRelicsValue && this.relicsValue.length > 0) {
            this.addRelicMarkers();
        } else {
            // Add a message if no relics found
            const noRelicsMessage = L.control({ position: 'bottomcenter' });
            noRelicsMessage.onAdd = function() {
                const div = L.DomUtil.create('div', 'no-relics-message');
                div.innerHTML = '<div class="alert alert-info">No relics found within the search radius.</div>';
                return div;
            };
            noRelicsMessage.addTo(this.map);
        }
    }

    addRelicMarkers() {
        // Find relics with valid coordinates
        const relicsWithCoords = this.relicsValue.filter(relic => 
            relic.latitude !== null && relic.longitude !== null
        );

        if (relicsWithCoords.length === 0) {
            return;
        }

        // Create a bounds object to fit all markers
        const bounds = L.latLngBounds();

        // Add markers for each relic
        relicsWithCoords.forEach(relic => {
            const marker = L.marker([relic.latitude, relic.longitude]).addTo(this.map);
            
            // Add popup with relic info
            marker.bindPopup(`
                <strong>${relic.saint}</strong><br>
                ${relic.address || ''}<br>
                ${relic.location || ''}<br>
                <a href="/relic/${relic.id}" class="btn btn-sm btn-primary mt-2">View Details</a>
            `);
            
            // Extend bounds to include this marker
            bounds.extend([relic.latitude, relic.longitude]);
        });

        // Fit the map to show all markers
        if (bounds.isValid()) {
            this.map.fitBounds(bounds);
            
            // Add a circle to show the search radius around the center of the bounds
            const center = bounds.getCenter();
            L.circle(center, {
                radius: this.radiusValue * 1000, // Convert km to meters
                color: 'blue',
                fillColor: '#30f',
                fillOpacity: 0.1
            }).addTo(this.map);
        }
    }
}