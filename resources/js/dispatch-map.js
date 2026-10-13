import { Map, Marker, Popup, NavigationControl, LngLatBounds } from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';

function isWebGlSupported() {
    try {
        const canvas = document.createElement('canvas');
        return Boolean(window.WebGLRenderingContext && (canvas.getContext('webgl') || canvas.getContext('experimental-webgl')));
    } catch (e) {
        return false;
    }
}

/**
 * MBG-Track Smart Dispatch Map Component (MapLibre GL JS + OpenFreeMap)
 * Supports hybrid multi-stop routing, numbered school pins, active leg highlight,
 * and graceful fallback when WebGL or network routing is unavailable.
 */
export function initDispatchMap(containerId, options = {}) {
    const container = document.getElementById(containerId);
    if (!container) return null;

    // WebGL support check
    if (!isWebGlSupported()) {
        container.innerHTML = `
            <div class="flex flex-col items-center justify-center h-full p-6 text-center bg-slate-50 border border-slate-200 rounded-xl text-slate-600">
                <svg class="w-10 h-10 text-amber-500 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="font-medium text-slate-800">Akselerasi Grafis (WebGL) Tidak Tersedia</div>
                <div class="text-xs text-slate-500 mt-1 max-w-sm">Perangkat Anda tidak mendukung rendering peta interaktif WebGL. Informasi rute tetap dapat dibaca melalui daftar manifest pemberhentian.</div>
            </div>
        `;
        return null;
    }

    const {
        dataUrl = null,
        initialData = null,
        interactive = true,
        onStopSelected = null,
    } = options;

    let map = null;
    let markers = [];

    const defaultCenter = [112.735, -7.288]; // Surabaya default center
    const defaultZoom = 12;

    try {
        map = new Map({
            container: containerId,
            style: 'https://tiles.openfreemap.org/styles/liberty',
            center: defaultCenter,
            zoom: defaultZoom,
            interactive: interactive,
            attributionControl: true,
        });

        map.addControl(new NavigationControl({ showCompass: false }), 'top-right');
    } catch (err) {
        console.error('MapLibre initialization failed:', err);
        container.innerHTML = `
            <div class="flex items-center justify-center h-full p-6 text-slate-500 bg-slate-50 rounded-xl text-xs">
                Gagal memuat basemap peta. Periksa koneksi internet Anda.
            </div>
        `;
        return null;
    }

    function renderMapData(data) {
        if (!data || !map) return;

        // Clear existing markers
        markers.forEach(m => m.remove());
        markers = [];

        const bounds = new LngLatBounds();
        let hasPoints = false;

        // 1. Add SPPG Marker
        if (data.sppg && data.sppg.lat && data.sppg.lng) {
            const sppgEl = document.createElement('div');
            sppgEl.className = 'flex items-center justify-center w-8 h-8 rounded-full bg-emerald-700 text-white font-bold text-xs shadow-md border-2 border-white ring-2 ring-emerald-500 cursor-pointer';
            sppgEl.innerHTML = `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>`;
            sppgEl.title = `Dapur SPPG: ${data.sppg.name}`;

            const sppgMarker = new Marker({ element: sppgEl })
                .setLngLat([data.sppg.lng, data.sppg.lat])
                .setPopup(new Popup({ offset: 12 }).setHTML(`
                    <div class="p-2 text-xs">
                        <div class="font-bold text-emerald-800">DAPUR SPPG ASAL</div>
                        <div class="text-slate-700">${data.sppg.name}</div>
                    </div>
                `))
                .addTo(map);

            markers.push(sppgMarker);
            bounds.extend([data.sppg.lng, data.sppg.lat]);
            hasPoints = true;
        }

        // 2. Add School Markers (Numbered 1, 2, 3...)
        if (Array.isArray(data.stops)) {
            data.stops.forEach((stop) => {
                if (!stop.lat || !stop.lng) return;

                const isCurrent = Boolean(stop.is_active || stop.is_current_target);
                const markerEl = document.createElement('div');
                markerEl.className = `flex items-center justify-center w-8 h-8 rounded-full font-bold text-xs shadow-md border-2 border-white cursor-pointer transition-transform ${
                    isCurrent 
                        ? 'bg-amber-600 text-white ring-4 ring-amber-300 scale-110 z-10' 
                        : 'bg-slate-700 text-white ring-2 ring-slate-400'
                }`;
                markerEl.innerText = stop.sequence_number;
                markerEl.title = `Stop #${stop.sequence_number}: ${stop.school_name}`;

                if (typeof onStopSelected === 'function') {
                    markerEl.addEventListener('click', () => onStopSelected(stop));
                }

                const popupHtml = `
                    <div class="p-2 text-xs min-w-[160px]">
                        <div class="flex items-center gap-1 font-bold text-slate-800">
                            <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-slate-200 text-[10px]">${stop.sequence_number}</span>
                            <span>${stop.school_name}</span>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1">${stop.school_address || ''}</div>
                        ${stop.planned_portions ? `<div class="mt-1 font-semibold text-emerald-700">Rencana: ${stop.planned_portions} porsi</div>` : ''}
                        ${stop.loaded_portions ? `<div class="font-semibold text-blue-700">Dimuat: ${stop.loaded_portions} porsi</div>` : ''}
                        <div class="mt-1 text-[10px] uppercase font-bold text-slate-400">${stop.status_label || stop.stage || stop.status}</div>
                    </div>
                `;

                const schoolMarker = new Marker({ element: markerEl })
                    .setLngLat([stop.lng, stop.lat])
                    .setPopup(new Popup({ offset: 12 }).setHTML(popupHtml))
                    .addTo(map);

                markers.push(schoolMarker);
                bounds.extend([stop.lng, stop.lat]);
                hasPoints = true;
            });
        }

        // 3. Add Polyline Route Geometry from OSRM
        if (data.geometry && data.geometry.coordinates) {
            const geojsonSource = {
                type: 'geojson',
                data: {
                    type: 'Feature',
                    properties: {},
                    geometry: data.geometry,
                },
            };

            if (map.getSource('route-line')) {
                map.getSource('route-line').setData(geojsonSource.data);
            } else {
                map.addSource('route-line', geojsonSource);
                map.addLayer({
                    id: 'route-line-layer',
                    type: 'line',
                    source: 'route-line',
                    layout: {
                        'line-join': 'round',
                        'line-cap': 'round',
                    },
                    paint: {
                        'line-color': '#1C6654',
                        'line-width': 5,
                        'line-opacity': 0.85,
                    },
                });

                // Subtle outline
                map.addLayer({
                    id: 'route-line-casing',
                    type: 'line',
                    source: 'route-line',
                    layout: {
                        'line-join': 'round',
                        'line-cap': 'round',
                    },
                    paint: {
                        'line-color': '#FFFFFF',
                        'line-width': 7,
                        'line-opacity': 0.4,
                    },
                }, 'route-line-layer');
            }
        }

        // Fit bounds
        if (hasPoints && !bounds.isEmpty()) {
            map.fitBounds(bounds, {
                padding: { top: 50, bottom: 50, left: 50, right: 50 },
                maxZoom: 15,
                duration: 800,
            });
        }
    }

    map.on('load', () => {
        if (initialData) {
            renderMapData(initialData);
        } else if (dataUrl) {
            fetch(dataUrl, {
                headers: { 'Accept': 'application/json' },
            })
                .then(res => res.json())
                .then(data => renderMapData(data))
                .catch(err => {
                    console.warn('Failed to fetch map data:', err);
                });
        }
    });

    return {
        map,
        updateData: renderMapData,
        destroy: () => {
            if (map) map.remove();
        },
    };
}

window.initDispatchMap = initDispatchMap;
