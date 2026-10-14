<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Isolated Map Test : MBG-Track</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        #test-map {
            width: 100%;
            height: 100%;
            min-height: 500px;
        }
    </style>
</head>
<body class="h-full flex flex-col font-sans text-slate-800">
    <header class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between shadow-xs">
        <div>
            <h1 class="text-lg font-bold text-[#1C6654]">MBG-Track Isolated Basemap Diagnostic</h1>
            <p class="text-xs text-slate-500">Center Surabaya [112.735, -7.288] • Style: OpenFreeMap Liberty</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-slate-600">Zoom:</span>
            <button id="btn-z12" class="px-2.5 py-1 text-xs font-medium bg-slate-100 hover:bg-slate-200 rounded border border-slate-300">12 (Kota)</button>
            <button id="btn-z14" class="px-2.5 py-1 text-xs font-medium bg-slate-100 hover:bg-slate-200 rounded border border-slate-300">14 (Kecamatan)</button>
            <button id="btn-z16" class="px-2.5 py-1 text-xs font-medium bg-slate-100 hover:bg-slate-200 rounded border border-slate-300">16 (Jalan Lokal)</button>
            <button id="btn-z18" class="px-2.5 py-1 text-xs font-medium bg-slate-100 hover:bg-slate-200 rounded border border-slate-300">18 (Detail Bangunan)</button>
            <button id="btn-reset" class="px-3 py-1 text-xs font-medium bg-[#1C6654] text-white rounded hover:bg-[#164D42]">Reset Center</button>
        </div>
    </header>

    <div class="flex-1 flex flex-col lg:flex-row min-h-0">
        <!-- Map Canvas -->
        <div class="flex-1 relative bg-slate-200">
            <div id="test-map"></div>
            <div id="map-status-overlay" class="absolute bottom-3 left-3 bg-white/95 backdrop-blur-xs p-3 rounded-lg border border-slate-300 shadow-sm text-xs z-10 space-y-1 font-mono max-w-sm">
                <div class="font-bold text-slate-700">Status Diagnostik:</div>
                <div id="diag-webgl">WebGL: Checking...</div>
                <div id="diag-style">Style: Loading...</div>
                <div id="diag-layers">Layers: 0</div>
                <div id="diag-center">Center: -</div>
                <div id="diag-zoom">Zoom: -</div>
            </div>
        </div>

        <!-- Diagnostic Log Console -->
        <aside class="w-full lg:w-96 bg-white border-t lg:border-t-0 lg:border-l border-slate-200 flex flex-col h-64 lg:h-auto">
            <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wide">Event & Network Log</span>
                <span id="log-counter" class="text-[10px] font-mono bg-slate-200 text-slate-700 px-1.5 py-0.5 rounded">0 events</span>
            </div>
            <div id="event-logs" class="flex-1 p-3 font-mono text-[11px] overflow-y-auto space-y-1 bg-slate-900 text-emerald-400">
            </div>
        </aside>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const logContainer = document.getElementById('event-logs');
            const counterEl = document.getElementById('log-counter');
            let eventCount = 0;

            function addLog(type, msg, isError = false) {
                eventCount++;
                counterEl.textContent = `${eventCount} events`;
                const div = document.createElement('div');
                div.className = isError ? 'text-red-400' : (type === 'WARN' ? 'text-amber-300' : 'text-emerald-300');
                const time = new Date().toISOString().substring(11, 19);
                div.textContent = `[${time}] [${type}] ${msg}`;
                logContainer.appendChild(div);
                logContainer.scrollTop = logContainer.scrollHeight;
            }

            // 1. Check WebGL
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            const diagWebgl = document.getElementById('diag-webgl');
            if (gl) {
                diagWebgl.textContent = 'WebGL: Didukung (' + gl.getParameter(gl.RENDERER) + ')';
                diagWebgl.className = 'text-emerald-700 font-semibold';
                addLog('INIT', 'WebGL renderer terdeteksi: ' + gl.getParameter(gl.RENDERER));
            } else {
                diagWebgl.textContent = 'WebGL: TIDAK DIDUKUNG';
                diagWebgl.className = 'text-red-600 font-bold';
                addLog('ERROR', 'WebGL context gagal dibuat', true);
            }

            function startMap() {
                if (!window.maplibregl || !window.maplibregl.Map) {
                    addLog('WAIT', 'Menunggu bundel MapLibre GL termuat...');
                    setTimeout(startMap, 100);
                    return;
                }

                const { Map, NavigationControl, Marker, AttributionControl } = window.maplibregl;
                const centerSurabaya = [112.735, -7.288];
                const styleUrl = 'https://tiles.openfreemap.org/styles/liberty';

                addLog('INIT', 'Memulai MapLibre GL dengan style: ' + styleUrl);

                try {
                    const map = new Map({
                        container: 'test-map',
                        style: styleUrl,
                        center: centerSurabaya,
                        zoom: 12,
                        attributionControl: false
                    });

                    window.__testMap = map;

                    if (AttributionControl) {
                        const attrib = new AttributionControl({ compact: true });
                        map.addControl(attrib, 'bottom-right');
                        if (attrib._container) {
                            attrib._container.classList.add('maplibregl-compact');
                            attrib._container.classList.remove('maplibregl-compact-show');
                        }
                    }

                    map.addControl(new NavigationControl({ showCompass: true }), 'top-right');

            // Add sample test marker at center
            new Marker({ color: '#1C6654' })
                .setLngLat(centerSurabaya)
                .addTo(map);

            map.on('error', (e) => {
                const errDetail = e && e.error ? (e.error.message || JSON.stringify(e.error)) : 'Unknown map error';
                addLog('ERROR', 'Map Error: ' + errDetail, true);
            });

            map.on('style.error', (e) => {
                addLog('ERROR', 'Style Error: ' + (e.error ? e.error.message : 'gagal memuat style'), true);
            });

            map.on('data', (e) => {
                if (e.dataType === 'style') {
                    addLog('DATA', 'Style data event dipicu');
                }
            });

            map.on('load', () => {
                addLog('SUCCESS', 'Map load event selesai!');
                document.getElementById('diag-style').textContent = 'Style: Loaded (Liberty)';
                document.getElementById('diag-style').className = 'text-emerald-700 font-semibold';
                
                const layers = map.getStyle().layers || [];
                document.getElementById('diag-layers').textContent = 'Layers: ' + layers.length + ' layers aktif';
                addLog('INFO', 'Total layers di style: ' + layers.length);

                // Check key layers
                const roadLayers = layers.filter(l => l.id.includes('road') || l.id.includes('highway') || l.id.includes('street'));
                const buildingLayers = layers.filter(l => l.id.includes('building'));
                const labelLayers = layers.filter(l => l.id.includes('label') || l.id.includes('name'));
                addLog('INFO', `Layers breakdown: ${roadLayers.length} road layers, ${buildingLayers.length} building layers, ${labelLayers.length} label layers`);
            });

            map.on('move', () => {
                const c = map.getCenter();
                const z = map.getZoom();
                document.getElementById('diag-center').textContent = `Center: ${c.lng.toFixed(4)}, ${c.lat.toFixed(4)}`;
                document.getElementById('diag-zoom').textContent = `Zoom: ${z.toFixed(2)}`;
            });

            // Zoom button handlers
            document.getElementById('btn-z12').addEventListener('click', () => {
                map.flyTo({ zoom: 12, center: centerSurabaya });
                addLog('NAV', 'Navigasi ke Zoom 12');
            });
            document.getElementById('btn-z14').addEventListener('click', () => {
                map.flyTo({ zoom: 14, center: centerSurabaya });
                addLog('NAV', 'Navigasi ke Zoom 14');
            });
            document.getElementById('btn-z16').addEventListener('click', () => {
                map.flyTo({ zoom: 16, center: centerSurabaya });
                addLog('NAV', 'Navigasi ke Zoom 16');
            });
            document.getElementById('btn-z18').addEventListener('click', () => {
                map.flyTo({ zoom: 18, center: centerSurabaya });
                addLog('NAV', 'Navigasi ke Zoom 18');
            });
            document.getElementById('btn-reset').addEventListener('click', () => {
                map.flyTo({ zoom: 12, center: centerSurabaya });
                addLog('NAV', 'Reset center ke Surabaya [112.735, -7.288]');
            });

                } catch (err) {
                    addLog('FATAL', 'Map constructor threw exception: ' + err.message, true);
                }
            }

            startMap();
        });
    </script>
</body>
</html>
