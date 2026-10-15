<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Status Pengantaran Makanan Bergizi : {{ $trip->trip_code }} : MBG-Track</title>
    <meta name="description" content="Informasi publik resmi status rute dan jadwal pengantaran Makanan Bergizi Gratis (MBG) terverifikasi.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F5F7F3] text-[#172923] font-sans antialiased min-h-screen flex flex-col justify-between">
    <!-- Top Bar Publik -->
    <header class="bg-white border-b border-[#DCE5DD] sticky top-0 z-30">
        <div class="max-w-5xl mx-auto px-4 py-3.5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-[#1C6654] text-white flex items-center justify-center font-bold text-sm shadow-xs">
                        M
                    </span>
                    <span class="font-bold text-sm tracking-tight text-[#172923]">MBG-Track</span>
                </a>
                <span class="hidden sm:inline-block text-[#DCE5DD]">|</span>
                <span class="hidden sm:inline-block text-xs text-[#53645B] font-medium">Portal Informasi Publik</span>
            </div>

            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#EDF7F2] text-[#1B573E] border border-[#BCE2D0]">
                    <span class="w-2 h-2 rounded-full bg-[#1B573E]"></span>
                    <span>Portal Publik Resmi</span>
                </span>
                <span id="public-live-indicator" class="hidden sm:inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-medium bg-white text-[#53645B] border border-[#DCE5DD]">
                    <span>Pembaruan:</span>
                    <span id="public-last-updated" class="font-mono font-bold text-[#1C6654]">
                        {{ $trip->updated_at ? $trip->updated_at->format('H:i:s') . ' WIB' : 'Sinkron' }}
                    </span>
                </span>
            </div>
        </div>
    </header>

    <!-- Konten Utama -->
    <main class="max-w-5xl mx-auto px-4 py-6 w-full flex-1">
        <!-- Header Rute Publik (Page U03) -->
        <div class="mb-6">
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <span class="text-xs font-mono font-bold text-[#53645B] bg-white border border-[#DCE5DD] px-2.5 py-0.5 rounded-lg">
                    Kode Rute: {{ $trip->trip_code }}
                </span>
                <span class="text-xs font-semibold text-[#172923] bg-white border border-[#DCE5DD] px-2.5 py-0.5 rounded-lg">
                    Dapur: {{ $trip->sppg->name }}
                </span>
                <span class="text-xs font-medium text-[#53645B] bg-white border border-[#DCE5DD] px-2.5 py-0.5 rounded-lg">
                    {{ $trip->service_date->translatedFormat('l, d F Y') }}
                </span>
            </div>

            <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">
                Status Pengantaran Makanan Bergizi Gratis
            </h1>
            <p class="text-xs sm:text-sm text-[#53645B] mt-1 max-w-2xl">
                Jalur distribusi makanan sehat ke sekolah penerima manfaat. Informasi diperbarui secara transparan berdasarkan tahapan operasional dapur SPPG.
            </p>
        </div>

        <!-- Banner Status Operasional Terkini -->
        @php
            $stageText = match ($trip->status) {
                \App\Enums\TripStatus::ASSIGNED,
                \App\Enums\TripStatus::VEHICLE_CHECKED,
                \App\Enums\TripStatus::READY => 'Persiapan di Dapur SPPG (Pengecekan Mutu & Armada Selesai)',
                \App\Enums\TripStatus::IN_PROGRESS => 'Armada Sedang dalam Perjalanan Menuju Sekolah Tujuan',
                \App\Enums\TripStatus::COMPLETED => 'Seluruh Pengantaran Makanan Telah Selesai Diterima',
                \App\Enums\TripStatus::CANCELLED => 'Jadwal Pengantaran Dibatalkan oleh Kepala SPPG',
                default => 'Dalam Proses Operasional',
            };
        @endphp
        <div class="mb-6 p-4 rounded-2xl bg-white border border-[#DCE5DD] shadow-2xs flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] flex items-center justify-center text-[#1C6654] shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <span class="text-[11px] font-semibold text-[#53645B] block uppercase tracking-wider">Tahapan Operasional Terkini:</span>
                    <span id="public-stage-text" class="text-sm font-bold text-[#172923]">{{ $stageText }}</span>
                </div>
            </div>

            <span id="public-trip-status" class="text-xs font-bold px-3 py-1 rounded-full border {{ $trip->status->badgeClass() }} shrink-0">
                {{ $trip->status->label() }}
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Kolom Kiri (7 Kolom): Peta Rute MapLibre GL JS -->
            <div class="lg:col-span-7 space-y-4">
                <div class="p-4 sm:p-5 rounded-2xl bg-white border border-[#DCE5DD]">
                    <div class="flex items-center justify-between pb-3 border-b border-[#DCE5DD] mb-3">
                        <h2 class="text-sm sm:text-base font-bold text-[#172923]">Peta Jalur Pengantaran Sekolah</h2>
                        @if ($trip->total_distance_m)
                            <span class="text-xs font-mono font-bold text-[#1C6654]">
                                Jarak: {{ round($trip->total_distance_m / 1000, 1) }} km
                            </span>
                        @endif
                    </div>

                    <div id="public-delivery-map" 
                         class="w-full h-72 sm:h-96 rounded-xl overflow-hidden border border-[#DCE5DD] bg-slate-100 mb-3">
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-[#708177]">
                        <span>Dapur Asal: {{ $trip->sppg->name }}</span>
                        <span>Pengantaran MBG Terverifikasi</span>
                    </div>
                </div>

                <!-- Prinsip Perlindungan Data Publik -->
                <div class="p-4 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] text-xs text-[#53645B]">
                    <span class="font-bold text-[#172923] block mb-1">Perlindungan Privasi &amp; Keamanan Distribusi:</span>
                    <p class="text-[11px] leading-relaxed">
                        Data publik disanitasi secara ketat sesuai prinsip *data minimization*. Informasi pengemudi, kontak pribadi, nomor polisi armada, rute privat, dan daftar perhentian sekolah lain dilindungi dari penayangan umum.
                    </p>
                </div>
            </div>

            <!-- Kolom Kanan (5 Kolom): Status Sekolah Penerima (Minimal Projection) -->
            <div class="lg:col-span-5 space-y-4">
                <div class="p-4 sm:p-5 rounded-2xl bg-white border border-[#DCE5DD]">
                    <div class="flex items-center justify-between pb-3 border-b border-[#DCE5DD] mb-4">
                        <h2 class="text-sm sm:text-base font-bold text-[#172923]">Status Pengantaran Sekolah</h2>
                        <span class="text-xs font-bold text-[#1C6654] bg-[#EDF7F2] px-2 py-0.5 rounded border border-[#BCE2D0]">
                            {{ $matchedStop ? 'Terverifikasi' : 'Pencarian' }}
                        </span>
                    </div>

                    @if (!empty($hasFilter))
                        @if ($matchedStop)
                            @php
                                $publicStopLabel = match ($matchedStop->status) {
                                    \App\Enums\TripStopStatus::PENDING => 'Menunggu Giliran Pengantaran',
                                    \App\Enums\TripStopStatus::EN_ROUTE => 'Armada Sedang Menuju Sekolah Ini',
                                    \App\Enums\TripStopStatus::ARRIVED => 'Armada Telah Tiba di Sekolah',
                                    \App\Enums\TripStopStatus::ARRIVED_AWAITING_CONFIRMATION => 'Tiba, Menunggu Konfirmasi Sekolah',
                                    \App\Enums\TripStopStatus::HANDED_OVER => 'Makanan Telah Diserahkan',
                                    \App\Enums\TripStopStatus::COMPLETED => 'Pengantaran Selesai',
                                    \App\Enums\TripStopStatus::SKIPPED => 'Dilewati',
                                    \App\Enums\TripStopStatus::EXCEPTION => 'Terdapat Kendala Pengantaran',
                                    default => 'Status Belum Diketahui',
                                };
                            @endphp
                            <div class="p-4 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD]">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <span class="text-sm font-bold text-[#172923] block">{{ $matchedStop->school->name }}</span>
                                        <span class="text-xs text-[#53645B]">{{ $matchedStop->school->education_level }}</span>
                                    </div>
                                    <span class="text-[10px] px-2 py-0.5 rounded border font-semibold {{ $matchedStop->status->badgeClass() }}">
                                        {{ $matchedStop->status->label() }}
                                    </span>
                                </div>
                                <div class="mt-3 pt-3 border-t border-[#E8EEE9] flex justify-between text-xs">
                                    <span class="text-[#708177]">Status Pengantaran:</span>
                                    <span class="font-bold text-[#172923]">{{ $publicStopLabel }}</span>
                                </div>
                            </div>
                        @else
                            <div class="p-4 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] text-center">
                                <div class="w-10 h-10 rounded-full bg-[#EDF7F2] text-[#1C6654] mx-auto flex items-center justify-center mb-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <p class="text-xs font-medium text-[#53645B] leading-relaxed">
                                    Sekolah tidak ditemukan pada jadwal pengantaran rute ini. Pastikan kode atau tautan sekolah sesuai.
                                </p>
                            </div>
                        @endif
                    @else
                        <div class="p-4 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] text-center">
                            <div class="w-10 h-10 rounded-full bg-[#EDF7F2] text-[#1C6654] mx-auto flex items-center justify-center mb-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <h3 class="text-xs font-bold text-[#172923] mb-1">Informasi Perhentian Dilindungi</h3>
                            <p class="text-xs font-medium text-[#53645B] leading-relaxed">
                                Demi perlindungan privasi dan keamanan distribusi, daftar perhentian tidak dipublikasikan secara terbuka. Silakan akses pemantauan status melalui portal resmi sekolah penerima manfaat Anda.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>

    <!-- Footer Publik -->
    <footer class="bg-white border-t border-[#DCE5DD] py-4 mt-8">
        <div class="max-w-5xl mx-auto px-4 text-center text-xs text-[#708177]">
            &copy; {{ date('Y') }} MBG-Track : Sistem Keterlacakan &amp; Integritas Makanan Bergizi Gratis. Portal Informasi Publik Terbuka.
        </div>
    </footer>

    <!-- Script Inisialisasi Peta Publik -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.initDispatchMap) {
                window.initDispatchMap('public-delivery-map', {
                    dataUrl: '{{ route('public.trips.map-data', array_merge(['tripCode' => $trip->trip_code], request()->only(['school', 'school_slug', 'school_id', 'filter']))) }}',
                    pollInterval: 15000,
                    onTelemetryUpdate: function(data) {
                        if (!data) return;

                        if (data.last_updated) {
                            const dt = new Date(data.last_updated);
                            const timeStr = dt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
                            const lastUpdatedEl = document.getElementById('public-last-updated');
                            if (lastUpdatedEl) lastUpdatedEl.textContent = timeStr;
                        }

                        if (data.delivery_stage) {
                            const stageEl = document.getElementById('public-stage-text');
                            if (stageEl) stageEl.textContent = data.delivery_stage;
                        }

                        if (data.status_label) {
                            const statusEl = document.getElementById('public-trip-status');
                            if (statusEl) statusEl.textContent = data.status_label;
                        }

                        if (Array.isArray(data.stops)) {
                            data.stops.forEach(function(s) {
                                const stageEl = document.getElementById('stop-stage-text-' + s.sequence_number);
                                if (stageEl && s.stage) stageEl.textContent = s.stage;

                                const badgeEl = document.getElementById('stop-badge-' + s.sequence_number);
                                if (badgeEl && s.status_label) badgeEl.textContent = s.status_label;
                            });
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
