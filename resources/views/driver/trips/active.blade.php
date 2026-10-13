<x-app-shell title="Perjalanan Aktif : {{ $trip->trip_code }} : MBG-Track">
    <!-- Header Perjalanan Aktif (Page D03) -->
    <header class="mb-6 pb-4 border-b border-[#DCE5DD]">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                    <a href="{{ route('driver.trips.index') }}" class="text-xs font-bold text-[#1C6654] hover:underline flex items-center gap-1 touch-target">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span>Kembali ke Daftar Tugas (D01)</span>
                    </a>
                    <span class="text-xs font-mono text-[#53645B] bg-white border border-[#DCE5DD] px-2 py-0.5 rounded">
                        Trip: {{ $trip->trip_code }}
                    </span>
                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full border {{ $trip->status->badgeClass() }}">
                        Status: {{ $trip->status->label() }}
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">
                    Perjalanan Aktif &amp; Navigasi Multi-Stop
                </h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">
                    Armada: <strong>{{ $trip->vehicle->plate_number }} ({{ $trip->vehicle->vehicle_name }})</strong> : 
                    Dapur SPPG: <strong>{{ $trip->sppg->name }}</strong>
                </p>
            </div>

            <!-- Petunjuk Keamanan Berkendara -->
            <div class="p-3 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] text-[11px] text-[#53645B] max-w-xs">
                <span class="font-bold text-[#172923] block mb-0.5">Keselamatan Kurir:</span>
                Fokus berkendara di jalan raya. Interaksi aplikasi hanya dilakukan saat kendaraan berhenti di tujuan.
            </div>
        </div>
    </header>

    <!-- Notifikasi Flash -->
    @if (session('success'))
        <div class="mb-6 p-4 rounded-2xl bg-[#EDF7F2] border border-[#BCE2D0] text-[#1B573E] text-xs sm:text-sm font-semibold flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 p-4 rounded-2xl bg-[#FDF2F2] border border-[#F5C2C2] text-[#9B3232] text-xs sm:text-sm font-semibold flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Banner Status GPS Telemetri Perangkat -->
    <div id="gps-status-card" class="mb-6 p-4 rounded-2xl bg-white border border-[#DCE5DD] shadow-2xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div id="gps-indicator-dot" class="w-4 h-4 rounded-full bg-amber-400 animate-pulse shrink-0"></div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-[#172923]">Status Telemetri GPS Perangkat:</span>
                        <span id="gps-status-text" class="text-xs font-semibold px-2 py-0.5 rounded border {{ $trackingStatus['badge_class'] }}">
                            {{ $trackingStatus['label'] }}
                        </span>
                    </div>
                    <p id="gps-status-detail" class="text-[11px] text-[#53645B] mt-0.5">
                        {{ $trackingStatus['age_human'] }}
                    </p>
                </div>
            </div>

            <!-- Kontrol Berbagi Lokasi -->
            <div class="flex items-center gap-2">
                <button type="button" id="btn-toggle-gps" 
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-[#1C6654] text-white hover:bg-[#154E40] transition-colors flex items-center gap-1.5 touch-target">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span id="btn-gps-label">Bagikan Lokasi GPS</span>
                </button>
            </div>
        </div>

        <!-- Penjelasan Keterbatasan Browser Mobile -->
        <div class="mt-3 pt-3 border-t border-[#E8EEE9] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-[11px] text-[#708177]">
            <p>
                <strong>Catatan Pelacakan Web:</strong> GPS dikirim berkala saat layar peramban aktif. Jika layar terkunci, pengiriman lokasi terhenti sementara dan penanda akan berada pada titik terakhir (stale).
            </p>
            <span id="gps-accuracy-display" class="font-mono text-xs text-[#1C6654] shrink-0">
                @if($trackingStatus['accuracy_m'])
                    Akurasi: ±{{ round($trackingStatus['accuracy_m']) }}m
                @endif
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Kolom Kiri (7 Kolom): Peta Rute Navigasi Aktif -->
        <div class="lg:col-span-7 space-y-6">
            <div class="p-5 rounded-2xl bg-white border border-[#DCE5DD]">
                <div class="flex items-center justify-between pb-3 border-b border-[#DCE5DD] mb-3">
                    <h2 class="text-sm sm:text-base font-bold text-[#172923]">Peta Perjalanan &amp; Posisi Armada</h2>
                    <span class="text-xs font-mono font-bold text-[#1C6654]">
                        Stop Aktif: #{{ $activeStop?->sequence_number ?? 1 }}
                    </span>
                </div>

                <div id="driver-active-map" 
                     class="w-full h-80 sm:h-96 rounded-xl overflow-hidden border border-[#DCE5DD] bg-slate-100 mb-3">
                </div>

                <div class="flex items-center justify-between text-[11px] text-[#708177]">
                    <span>Dapur: {{ $trip->sppg->name }}</span>
                    <span>Tujuan Berikutnya: {{ $activeStop?->school->name }}</span>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan (5 Kolom): Pemberhentian Aktif & Aksi Tiba -->
        <div class="lg:col-span-5 space-y-6">
            <!-- 1. Card Pemberhentian Aktif Saat Ini -->
            @if ($activeStop)
                <div class="p-5 rounded-2xl bg-[#F0F7F4] border-2 border-[#1C6654] shadow-xs">
                    <div class="flex items-center justify-between pb-3 border-b border-[#BCE2D0] mb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-[#1C6654] text-white text-xs font-bold flex items-center justify-center">
                                {{ $activeStop->sequence_number }}
                            </span>
                            <span class="text-xs font-bold text-[#1C6654] uppercase tracking-wider">Tujuan Utama Aktif</span>
                        </div>
                        <span class="text-xs font-bold px-2 py-0.5 rounded border {{ $activeStop->status->badgeClass() }}">
                            {{ $activeStop->status->label() }}
                        </span>
                    </div>

                    <h3 class="text-base font-bold text-[#172923] mb-1">
                        {{ $activeStop->school->name }}
                    </h3>
                    <p class="text-xs text-[#53645B] mb-3 leading-relaxed">
                        {{ $activeStop->school->address }}
                    </p>

                    <div class="p-3 rounded-xl bg-white border border-[#BCE2D0] text-xs space-y-1 mb-4">
                        <div class="flex justify-between">
                            <span class="text-[#53645B]">Muatan Direncanakan:</span>
                            <span class="font-bold text-[#172923]">{{ $activeStop->planned_portions ?: $activeStop->total_loaded_portions }} Porsi</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#53645B]">Muatan Terverifikasi:</span>
                            <span class="font-bold text-[#1C6654]">{{ $activeStop->loaded_portions ?: $activeStop->total_loaded_portions }} Porsi</span>
                        </div>
                    </div>

                    <!-- Tombol Aksi Tiba di Sekolah -->
                    @if (in_array($activeStop->status, [\App\Enums\TripStopStatus::PENDING, \App\Enums\TripStopStatus::EN_ROUTE], true))
                        <form action="{{ route('driver.trips.stops.arrive', [$trip, $activeStop]) }}" method="POST">
                            @csrf
                            <button type="submit" 
                                    class="w-full py-3 rounded-xl font-bold text-sm bg-[#1C6654] text-white hover:bg-[#154E40] transition-colors shadow-sm flex items-center justify-center gap-2 touch-target">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Tiba di {{ $activeStop->school->name }}</span>
                            </button>
                        </form>
                    @elseif (in_array($activeStop->status, [\App\Enums\TripStopStatus::ARRIVED, \App\Enums\TripStopStatus::ARRIVED_AWAITING_CONFIRMATION], true))
                        <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-medium text-center">
                            <span class="font-bold block mb-0.5">Armada Telah Tiba</span>
                            Menunggu pemeriksaan organoleptik dan konfirmasi serah terima dari pihak PIC Sekolah.
                        </div>
                    @else
                        <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium text-center">
                            <span class="font-bold block mb-0.5">Serah Terima Selesai</span>
                            Pemberhentian ini telah diselesaikan.
                        </div>
                    @endif
                </div>
            @endif

            <!-- 2. Ringkasan Seluruh Pemberhentian -->
            <div class="p-5 rounded-2xl bg-white border border-[#DCE5DD]">
                <h3 class="text-xs font-bold text-[#53645B] uppercase tracking-wider mb-3">
                    Urutan Rute Seluruh Sekolah ({{ $stops->count() }})
                </h3>

                <div class="space-y-2.5">
                    @foreach ($stops as $stop)
                        <div class="p-3 rounded-xl border {{ $activeStop && $activeStop->id === $stop->id ? 'bg-[#F0F7F4] border-[#1C6654]' : 'bg-[#FAFBF9] border-[#DCE5DD]' }} flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2.5">
                                <span class="w-5 h-5 rounded-full {{ $activeStop && $activeStop->id === $stop->id ? 'bg-[#1C6654] text-white' : 'bg-slate-200 text-slate-700' }} text-[10px] font-bold flex items-center justify-center">
                                    {{ $stop->sequence_number }}
                                </span>
                                <div>
                                    <span class="font-bold text-[#172923] block">{{ $stop->school->name }}</span>
                                    <span class="text-[10px] text-[#53645B]">{{ $stop->loaded_portions ?: $stop->planned_portions }} Porsi</span>
                                </div>
                            </div>
                            <span class="text-[10px] px-2 py-0.5 rounded border {{ $stop->status->badgeClass() }}">
                                {{ $stop->status->label() }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Script Geolocation Kurir & Peta Navigasi -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi peta interaktif
            if (window.initDispatchMap) {
                window.initDispatchMap('driver-active-map', {
                    dataUrl: '{{ route('trips.map-data', $trip) }}'
                });
            }

            // Client Geolocation Telemetry Logic
            const telemetryUrl = '{{ route('driver.trips.telemetry', $trip) }}';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const statusText = document.getElementById('gps-status-text');
            const statusDetail = document.getElementById('gps-status-detail');
            const accuracyDisplay = document.getElementById('gps-accuracy-display');
            const btnToggleGps = document.getElementById('btn-toggle-gps');
            const btnGpsLabel = document.getElementById('btn-gps-label');
            const indicatorDot = document.getElementById('gps-indicator-dot');

            let watchId = null;
            let lastSentTime = 0;

            function sendLocation(pos) {
                const now = Date.now();
                // Throttle: Send at most once every 10 seconds to save battery and bandwidth
                if (now - lastSentTime < 10000) return;
                lastSentTime = now;

                const payload = {
                    latitude: pos.coords.latitude,
                    longitude: pos.coords.longitude,
                    accuracy: pos.coords.accuracy,
                    speed: pos.coords.speed,
                    heading: pos.coords.heading,
                    recorded_at: new Date(pos.timestamp).toISOString()
                };

                fetch(telemetryUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        statusText.textContent = 'Terhubung (GPS Aktif)';
                        statusText.className = 'text-xs font-semibold px-2 py-0.5 rounded border bg-emerald-50 text-emerald-700 border-emerald-200';
                        statusDetail.textContent = 'Data lokasi diperbarui: ' + new Date().toLocaleTimeString('id-ID');
                        indicatorDot.className = 'w-4 h-4 rounded-full bg-emerald-500 animate-pulse shrink-0';
                        if (accuracyDisplay) {
                            accuracyDisplay.textContent = 'Akurasi: ±' + Math.round(pos.coords.accuracy) + 'm';
                        }
                    }
                })
                .catch(err => {
                    console.warn('Gagal mengirim koordinat:', err);
                });
            }

            function handleGeoError(err) {
                indicatorDot.className = 'w-4 h-4 rounded-full bg-rose-500 shrink-0';
                statusText.className = 'text-xs font-semibold px-2 py-0.5 rounded border bg-rose-50 text-rose-700 border-rose-200';
                if (err.code === 1) {
                    statusText.textContent = 'Izin Lokasi Ditolak';
                    statusDetail.textContent = 'Izin GPS ditolak oleh browser. Anda tetap dapat melanjutkan pengantaran dan menandai kedatangan secara manual.';
                } else if (err.code === 2) {
                    statusText.textContent = 'Sinyal GPS Tidak Tersedia';
                    statusDetail.textContent = 'Perangkat tidak dapat menentukan posisi. Pastikan GPS aktif dan berada di area terbuka.';
                } else {
                    statusText.textContent = 'Waktu Tunggu GPS Habis';
                    statusDetail.textContent = 'Mencoba kembali membaca satelit lokasi...';
                }
            }

            function startTracking() {
                if (!navigator.geolocation) {
                    statusText.textContent = 'Browser Tidak Mendukung GPS';
                    return;
                }

                btnGpsLabel.textContent = 'Hentikan Berbagi Lokasi';
                btnToggleGps.className = 'px-3.5 py-1.5 rounded-xl text-xs font-bold bg-rose-700 text-white hover:bg-rose-800 transition-colors flex items-center gap-1.5 touch-target';

                watchId = navigator.geolocation.watchPosition(sendLocation, handleGeoError, {
                    enableHighAccuracy: true,
                    maximumAge: 10000,
                    timeout: 20000
                });
            }

            function stopTracking() {
                if (watchId !== null) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                }
                btnGpsLabel.textContent = 'Bagikan Lokasi GPS';
                btnToggleGps.className = 'px-3.5 py-1.5 rounded-xl text-xs font-bold bg-[#1C6654] text-white hover:bg-[#154E40] transition-colors flex items-center gap-1.5 touch-target';
                statusText.textContent = 'Pelacakan Dinonaktifkan Sementara';
                statusText.className = 'text-xs font-semibold px-2 py-0.5 rounded border bg-slate-100 text-slate-700 border-slate-300';
                indicatorDot.className = 'w-4 h-4 rounded-full bg-slate-400 shrink-0';
            }

            btnToggleGps?.addEventListener('click', function() {
                if (watchId !== null) {
                    stopTracking();
                } else {
                    startTracking();
                }
            });

            // Automatically attempt geolocation sharing on load
            startTracking();
        });
    </script>
</x-app-shell>
