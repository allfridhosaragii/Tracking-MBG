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

            <!-- Kartu Jejak Waktu & Milestone PBI-11 -->
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
                    <div class="flex justify-between py-1 border-b border-[#FAFBF9]">
                        <span class="text-[#53645B]">Mulai Kembali ke SPPG:</span>
                        <span class="font-bold text-[#172923]">{{ $trip->return_started_at ? $trip->return_started_at->format('H:i') . ' WIB' : 'Belum kembali' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-[#FAFBF9]">
                        <span class="text-[#53645B]">Tiba di Dapur SPPG:</span>
                        <span class="font-bold text-[#172923]">{{ $trip->arrived_at_sppg_at ? $trip->arrived_at_sppg_at->format('H:i') . ' WIB' : 'Belum tiba di SPPG' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-[#FAFBF9]">
                        <span class="text-[#53645B]">Selesai &amp; Ditutup:</span>
                        <span class="font-bold text-[#1C6654]">{{ $trip->completed_at ? $trip->completed_at->format('H:i') . ' WIB' : 'Belum ditutup' }}</span>
                    </div>
                    @if ($trip->closedBy)
                        <div class="flex justify-between py-1">
                            <span class="text-[#53645B]">Ditutup Oleh:</span>
                            <span class="font-bold text-[#172923]">{{ $trip->closedBy->name }} ({{ $trip->closedBy->role->label() }})</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Kartu Rekonsiliasi Wadah & Ompreng (PBI-11) -->
            <div class="p-5 rounded-2xl bg-white border border-[#DCE5DD]">
                <div class="flex items-center justify-between pb-3 border-b border-[#DCE5DD] mb-3">
                    <h2 class="text-base font-bold text-[#172923] flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-[#B78C56]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        Rekonsiliasi Tray &amp; Kontainer
                    </h2>
                    @if ($trip->total_trays_returned !== null)
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-200">
                            Terekonsiliasi
                        </span>
                    @else
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-200">
                            Belum Direkonsiliasi
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-3 gap-2 text-center text-xs mb-3">
                    <div class="p-2.5 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0]">
                        <span class="text-[10px] font-semibold text-[#1C6654] block mb-0.5">Kembali Baik</span>
                        <span class="text-base font-bold font-mono text-[#1C6654]">{{ $trip->total_trays_returned !== null ? $trip->total_trays_returned : '-' }}</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-amber-50 border border-amber-200">
                        <span class="text-[10px] font-semibold text-amber-800 block mb-0.5">Rusak / Retak</span>
                        <span class="text-base font-bold font-mono text-amber-700">{{ $trip->total_trays_damaged !== null ? $trip->total_trays_damaged : '-' }}</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-rose-50 border border-rose-200">
                        <span class="text-[10px] font-semibold text-rose-800 block mb-0.5">Hilang</span>
                        <span class="text-base font-bold font-mono text-rose-700">{{ $trip->total_trays_missing !== null ? $trip->total_trays_missing : '-' }}</span>
                    </div>
                </div>

                @if ($trip->tray_reconciliation_notes || $trip->trayReturn?->notes)
                    <div class="p-2.5 rounded-lg bg-[#FAFBF9] border border-[#DCE5DD] text-[11px] text-[#53645B] mb-3">
                        <span class="font-bold text-[#172923] block mb-0.5">Catatan Fisik Wadah:</span>
                        {{ $trip->tray_reconciliation_notes ?? $trip->trayReturn?->notes }}
                    </div>
                @endif

                <!-- Insiden Terkait Rekonsiliasi -->
                @if ($trip->incidents->isNotEmpty())
                    <div class="pt-2 border-t border-[#E8EEE9] space-y-2">
                        <span class="text-[11px] font-bold text-[#172923] uppercase tracking-wider block">Insiden Selisih / Kerusakan:</span>
                        @foreach ($trip->incidents as $incident)
                            <div class="p-2.5 rounded-lg bg-rose-50 border border-rose-200 text-xs">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-mono font-bold text-rose-900 text-[11px]">{{ $incident->incident_code }}</span>
                                    <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-rose-200 text-rose-900 uppercase">{{ $incident->severity->value }}</span>
                                </div>
                                <p class="text-[11px] text-rose-800 leading-relaxed">{{ $incident->description }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Kontrol Penutupan Administratif Kepala SPPG (PBI-11) -->
            @if (auth()->user()->role === \App\Enums\UserRole::KEPALA_SPPG && $trip->status === \App\Enums\TripStatus::IN_PROGRESS && ($closureReadiness['all_stops_handled'] ?? false))
                <div class="p-5 rounded-2xl bg-amber-50 border-2 border-amber-300 text-xs space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-amber-600 text-white flex items-center justify-center font-bold text-xs">!</span>
                        <h3 class="font-bold text-amber-900 text-sm">Otorisasi Penutupan Administratif</h3>
                    </div>

                    @if ($closureReadiness['can_close'] ?? false)
                        <p class="text-[11px] text-amber-800 leading-relaxed">
                            Seluruh prasyarat operasional telah terpenuhi (armada tiba di SPPG dan ompreng terekonsiliasi). Sebagai Kepala SPPG, Anda dapat menutup perjalanan secara administratif untuk menyelesaikan operasional harian.
                        </p>

                        @if (($closureReadiness['mode_b_unallocated_stops'] ?? 0) > 0)
                            <div class="p-2.5 rounded-lg bg-white/80 border border-amber-300 text-[11px] text-amber-900">
                                <strong>Peringatan Mode B:</strong> Terdapat {{ $closureReadiness['mode_b_unallocated_stops'] }} sekolah dengan serah terima darurat tanpa alokasi porsi. Nilai null tidak akan dimanipulasi atau diubah ke 0.
                            </div>
                        @endif

                        <form action="{{ route('head.distribution.trips.close', $trip) }}" method="POST" class="space-y-2.5"
                              onsubmit="return confirm('Apakah Anda yakin ingin menutup perjalanan ini secara administratif?');">
                            @csrf
                            <div>
                                <label for="head_completion_notes" class="block text-[10px] font-bold text-amber-900 mb-1">Catatan Penutupan Administratif (Opsional):</label>
                                <input type="text" name="completion_notes" id="head_completion_notes" placeholder="Catatan Kepala SPPG..." class="w-full text-xs p-2 rounded-lg border border-amber-300 bg-white">
                            </div>
                            <button type="submit" class="w-full min-h-[44px] py-2.5 px-4 rounded-xl font-bold text-xs bg-amber-700 text-white hover:bg-amber-800 transition-colors shadow-xs flex items-center justify-center gap-2 touch-target cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Tutup Perjalanan Administratif (Kepala SPPG)</span>
                            </button>
                        </form>
                    @else
                        <div class="p-3 rounded-xl bg-white border border-amber-300 space-y-2">
                            <span class="font-bold text-amber-950 block">Prasyarat Penutupan Belum Lengkap:</span>
                            <ul class="list-disc list-inside text-[11px] text-amber-900 space-y-1">
                                @foreach ($closureReadiness['blockers'] ?? [] as $blocker)
                                    <li>{{ $blocker }}</li>
                                @endforeach
                            </ul>
                            <p class="text-[10px] text-amber-800 italic pt-1">
                                Penutupan administratif hanya dapat diproses setelah armada mencatat kedatangan dan rekonsiliasi fisik ompreng selesai dicatat.
                            </p>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Catatan Telemetri GPS Kejujuran Operasional -->
            <div class="p-4 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] text-xs text-[#53645B]">
                <div class="font-bold text-[#172923] mb-1 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-[#708177]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Telemetri &amp; Pelacakan Real-time (PBI-09 &amp; PBI-11)</span>
                </div>
                <p class="text-[11px] leading-relaxed">
                    Pelacakan GPS aktif di lapangan dan kepulangan armada ke SPPG menggunakan server timestamp aktual. Saat trip ditutup, sesi telemetri otomatis dialihkan ke status OFFLINE permanen.
                </p>
            </div>
        </div>
    </div>

    <!-- Script Inisialisasi Dossier Map -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.initDispatchMap) {
                window.initDispatchMap('trip-dossier-map', {
                    dataUrl: '{{ route('trips.map-data', $trip) }}',
                    pollInterval: {{ $trip->status === \App\Enums\TripStatus::IN_PROGRESS ? 10000 : 0 }}
                });
            }
        });
    </script>
</x-app-shell>
