import { Map as MapLibreMap, Marker, Popup, NavigationControl, AttributionControl, LngLatBounds, setWorkerUrl } from 'maplibre-gl';
import maplibreWorkerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?url';
import 'maplibre-gl/dist/maplibre-gl.css';

// Configure MapLibre Web Worker explicitly for Vite bundle
if (typeof setWorkerUrl === 'function' && maplibreWorkerUrl) {
    setWorkerUrl(maplibreWorkerUrl);
}

function isWebGlSupported() {
    try {
        const canvas = document.createElement('canvas');
        return Boolean(window.WebGLRenderingContext && (canvas.getContext('webgl') || canvas.getContext('experimental-webgl')));
    } catch (e) {
        return false;
    }
}

/**
 * Creates safe DOM popup node for a school stop without innerHTML interpolation.
 */
function createSchoolPopupContent(stop) {
    const popupNode = document.createElement('div');
    popupNode.className = 'p-2 text-xs min-w-[160px]';

    const headerRow = document.createElement('div');
    headerRow.className = 'flex items-center gap-1 font-bold text-slate-800';

    const seqBadge = document.createElement('span');
    seqBadge.className = 'inline-flex items-center justify-center w-4 h-4 rounded-full bg-slate-200 text-[10px]';
    seqBadge.textContent = String(stop.sequence_number);

    const nameSpan = document.createElement('span');
    nameSpan.className = 'truncate max-w-[180px]';
    nameSpan.textContent = stop.school_name || '';

    headerRow.appendChild(seqBadge);
    headerRow.appendChild(nameSpan);
    popupNode.appendChild(headerRow);

    if (stop.school_address) {
        const addrDiv = document.createElement('div');
        addrDiv.className = 'text-[11px] text-slate-500 mt-1';
        addrDiv.textContent = stop.school_address;
        popupNode.appendChild(addrDiv);
    }

    if (stop.planned_portions) {
        const planDiv = document.createElement('div');
        planDiv.className = 'mt-1 font-semibold text-emerald-700';
        planDiv.textContent = `Rencana: ${stop.planned_portions} porsi`;
        popupNode.appendChild(planDiv);
    }

    if (stop.loaded_portions) {
        const loadDiv = document.createElement('div');
        loadDiv.className = 'font-semibold text-blue-700';
        loadDiv.textContent = `Dimuat: ${stop.loaded_portions} porsi`;
        popupNode.appendChild(loadDiv);
    }

    const statusDiv = document.createElement('div');
    statusDiv.className = 'mt-1 text-[10px] uppercase font-bold text-slate-400';
    statusDiv.textContent = stop.status_label || stop.stage || stop.status || '';
    popupNode.appendChild(statusDiv);

    return popupNode;
}

/**
 * Creates safe DOM popup node for driver telemetry without innerHTML interpolation.
 */
function createDriverPopupContent(tel) {
    const driverPopupNode = document.createElement('div');
    driverPopupNode.className = 'p-2 text-xs min-w-[160px]';

    const dHeader = document.createElement('div');
    dHeader.className = 'font-bold text-emerald-800 flex items-center gap-1.5';
    dHeader.textContent = 'POSISI ARMADA KURIR';
    driverPopupNode.appendChild(dHeader);

    const dStatus = document.createElement('div');
    dStatus.className = 'mt-1 text-[11px] font-semibold text-slate-700';
    dStatus.textContent = tel.label || 'Aktif';
    driverPopupNode.appendChild(dStatus);

    if (tel.accuracy_m) {
        const dAcc = document.createElement('div');
        dAcc.className = 'text-[10px] text-slate-500 mt-0.5 font-mono';
        dAcc.textContent = `Ketidakpastian akurasi: ±${Math.round(tel.accuracy_m)}m`;
        driverPopupNode.appendChild(dAcc);
    }

    if (tel.age_human) {
        const dAge = document.createElement('div');
        dAge.className = 'text-[10px] text-slate-400 mt-0.5';
        dAge.textContent = `Pembaruan terakhir: ${tel.age_human}`;
        driverPopupNode.appendChild(dAge);
    }

    return driverPopupNode;
}

