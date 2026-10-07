import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import '@geoman-io/leaflet-geoman-free';
import '@geoman-io/leaflet-geoman-free/dist/leaflet-geoman.css';

// Fix default marker icon paths when bundled with Vite
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

window.L = L;

/**
 * Alpine component: leafletMap
 *
 * Modes:
 *  - marker:  click on map to place a single draggable marker. State: {lat, lng}
 *  - polygon: draw/edit a polygon geofence. State: [[lat, lng], ...]
 */
function leafletMapComponent(config) {    return {
        state: config.state ?? null,
        mode: config.mode ?? 'marker',
        defaultLat: config.defaultLat ?? -6.870255717160778,
        defaultLng: config.defaultLng ?? 109.18670476501677,
        defaultZoom: config.defaultZoom ?? 13,
        hint: '',

        map: null,
        marker: null,
        polygon: null,

        init() {
            this.hint = this.mode === 'polygon'
                ? 'Gunakan toolbar di peta untuk menggambar polygon geofence (klik titik-titik membentuk area, klik titik pertama untuk menutup).'
                : 'Klik pada peta untuk menempatkan titik lokasi. Marker dapat digeser (drag).';

            this.map = L.map(this.$refs.map, {
                center: this.getInitialCenter(),
                zoom: this.defaultZoom,
            });

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            }).addTo(this.map);

            if (this.mode === 'polygon') {
                this.initPolygonMode();
            } else {
                this.initMarkerMode();
            }

            // Re-render correctly when inside modal / tabs
            setTimeout(() => this.map.invalidateSize(), 300);
        },

        getInitialCenter() {
            if (this.mode === 'marker' && this.state && this.state.lat && this.state.lng) {
                return [this.state.lat, this.state.lng];
            }

            if (this.mode === 'polygon' && Array.isArray(this.state) && this.state.length > 0) {
                return this.state[0];
            }

            return [this.defaultLat, this.defaultLng];
        },

        initMarkerMode() {
            if (this.state && this.state.lat && this.state.lng) {
                this.marker = L.marker([this.state.lat, this.state.lng], { draggable: true }).addTo(this.map);
                this.bindMarkerDrag();
            }

            this.map.on('click', (e) => {
                const { lat, lng } = e.latlng;

                if (this.marker) {
                    this.marker.setLatLng([lat, lng]);
                } else {
                    this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);
                    this.bindMarkerDrag();
                }

                this.syncMarkerState();
            });
        },

        bindMarkerDrag() {
            this.marker.on('dragend', () => this.syncMarkerState());
        },

        syncMarkerState() {
            const { lat, lng } = this.marker.getLatLng();
            this.state = {
                lat: parseFloat(lat.toFixed(7)),
                lng: parseFloat(lng.toFixed(7)),
            };
        },

        initPolygonMode() {
            this.map.pm.addControls({
                position: 'topleft',
                drawMarker: false,
                drawCircleMarker: false,
                drawPolyline: false,
                drawRectangle: false,
                drawCircle: false,
                drawText: false,
                drawPolygon: true,
                editMode: true,
                dragMode: false,
                cutPolygon: false,
                removalMode: true,
                rotateMode: false,
            });

            // Restore existing polygon
            if (Array.isArray(this.state) && this.state.length >= 3) {
                this.polygon = L.polygon(this.state).addTo(this.map);
                this.map.fitBounds(this.polygon.getBounds(), { padding: [20, 20] });
                this.bindPolygonEdit(this.polygon);
            }

            this.map.on('pm:create', (e) => {
                // Only one geofence polygon allowed
                if (this.polygon) {
                    this.map.removeLayer(this.polygon);
                    this.polygon = null;
                }

                this.polygon = e.layer;
                this.bindPolygonEdit(this.polygon);
                this.syncPolygonState();
            });

            this.map.on('pm:remove', (e) => {
                if (this.polygon && e.layer === this.polygon) {
                    this.polygon = null;
                    this.state = null;
                }
            });
        },

        bindPolygonEdit(layer) {
            layer.on('pm:edit', () => this.syncPolygonState());
        },

        syncPolygonState() {
            if (!this.polygon) {
                this.state = null;
                return;
            }

            const latLngs = this.polygon.getLatLngs()[0] ?? [];

            this.state = latLngs.map((point) => [
                parseFloat(point.lat.toFixed(7)),
                parseFloat(point.lng.toFixed(7)),
            ]);
        },
    };
};

/**
 * Alpine component: companyAreaMap (dashboard widget)
 *
 * Renders company geofence polygons + area markers.
 * Clicking an area marker lazy-loads its equipment tag numbers.
 */
