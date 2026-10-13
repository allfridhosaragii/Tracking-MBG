<x-app-shell title="Detail Perjalanan &amp; Rute : {{ $trip->trip_code }} : MBG-Track">
    <!-- Header Trip Detail (Page C03) -->
    <header class="mb-6 pb-4 border-b border-[#DCE5DD]">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                    <x-role-badge :role="auth()->user()->role" />
                    <span class="text-xs font-mono text-[#53645B] bg-white border border-[#DCE5DD] px-2 py-0.5 rounded">
                        SPPG: {{ $trip->sppg?->code ?? 'SPPG-'.$trip->sppg_id }}
                    </span>
                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full border {{ $trip->status->badgeClass() }}">
                        Status: {{ $trip->status->label() }}
                    </span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded border bg-[#EDF2ED] text-[#172923] border-[#DCE5DD]">
                        {{ $trip->trip_type?->label() ?? 'Pengantaran' }}
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">
                    Rute Perjalanan &amp; Pemberhentian: {{ $trip->trip_code }}
                </h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">
                    Tanggal Layanan: <strong>{{ $trip->service_date->translatedFormat('l, d F Y') }}</strong> : Armada: <strong>{{ $trip->vehicle->plate_number }} ({{ $trip->vehicle->vehicle_type }})</strong>
                </p>
            </div>

            <!-- Tombol Navigasi Kontekstual Berdasarkan Role -->
            <div class="flex items-center gap-2">
                @if (auth()->user()->role === \App\Enums\UserRole::KEPALA_SPPG)
                    <a href="{{ route('head.distribution.index', ['service_date' => $trip->service_date->toDateString()]) }}" 
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-[#DCE5DD] text-xs font-semibold text-[#172923] hover:bg-[#FAFBF9] transition-colors touch-target">
                        <svg class="w-4 h-4 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>Kontrol Distribusi (K05)</span>
                    </a>
                @elseif (auth()->user()->role === \App\Enums\UserRole::KURIR)
                    @if (in_array($trip->status->value, ['ASSIGNED', 'VEHICLE_CHECKED', 'READY']))
                        <a href="{{ route('driver.trips.prepare', $trip) }}" 
                           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-[#1C6654] text-white text-xs font-bold rounded-xl shadow-xs transition-colors touch-target">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                            <span>Buka Persiapan Trip (D02)</span>
                        </a>
                    @else
                        <a href="{{ route('driver.trips.index') }}" 
                           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-[#DCE5DD] text-xs font-semibold text-[#172923] hover:bg-[#FAFBF9] transition-colors touch-target">
                            <svg class="w-4 h-4 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            <span>Daftar Tugas (D01)</span>
                        </a>
                    @endif
                @elseif (auth()->user()->role === \App\Enums\UserRole::PIC_SEKOLAH)
                    <a href="{{ route('school.dashboard') }}" 
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-[#DCE5DD] text-xs font-semibold text-[#172923] hover:bg-[#FAFBF9] transition-colors touch-target">
                        <svg class="w-4 h-4 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>Dashboard Sekolah (P01)</span>
                    </a>
                @endif
            </div>
        </div>
    </header>

    <!-- Peta Rute Perjalanan Multi-Sekolah -->
    <div class="mb-6 p-5 rounded-2xl bg-white border border-[#DCE5DD]">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-3 border-b border-[#DCE5DD] mb-3 gap-2">
            <div>
                <h2 class="text-base font-bold text-[#172923]">Peta Rute Perjalanan &amp; Titik Lokasi Sekolah</h2>
                <p class="text-xs text-[#53645B]">Visualisasi jalur navigasi OSRM dan urutan perhentian resmi dari SPPG ke sekolah tujuan.</p>
            </div>
            @if ($trip->total_distance_m)
                <span class="text-xs font-mono font-bold text-[#1C6654] bg-[#EDF7F2] px-2.5 py-1 rounded-lg border border-[#BCE2D0] shrink-0">
                    Jarak: {{ round($trip->total_distance_m / 1000, 1) }} km | Estimasi: ~{{ round(($trip->estimated_duration_s ?? 0) / 60) }} mnt
                </span>
            @endif
        </div>
        <div id="trip-dossier-map" class="w-full h-72 sm:h-96 rounded-xl overflow-hidden border border-[#DCE5DD] bg-slate-100"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Kolom Kiri (7 Kolom): Urutan Pemberhentian (Stop Rail) -->
        <div class="lg:col-span-7 space-y-6">
            <div class="p-5 rounded-2xl bg-white border border-[#DCE5DD]">
                <div class="flex items-center justify-between pb-3 border-b border-[#DCE5DD] mb-4">
                    <div>
                        <h2 class="text-base font-bold text-[#172923]">Urutan Pemberhentian Sekolah</h2>
                        <p class="text-xs text-[#53645B]">Jalur dan estimasi alokasi yang wajib diselesaikan berurutan.</p>
                    </div>
                    <span class="text-xs font-bold text-[#1C6654] bg-[#EDF7F2] px-2 py-0.5 rounded border border-[#BCE2D0]">
                        {{ $trip->stops->count() }} Perhentian
                    </span>
                </div>

                <!-- Stop Rail List -->
                <div class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-3 before:bottom-3 before:w-0.5 before:bg-[#DCE5DD]">
                    @foreach ($trip->stops as $stop)
                        @php
                            $isTargetSchool = $isPic && (int) $stop->school_id === (int) $userSchoolId;
                            $canViewFullDetails = ! $isPic || $isTargetSchool;
                        @endphp

                        <div class="relative">
                            <!-- Dot Indikator Urutan -->
                            <span class="absolute -left-6 top-1 w-5 h-5 rounded-full {{ $stop->status->value === 'COMPLETED' ? 'bg-[#1C6654]' : ($stop->status->value === 'EN_ROUTE' ? 'bg-[#285F80]' : 'bg-white border-2 border-[#DCE5DD]') }} text-white text-[10px] font-bold flex items-center justify-center">
                                @if ($stop->status->value === 'COMPLETED')
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    <span class="{{ $stop->status->value === 'EN_ROUTE' ? 'text-white' : 'text-[#53645B]' }}">{{ $stop->sequence_number }}</span>
                                @endif
                            </span>

                            <div class="p-4 rounded-xl {{ $isTargetSchool ? 'bg-[#EDF7F2] border-2 border-[#1C6654]' : 'bg-[#FAFBF9] border border-[#DCE5DD]' }}">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-[#172923]">
                                                @if ($canViewFullDetails)
                                                    {{ $stop->school->name }}
                                                @else
                                                    Pemberhentian Sekolah #{{ $stop->sequence_number }} (Terlindungi Privasi)
                                                @endif
                                            </span>
                                            @if ($isTargetSchool)
                                                <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-[#1C6654] text-white">
                                                    Sekolah Anda
                                                </span>
                                            @endif
                                        </div>
                                        @if ($canViewFullDetails)
                                            <span class="text-[11px] text-[#53645B] block mt-0.5">
                                                {{ $stop->school->address }} ({{ $stop->school->education_level }})
                                            </span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded border {{ $stop->status->badgeClass() }}">
                                        {{ $stop->status->label() }}
                                    </span>
                                </div>

                                <!-- Manifes Makanan pada Perhentian Ini -->
                                @if ($canViewFullDetails)
                                    <div class="mt-3 pt-2 border-t border-[#E8EEE9] space-y-1.5">
                                        <div class="text-[11px] font-semibold text-[#53645B] flex justify-between">
                                            <span>Paket Makanan yang Dialokasikan:</span>
                                            <span class="font-bold text-[#172923]">{{ $stop->total_loaded_portions }} Porsi</span>
                                        </div>
                                        <div class="space-y-1">
                                            @foreach ($stop->deliveryItems as $item)
                                                <div class="flex items-center justify-between text-[11px] pl-2 border-l-2 border-[#1C6654]">
                                                    <span class="text-[#172923]">
                                                        Batch: <a href="{{ route('batches.show', $item->foodBatch) }}" class="font-bold text-[#1C6654] hover:underline">{{ $item->foodBatch->batch_code }}</a>
                                                        <span class="text-[#708177]">({{ $item->foodBatch->recipeMenu?->name ?? 'Menu' }})</span>
                                                    </span>
                                                    <span class="font-bold text-[#1B573E]">
                                                        {{ $item->loaded_portions ?? $item->released_portions }} Porsi
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="mt-2 text-[11px] text-[#708177] italic">
                                        Rincian alokasi sekolah lain dirahasiakan sesuai prinsip perlindungan data tenant.
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Kolom Kanan (5 Kolom): Detail Operasional & Jejak Waktu -->
        <div class="lg:col-span-5 space-y-4">
            <!-- Kartu Info Armada & Pengemudi -->
            <div class="p-5 rounded-2xl bg-white border border-[#DCE5DD]">
                <h2 class="text-base font-bold text-[#172923] pb-3 border-b border-[#DCE5DD] mb-3">
                    Armada &amp; Personel Pengiriman
                </h2>

                <div class="space-y-3 text-xs">
                    <div class="flex justify-between py-1 border-b border-[#FAFBF9]">
                        <span class="text-[#53645B]">Kurir Bertugas:</span>
                        <span class="font-bold text-[#172923]">{{ $trip->driver->name }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-[#FAFBF9]">
                        <span class="text-[#53645B]">Nomor Polisi Armada:</span>
                        <span class="font-bold font-mono text-[#172923]">{{ $trip->vehicle->plate_number }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-[#FAFBF9]">
                        <span class="text-[#53645B]">Tipe Kendaraan:</span>
                        <span class="font-bold text-[#172923]">{{ $trip->vehicle->vehicle_type }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-[#FAFBF9]">
                        <span class="text-[#53645B]">Kapasitas Muatan:</span>
                        <span class="font-bold text-[#172923]">{{ $trip->vehicle->capacity_portions ?? 'Tidak dibatasi' }} Porsi</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-[#53645B]">Total Muatan Ditugaskan:</span>
                        <span class="font-bold text-[#1B573E]">{{ $trip->total_loaded_portions }} Porsi</span>
                    </div>
                </div>
            </div>

            <!-- Kartu Jejak Waktu & Milestone -->
            <div class="p-5 rounded-2xl bg-white border border-[#DCE5DD]">
                <h2 class="text-base font-bold text-[#172923] pb-3 border-b border-[#DCE5DD] mb-3">
                    Jejak Waktu Operasional
                </h2>

                <div class="space-y-3 text-xs">
                    <div class="flex justify-between py-1 border-b border-[#FAFBF9]">
                        <span class="text-[#53645B]">Direncanakan Berangkat:</span>
                        <span class="font-bold text-[#172923]">{{ $trip->planned_departure_at ? $trip->planned_departure_at->format('H:i') . ' WIB' : 'Belum diatur' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-[#FAFBF9]">
                        <span class="text-[#53645B]">Inspeksi Kendaraan:</span>
                        <span class="font-bold text-[#172923]">
                            @if ($trip->vehicle_checked_at)
                                {{ $trip->vehicle_checked_at->format('H:i') }} WIB ({{ $trip->vehicle_check_passed ? 'Laik' : 'Gagal' }})
                            @else
                                Belum diperiksa
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-[#FAFBF9]">
                        <span class="text-[#53645B]">Keberangkatan Nyata:</span>
                        <span class="font-bold text-[#172923]">{{ $trip->started_at ? $trip->started_at->format('H:i') . ' WIB' : 'Belum berangkat' }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-[#53645B]">Selesai Perjalanan:</span>
                        <span class="font-bold text-[#172923]">{{ $trip->completed_at ? $trip->completed_at->format('H:i') . ' WIB' : 'Belum selesai' }}</span>
                    </div>
                </div>
            </div>

            <!-- Catatan Telemetri GPS Kejujuran Operasional -->
            <div class="p-4 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] text-xs text-[#53645B]">
                <div class="font-bold text-[#172923] mb-1 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-[#708177]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Telemetri &amp; Pelacakan Real-time</span>
                </div>
                <p class="text-[11px] leading-relaxed">
                    Pelacakan GPS aktif di lapangan dan pengembalian ompreng merupakan bagian dari <strong>PBI-09</strong>. Sistem tidak menyimulasikan rute palsu atau data lokasi buatan.
                </p>
            </div>
        </div>
    </div>

    <!-- Script Inisialisasi Dossier Map -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.initDispatchMap) {
                window.initDispatchMap('trip-dossier-map', {
                    dataUrl: '{{ route('trips.map-data', $trip) }}'
                });
            }
        });
    </script>
</x-app-shell>