/**
 * MBG-Track Smart Dispatch Map Component (MapLibre GL JS + OpenFreeMap)
 * Uses MapLibreMap for geographic rendering and native JavaScript Map for keyed collections.
 * Supports smooth keyed marker lifecycle, retained popup states, and graceful fallback indication.
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
        pollInterval = 0,
        onTelemetryUpdate = null,
    } = options;

    let map = null;
    let sppgMarker = null;
    // Native ES6 JavaScript Map for keyed school markers collection (avoids constructor collision)
    const schoolMarkers = new Map();
    let driverMarker = null;
    let driverElement = null;
    let driverPopup = null;
    let lastDriverCoords = null;
    let driverAnimFrameId = null;
    let isInitialRender = true;
    let pollTimer = null;

    const defaultCenter = [112.735, -7.288]; // Surabaya default center
    const defaultZoom = 12;

    try {
        map = new MapLibreMap({
            container: containerId,
            style: 'https://tiles.openfreemap.org/styles/liberty',
            center: defaultCenter,
            zoom: defaultZoom,
            interactive: interactive,
            attributionControl: false,
        });

        // Compact Attribution Control: collapsed by default (Gambar 2: only circular (i) icon),
        // expands to full attribution (Gambar 1) only when clicked/tapped.
        const attribControl = new AttributionControl({
            compact: true,
        });
        map.addControl(attribControl, 'bottom-right');

        let userToggledAttribution = false;
        if (attribControl._compactButton) {
            attribControl._compactButton.addEventListener('click', () => {
                userToggledAttribution = true;
            });
        }

        const ensureCollapsedAttribution = () => {
            if (userToggledAttribution) return;
            if (attribControl._container) {
                attribControl._container.classList.add('maplibregl-compact');
                attribControl._container.classList.remove('maplibregl-compact-show');
            }
        };

        ensureCollapsedAttribution();
        map.on('styledata', ensureCollapsedAttribution);
        map.once('load', ensureCollapsedAttribution);

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

        const bounds = new LngLatBounds();
        let hasPoints = false;

        // 1. Keyed SPPG Marker (Created once, updated without DOM recreation)
        if (data.sppg && data.sppg.lat && data.sppg.lng) {
            const sppgCoords = [Number(data.sppg.lng), Number(data.sppg.lat)];
            if (!sppgMarker) {
                const sppgEl = document.createElement('div');
                sppgEl.className = 'flex items-center justify-center w-8 h-8 rounded-full bg-emerald-700 text-white font-bold text-xs shadow-md border-2 border-white ring-2 ring-emerald-500 cursor-pointer';
                sppgEl.innerHTML = `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>`;
                sppgEl.title = `Dapur SPPG: ${data.sppg.name || 'SPPG'}`;

                const sppgPopupContent = document.createElement('div');
                sppgPopupContent.className = 'p-2 text-xs';
                const sppgHeader = document.createElement('div');
                sppgHeader.className = 'font-bold text-emerald-800';
                sppgHeader.textContent = 'DAPUR SPPG ASAL';
                const sppgName = document.createElement('div');
                sppgName.className = 'text-slate-700 mt-0.5';
                sppgName.textContent = data.sppg.name || '';
                sppgPopupContent.appendChild(sppgHeader);
                sppgPopupContent.appendChild(sppgName);

                sppgMarker = new Marker({ element: sppgEl })
                    .setLngLat(sppgCoords)
                    .setPopup(new Popup({ offset: 12 }).setDOMContent(sppgPopupContent))
                    .addTo(map);
            } else {
                sppgMarker.setLngLat(sppgCoords);
            }
            bounds.extend(sppgCoords);
            hasPoints = true;
        }

        // 2. Keyed School Markers (Preserved across updates to maintain popup states)
        if (Array.isArray(data.stops)) {
            const currentStopKeys = new Set();

            data.stops.forEach((stop) => {
                if (!stop.lat || !stop.lng) return;
                const stopKey = String(stop.id || stop.sequence_number);
                currentStopKeys.add(stopKey);
                const stopCoords = [Number(stop.lng), Number(stop.lat)];

                const isCurrent = Boolean(stop.is_active || stop.is_current_target);
                const isCompleted = ['COMPLETED', 'HANDED_OVER'].includes(stop.status);

                let markerRecord = schoolMarkers.get(stopKey);

                if (!markerRecord) {
                    const markerEl = document.createElement('div');
                    markerEl.title = `Stop #${stop.sequence_number}: ${stop.school_name || ''}`;

                    if (typeof onStopSelected === 'function') {
                        markerEl.addEventListener('click', () => onStopSelected(stop));
                    }

                    const popupNode = createSchoolPopupContent(stop);
                    const popup = new Popup({ offset: 12 }).setDOMContent(popupNode);

                    const marker = new Marker({ element: markerEl })
                        .setLngLat(stopCoords)
                        .setPopup(popup)
                        .addTo(map);

                    markerRecord = { marker, popup, element: markerEl, data: stop };
                    schoolMarkers.set(stopKey, markerRecord);
                } else {
                    markerRecord.marker.setLngLat(stopCoords);
                    const newPopupNode = createSchoolPopupContent(stop);
                    markerRecord.popup.setDOMContent(newPopupNode);
                    markerRecord.data = stop;
                }

                // Update styling based on stop progression
                if (isCompleted) {
                    markerRecord.element.className = 'flex items-center justify-center w-8 h-8 rounded-full font-bold text-xs shadow-md border-2 border-white bg-emerald-600 text-white ring-2 ring-emerald-300 cursor-pointer';
                    markerRecord.element.textContent = '✓';
                } else if (isCurrent) {
                    markerRecord.element.className = 'flex items-center justify-center w-8 h-8 rounded-full font-bold text-xs shadow-md border-2 border-white bg-amber-600 text-white ring-4 ring-amber-300 scale-110 z-10 cursor-pointer animate-pulse';
                    markerRecord.element.textContent = String(stop.sequence_number);
                } else {
                    markerRecord.element.className = 'flex items-center justify-center w-8 h-8 rounded-full font-bold text-xs shadow-md border-2 border-white bg-slate-700 text-white ring-2 ring-slate-400 cursor-pointer transition-transform';
                    markerRecord.element.textContent = String(stop.sequence_number);
                }

                bounds.extend(stopCoords);
                hasPoints = true;
            });

            // Clean up removed stops
            schoolMarkers.forEach((rec, key) => {
                if (!currentStopKeys.has(key)) {
                    rec.marker.remove();
                    schoolMarkers.delete(key);
                }
            });
        }

        // 2b. Keyed Driver Real Telemetry Marker
        if (data.driver_telemetry && data.driver_telemetry.latitude && data.driver_telemetry.longitude) {
            const tel = data.driver_telemetry;
            const newCoords = [Number(tel.longitude), Number(tel.latitude)];

            let bgClass = 'bg-slate-700 text-white';
            let ringClass = 'ring-2 ring-slate-400';
            if (tel.is_online) {
                bgClass = 'bg-emerald-600 text-white animate-pulse';
                ringClass = 'ring-4 ring-emerald-300';
            } else if (tel.is_stale) {
                bgClass = 'bg-amber-600 text-white';
                ringClass = 'ring-2 ring-amber-300';
            } else if (tel.is_stopped) {
                bgClass = 'bg-slate-500 text-slate-200';
                ringClass = 'ring-2 ring-slate-300';
            }

            if (!driverMarker) {
                driverElement = document.createElement('div');
                driverElement.className = `flex items-center justify-center w-8 h-8 rounded-full font-bold text-xs shadow-lg border-2 border-white ${bgClass} ${ringClass} cursor-pointer z-20`;
                driverElement.innerHTML = `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>`;
                driverElement.title = `Kurir: ${tel.label || 'Posisi Armada'}`;

                const driverPopupNode = createDriverPopupContent(tel);
                driverPopup = new Popup({ offset: 12 }).setDOMContent(driverPopupNode);

                driverMarker = new Marker({ element: driverElement })
                    .setLngLat(newCoords)
                    .setPopup(driverPopup)
                    .addTo(map);

                lastDriverCoords = newCoords;
            } else {
                driverElement.className = `flex items-center justify-center w-8 h-8 rounded-full font-bold text-xs shadow-lg border-2 border-white ${bgClass} ${ringClass} cursor-pointer z-20`;
                driverElement.title = `Kurir: ${tel.label || 'Posisi Armada'}`;

                // Smooth position update without DOM recreation
                if (!lastDriverCoords || (lastDriverCoords[0] === newCoords[0] && lastDriverCoords[1] === newCoords[1])) {
                    // No movement
                } else {
                    const startLng = lastDriverCoords[0];
                    const startLat = lastDriverCoords[1];
                    const targetLng = newCoords[0];
                    const targetLat = newCoords[1];
                    lastDriverCoords = newCoords;

                    const prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    if (prefersReduced) {
                        driverMarker.setLngLat(newCoords);
                    } else {
                        // Frame-paced visual interpolation
                        const duration = 400;
                        const startTime = performance.now();

                        if (driverAnimFrameId) {
                            cancelAnimationFrame(driverAnimFrameId);
                            driverAnimFrameId = null;
                        }

                        function step(currentTime) {
                            const elapsed = currentTime - startTime;
                            const progress = Math.min(elapsed / duration, 1);
                            const ease = 1 - (1 - progress) * (1 - progress);
                            const curLng = startLng + (targetLng - startLng) * ease;
                            const curLat = startLat + (targetLat - startLat) * ease;
                            driverMarker.setLngLat([curLng, curLat]);

                            if (progress < 1) {
                                driverAnimFrameId = requestAnimationFrame(step);
                            } else {
                                driverAnimFrameId = null;
                            }
                        }

                        driverAnimFrameId = requestAnimationFrame(step);
                    }
                }

                // Update popup text while keeping it open if user opened it
                const newDriverPopupNode = createDriverPopupContent(tel);
                driverPopup.setDOMContent(newDriverPopupNode);
            }

            bounds.extend(newCoords);
            hasPoints = true;
        } else if (driverMarker) {
            if (driverAnimFrameId) {
                cancelAnimationFrame(driverAnimFrameId);
                driverAnimFrameId = null;
            }
            driverMarker.remove();
            driverMarker = null;
            driverElement = null;
            driverPopup = null;
            lastDriverCoords = null;
        }

        // 3. Add Hybrid Multi-Stop Polyline Route Geometry (OSRM or Fallback Reference)
        let geometryToRender = (data.geometry && data.geometry.coordinates) ? data.geometry : null;
        let isFallbackGeometry = false;

        // Fallback: If OSRM is offline or null, construct sequenced dashed polyline
        if (!geometryToRender && data.sppg && Array.isArray(data.stops) && data.stops.length > 0) {
            const fallbackCoords = [];
            if (data.sppg.lng && data.sppg.lat) {
                fallbackCoords.push([Number(data.sppg.lng), Number(data.sppg.lat)]);
            }
            data.stops.forEach(s => {
                if (s.lng && s.lat) fallbackCoords.push([Number(s.lng), Number(s.lat)]);
            });
            if (fallbackCoords.length >= 2) {
                geometryToRender = {
                    type: 'LineString',
                    coordinates: fallbackCoords,
                };
                isFallbackGeometry = true;
            }
        }

        if (geometryToRender && geometryToRender.coordinates) {
            let activeLegIndex = 0;
            if (Array.isArray(data.stops)) {
                const activeStop = data.stops.find(s => Boolean(s.is_active || s.is_current_target));
                if (activeStop && activeStop.sequence_number) {
                    activeLegIndex = Math.max(0, activeStop.sequence_number - 1);
                }
            }

            let activeLegGeometry = null;
            if (Array.isArray(data.legs) && data.legs.length > 0) {
                const activeLeg = data.legs[activeLegIndex] || data.legs[0];
                if (activeLeg && activeLeg.geometry) {
                    activeLegGeometry = activeLeg.geometry;
                }
            }

            const previewGeoJson = {
                type: 'Feature',
                properties: {
                    isFallback: isFallbackGeometry,
                },
                geometry: geometryToRender,
            };

            if (map.getSource('route-preview-line')) {
                map.getSource('route-preview-line').setData(previewGeoJson);
            } else {
                map.addSource('route-preview-line', {
                    type: 'geojson',
                    data: previewGeoJson,
                });

                map.addLayer({
                    id: 'route-preview-layer',
                    type: 'line',
                    source: 'route-preview-line',
                    layout: {
                        'line-join': 'round',
                        'line-cap': 'round',
                    },
                    paint: {
                        'line-color': isFallbackGeometry ? '#B45309' : '#94A3B8',
                        'line-width': isFallbackGeometry ? 3 : 4,
                        'line-opacity': 0.7,
                        'line-dasharray': [3, 3],
                    },
                });
            }

            // Prominent active leg line (Forest Pine #1C6654) - only when detailed road geometry exists
            if (activeLegGeometry && !isFallbackGeometry) {
                const activeLineData = {
                    type: 'Feature',
                    properties: {},
                    geometry: activeLegGeometry,
                };

                if (map.getSource('route-active-line')) {
                    map.getSource('route-active-line').setData(activeLineData);
                } else {
                    map.addSource('route-active-line', {
                        type: 'geojson',
                        data: activeLineData,
                    });

                    map.addLayer({
                        id: 'route-active-casing',
                        type: 'line',
                        source: 'route-active-line',
                        layout: {
                            'line-join': 'round',
                            'line-cap': 'round',
                        },
                        paint: {
                            'line-color': '#FFFFFF',
                            'line-width': 8,
                            'line-opacity': 0.5,
                        },
                    });

                    map.addLayer({
                        id: 'route-active-layer',
                        type: 'line',
                        source: 'route-active-line',
                        layout: {
                            'line-join': 'round',
                            'line-cap': 'round',
                        },
                        paint: {
                            'line-color': '#1C6654',
                            'line-width': 6,
                            'line-opacity': 0.95,
                        },
                    });
                }
            } else if (map.getSource('route-active-line')) {
                // Clear active leg if in fallback mode
                map.getSource('route-active-line').setData({
                    type: 'FeatureCollection',
                    features: [],
                });
            }
        }

        // Fit bounds ONLY on initial render to preserve user pan/zoom during live polling
        if (hasPoints && !bounds.isEmpty() && isInitialRender) {
            map.fitBounds(bounds, {
                padding: { top: 50, bottom: 50, left: 50, right: 50 },
                maxZoom: 15,
                duration: 800,
            });
            isInitialRender = false;
        }
    }

    function fetchAndRender() {
        if (!dataUrl || document.hidden) return;
        fetch(dataUrl, {
            headers: { 'Accept': 'application/json' },
        })
            .then(res => res.json())
            .then(data => {
                renderMapData(data);
                if (typeof onTelemetryUpdate === 'function') {
                    onTelemetryUpdate(data);
                }
            })
            .catch(err => {
                console.warn('Map polling update failed:', err);
            });
    }

    function startPolling(intervalMs) {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
        const ms = intervalMs || pollInterval || 10000;
        pollTimer = setInterval(fetchAndRender, ms);
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    map.on('load', () => {
        map.resize();

        if (initialData) {
            renderMapData(initialData);
        } else if (dataUrl) {
            fetch(dataUrl, {
                headers: { 'Accept': 'application/json' },
            })
                .then(res => res.json())
                .then(data => {
                    renderMapData(data);
                    if (typeof onTelemetryUpdate === 'function') {
                        onTelemetryUpdate(data);
                    }
                })
                .catch(err => {
                    console.warn('Failed to fetch initial map data:', err);
                });
        }

        if (pollInterval && pollInterval > 0) {
            startPolling(pollInterval);
        }
    });

    const handleVisibilityChange = () => {
        if (!document.hidden && pollInterval && pollInterval > 0) {
            fetchAndRender();
        }
    };
    document.addEventListener('visibilitychange', handleVisibilityChange);

    return {
        map,
        updateData: renderMapData,
        startPolling,
        stopPolling,
        destroy: () => {
            stopPolling();
            document.removeEventListener('visibilitychange', handleVisibilityChange);
            if (driverAnimFrameId) {
                cancelAnimationFrame(driverAnimFrameId);
                driverAnimFrameId = null;
            }
            if (sppgMarker) {
                sppgMarker.remove();
                sppgMarker = null;
            }
            schoolMarkers.forEach(rec => rec.marker.remove());
            schoolMarkers.clear();
            if (driverMarker) {
                driverMarker.remove();
                driverMarker = null;
            }
            if (map) map.remove();
        },
    };
}

window.initDispatchMap = initDispatchMap;
window.maplibregl = { Map: MapLibreMap, Marker, Popup, NavigationControl, AttributionControl, LngLatBounds };