function companyAreaMapComponent(config) {
    return {
        companies: config.companies ?? [],
        colors: config.colors ?? {},
        map: null,
        layers: null,

        init() {
            const first = this.companies.find((c) => c.geofence?.length || c.areas?.length);
            let center = [-6.870255717160778, 109.18670476501677];

            if (first?.geofence?.length) {
                center = first.geofence[0];
            } else if (first?.areas?.length) {
                center = [first.areas[0].lat, first.areas[0].lng];
            }

            this.map = L.map(this.$refs.map, { center, zoom: 13 });

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            }).addTo(this.map);

            this.layers = L.layerGroup().addTo(this.map);
            this.drawCompanies();

            setTimeout(() => this.map.invalidateSize(), 300);
        },

        drawCompanies() {
            const allBounds = [];

            this.companies.forEach((company) => {
                const color = this.colors[company.color] ?? '#f59e0b';

                if (Array.isArray(company.geofence) && company.geofence.length >= 3) {
                    const polygon = L.polygon(company.geofence, {
                        color,
                        weight: 2,
                        fillColor: color,
                        fillOpacity: 0.12,
                    }).addTo(this.layers);

                    polygon.bindPopup(`
                        <div style="min-width:180px">
                            <div style="font-weight:600;font-size:14px">${this.escapeHtml(company.name)}</div>
                            <div style="font-size:12px;color:#6b7280">${this.escapeHtml(company.code)}</div>
                            <div style="font-size:12px;margin-top:4px">${company.areas.length} area</div>
                        </div>
                    `);

                    allBounds.push(...company.geofence);
                }

                company.areas.forEach((area) => {
                    const marker = L.circleMarker([area.lat, area.lng], {
                        radius: 8,
                        color: '#ffffff',
                        weight: 2,
                        fillColor: color,
                        fillOpacity: 1,
                    }).addTo(this.layers);

                    marker.on('click', () => this.showAreaEquipment(area));

                    allBounds.push([area.lat, area.lng]);
                });
            });

            if (allBounds.length) {
                this.map.fitBounds(allBounds, { padding: [30, 30] });
            }
        },

        async showAreaEquipment(area) {
            const popup = L.popup()
                .setLatLng([area.lat, area.lng])
                .setContent('<div style="padding:8px;font-size:13px">Memuat...</div>')
                .openOn(this.map);

            try {
                const data = await this.$wire.getAreaEquipment(area.id);

                if (!data) {
                    popup.setContent('<div style="padding:8px;font-size:13px">Data tidak ditemukan.</div>');
                    return;
                }

                const rows = data.equipments.map((eq) => `
                    <tr>
                        <td style="padding:3px 8px;font-size:12px;border-bottom:1px solid #e5e7eb">${this.escapeHtml(eq.tag_number)}</td>
                        <td style="padding:3px 8px;font-size:12px;border-bottom:1px solid #e5e7eb">${this.escapeHtml(eq.status ?? '-')}</td>
                    </tr>
                `).join('');

                popup.setContent(`
                    <div style="min-width:260px;max-width:320px">
                        <div style="font-weight:600;font-size:14px">${this.escapeHtml(data.area)}</div>
                        <div style="font-size:12px;color:#6b7280;margin-bottom:6px">${this.escapeHtml(data.company ?? '')} &middot; Total: ${data.total} equipment</div>
                        <div style="max-height:220px;overflow-y:auto">
                            <table style="width:100%;border-collapse:collapse">
                                <thead>
                                    <tr>
                                        <th style="text-align:left;padding:3px 8px;font-size:12px;border-bottom:2px solid #d1d5db">Tag Number</th>
                                        <th style="text-align:left;padding:3px 8px;font-size:12px;border-bottom:2px solid #d1d5db">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${rows || '<tr><td colspan="2" style="padding:6px 8px;font-size:12px;color:#6b7280">Tidak ada equipment.</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                        ${data.total > data.equipments.length ? `<div style="font-size:11px;color:#6b7280;margin-top:4px">Menampilkan ${data.equipments.length} dari ${data.total}</div>` : ''}
                        ${data.history_url ? `
                            <a
                                href="${data.history_url}"
                                target="_blank"
                                rel="noopener noreferrer"
                                style="display:inline-flex;align-items:center;gap:4px;margin-top:10px;padding:5px 12px;font-size:12px;font-weight:500;background:#f59e0b;color:#fff;border-radius:6px;text-decoration:none"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z" clip-rule="evenodd" />
                                </svg>
                                Lihat Riwayat Maintenance
                            </a>
                        ` : ''}
                    </div>
                `);
            } catch (error) {
                popup.setContent('<div style="padding:8px;font-size:13px;color:#ef4444">Gagal memuat data.</div>');
            }
        },

        escapeHtml(value) {
            if (value === null || value === undefined) return '';
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },
    };
};

function registerComponents() {
    if (!window.Alpine) return;

    window.Alpine.data('leafletMap', leafletMapComponent);
    window.Alpine.data('companyAreaMap', companyAreaMapComponent);
}

if (window.Alpine) {
    registerComponents();
} else {
    document.addEventListener('alpine:init', registerComponents);
}

window.leafletMap = leafletMapComponent;
window.companyAreaMap = companyAreaMapComponent;

export default L;
