<x-app-shell title="Detail Perjalanan & Rute | {{ $trip->trip_code }}">
    <div class="space-y-6">
        <!-- Page Header (C03) -->
        <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-5 border-b border-[#DCE5DD]">
            <div class="space-y-1.5">
                <div class="flex flex-wrap items-center gap-2">
                    <x-role-badge :role="auth()->user()->role" />
                    <span class="text-xs font-mono text-[#53645B] bg-white border border-[#DCE5DD] px-2.5 py-0.5 rounded-lg">
                        SPPG: {{ $trip->sppg?->code ?? 'SPPG-' . $trip->sppg_id }}
                    </span>
                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-lg border {{ $trip->status->badgeClass() }}">
                        Status: {{ $trip->status->label() }}
                    </span>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-lg border bg-[#FAFBF9] text-[#172923] border-[#DCE5DD]">
                        {{ $trip->trip_type?->label() ?? 'Pengantaran' }}
                    </span>
                </div>
                <h1 class="page-title">
                    Rute Perjalanan &amp; Pemberhentian: {{ $trip->trip_code }}
                </h1>
                <p class="text-xs sm:text-sm text-[#53645B]">
                    Tanggal Layanan: <strong>{{ $trip->service_date->translatedFormat('l, d F Y') }}</strong> • Armada: <strong>{{ $trip->vehicle->plate_number }} ({{ $trip->vehicle->vehicle_type }})</strong>
                </p>
            </div>

            <!-- Contextual Role Navigation -->
            <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
                @if (auth()->user()->role === \App\Enums\UserRole::KEPALA_SPPG)
                    <a href="{{ route('head.distribution.index', ['service_date' => $trip->service_date->toDateString()]) }}"
                       class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-[#DCE5DD] text-xs font-bold text-[#172923] hover:bg-[#FAFBF9] subtle-shadow smooth-transition">
                        <svg class="w-4 h-4 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>Kontrol Distribusi (K05)</span>
                    </a>
                @elseif (auth()->user()->role === \App\Enums\UserRole::KURIR)
                    @if (in_array($trip->status->value, ['ASSIGNED', 'VEHICLE_CHECKED', 'READY'], true))
                        <a href="{{ route('driver.trips.prepare', $trip) }}"
                           class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs font-bold subtle-shadow smooth-transition">
                            <span>Buka Persiapan Trip (D02)</span>
                        </a>
                    @elseif ($trip->status === \App\Enums\TripStatus::IN_PROGRESS)
                        <a href="{{ route('driver.trips.active', $trip) }}"
                           class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs font-bold subtle-shadow smooth-transition">
                            <span>Buka Navigasi Aktif (D03)</span>
                        </a>
                    @else
                        <a href="{{ route('driver.trips.index') }}"
                           class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-[#DCE5DD] text-xs font-bold text-[#172923] hover:bg-[#FAFBF9] subtle-shadow smooth-transition">
                            <svg class="w-4 h-4 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            <span>Daftar Tugas (D01)</span>
                        </a>
                    @endif
                @elseif (auth()->user()->role === \App\Enums\UserRole::PIC_SEKOLAH)
                    <a href="{{ route('school.dashboard') }}"
                       class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-[#DCE5DD] text-xs font-bold text-[#172923] hover:bg-[#FAFBF9] subtle-shadow smooth-transition">
                        <svg class="w-4 h-4 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>Dashboard Sekolah (P01)</span>
                    </a>
                @endif
            </div>
        </header>

        <!-- Multi-School Route Map -->
        <section aria-labelledby="dossier-map-heading" class="luxury-surface p-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-3.5 border-b border-[#E7ECE8] mb-3.5 gap-2">
                <div>
                    <h2 id="dossier-map-heading" class="section-title">Peta Rute Perjalanan &amp; Titik Lokasi Sekolah</h2>
                    <p class="text-xs text-[#53645B]">Visualisasi jalur navigasi OSRM dan urutan perhentian resmi dari SPPG ke sekolah tujuan.</p>
                </div>
                @if ($trip->total_distance_m)
                    <span class="text-xs font-mono font-bold text-[#21684B] bg-[#EDF7F2] px-3 py-1 rounded-lg border border-[#BCE2D0] shrink-0 tabular-nums self-start sm:self-auto">
                        Jarak: {{ round($trip->total_distance_m / 1000, 1) }} km • Estimasi: ~{{ round(($trip->estimated_duration_s ?? 0) / 60) }} mnt
                    </span>
                @endif
            </div>
            <div id="trip-dossier-map" class="w-full h-72 sm:h-96 rounded-xl overflow-hidden border border-[#DCE5DD] bg-[#EDF2ED]"></div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left 7 Columns: Ordered Stop Rail -->
            <div class="lg:col-span-7 space-y-6">
                <section aria-labelledby="stop-rail-heading" class="luxury-surface p-5 sm:p-6">
                    <div class="flex items-center justify-between pb-3.5 border-b border-[#E7ECE8] mb-5">
                        <div>
                            <h2 id="stop-rail-heading" class="section-title">Urutan Pemberhentian Sekolah</h2>
                            <p class="text-xs text-[#53645B]">Jalur dan alokasi yang wajib diselesaikan berurutan.</p>
                        </div>
                        <span class="text-xs font-bold text-[#21684B] bg-[#EDF7F2] px-2.5 py-1 rounded-lg border border-[#BCE2D0] tabular-nums">
                            {{ $trip->stops->count() }} Perhentian
                        </span>
                    </div>

                    <div class="relative pl-6 space-y-5 before:absolute before:left-2.5 before:top-3 before:bottom-3 before:w-0.5 before:bg-[#DCE5DD]">
                        @foreach ($trip->stops as $stop)
                            @php
                                $isTargetSchool = $isPic && (int) $stop->school_id === (int) $userSchoolId;
                                $canViewFullDetails = ! $isPic || $isTargetSchool;
                            @endphp

                            <div class="relative">
                                <span class="absolute -left-6 top-1 w-5 h-5 rounded-full {{ $stop->status->value === 'COMPLETED' ? 'bg-[#1C6654]' : ($stop->status->value === 'EN_ROUTE' ? 'bg-[#285F80]' : 'bg-white border-2 border-[#DCE5DD]') }} text-white text-[10px] font-bold flex items-center justify-center tabular-nums">
                                    @if ($stop->status->value === 'COMPLETED')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @else
                                        <span class="{{ $stop->status->value === 'EN_ROUTE' ? 'text-white' : 'text-[#53645B]' }}">{{ $stop->sequence_number }}</span>
                                    @endif
                                </span>

                                <div class="p-4 rounded-xl {{ $isTargetSchool ? 'bg-[#EDF7F2] border-2 border-[#1C6654]' : 'inset-surface' }}">
                                    <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-xs sm:text-sm font-bold text-[#172923]">
                                                    @if ($canViewFullDetails)
                                                        {{ $stop->school->name }}
                                                    @else
                                                        Pemberhentian Sekolah #{{ $stop->sequence_number }} (Terlindungi Privasi)
                                                    @endif
                                                </span>
                                                @if ($isTargetSchool)
                                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-[#1C6654] text-white">
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
                                        <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-lg border {{ $stop->status->badgeClass() }}">
                                            {{ $stop->status->label() }}
                                        </span>
                                    </div>

                                    @if ($canViewFullDetails)
                                        <div class="mt-3 pt-2.5 border-t border-[#E7ECE8] space-y-1.5">
                                            <div class="text-[11px] font-semibold text-[#53645B] flex justify-between">
                                                <span>Paket Makanan Dialokasikan:</span>
                                                <span class="font-bold text-[#172923] tabular-nums">{{ $stop->total_loaded_portions }} Porsi</span>
                                            </div>
                                            <div class="space-y-1">
                                                @foreach ($stop->deliveryItems as $item)
                                                    <div class="flex items-center justify-between text-[11px] pl-2.5 border-l-2 border-[#1C6654]">
                                                        <span class="text-[#172923]">
                                                            Batch: <a href="{{ route('batches.show', $item->foodBatch) }}" class="font-bold text-[#1C6654] hover:underline">{{ $item->foodBatch->batch_code }}</a>
                                                            <span class="text-[#53645B]">({{ $item->foodBatch->recipeMenu?->name ?? $item->foodBatch->menu?->name ?? 'Menu' }})</span>
                                                        </span>
                                                        <span class="font-bold text-[#21684B] tabular-nums">
                                                            {{ $item->loaded_portions ?? $item->released_portions }} Porsi
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <p class="mt-2 text-[11px] text-[#53645B]">
                                            Rincian alokasi sekolah lain dirahasiakan sesuai prinsip perlindungan data tenant.
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <!-- Right 5 Columns: Fleet, Timeline, Tray Reconciliation & Head Closure -->
            <div class="lg:col-span-5 space-y-5">
                <!-- Fleet & Personnel -->
                <section aria-labelledby="fleet-info-heading" class="luxury-surface p-5">
                    <h2 id="fleet-info-heading" class="section-title pb-3 border-b border-[#E7ECE8] mb-3">
                        Armada &amp; Personel Pengiriman
                    </h2>

                    <div class="space-y-2.5 text-xs">
                        <div class="flex justify-between py-1 border-b border-[#E7ECE8]">
                            <span class="text-[#53645B]">Kurir Bertugas:</span>
                            <span class="font-bold text-[#172923]">{{ $trip->driver->name }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#E7ECE8]">
                            <span class="text-[#53645B]">Nomor Polisi Armada:</span>
                            <span class="font-bold font-mono text-[#172923]">{{ $trip->vehicle->plate_number }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#E7ECE8]">
                            <span class="text-[#53645B]">Tipe Kendaraan:</span>
                            <span class="font-bold text-[#172923]">{{ $trip->vehicle->vehicle_type }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#E7ECE8]">
                            <span class="text-[#53645B]">Kapasitas Muatan:</span>
                            <span class="font-bold text-[#172923] tabular-nums">{{ $trip->vehicle->capacity_portions ?? 'Tidak dibatasi' }} Porsi</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-[#53645B]">Total Muatan Ditugaskan:</span>
                            <span class="font-bold text-[#21684B] tabular-nums">{{ $trip->total_loaded_portions }} Porsi</span>
                        </div>
                    </div>
                </section>

                <!-- Operational Timestamps -->
                <section aria-labelledby="trip-timestamps-heading" class="luxury-surface p-5">
                    <h2 id="trip-timestamps-heading" class="section-title pb-3 border-b border-[#E7ECE8] mb-3">
                        Jejak Waktu Operasional
                    </h2>

                    <div class="space-y-2.5 text-xs">
                        <div class="flex justify-between py-1 border-b border-[#E7ECE8]">
                            <span class="text-[#53645B]">Direncanakan Berangkat:</span>
                            <span class="font-bold text-[#172923] tabular-nums">{{ $trip->planned_departure_at ? $trip->planned_departure_at->format('H:i') . ' WIB' : 'Belum diatur' }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#E7ECE8]">
                            <span class="text-[#53645B]">Inspeksi Kendaraan:</span>
                            <span class="font-bold text-[#172923] tabular-nums">
                                @if ($trip->vehicle_checked_at)
                                    {{ $trip->vehicle_checked_at->format('H:i') }} WIB ({{ $trip->vehicle_check_passed ? 'Laik' : 'Gagal' }})
                                @else
                                    Belum diperiksa
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#E7ECE8]">
                            <span class="text-[#53645B]">Keberangkatan Nyata:</span>
                            <span class="font-bold text-[#172923] tabular-nums">{{ $trip->started_at ? $trip->started_at->format('H:i') . ' WIB' : 'Belum berangkat' }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#E7ECE8]">
                            <span class="text-[#53645B]">Mulai Kembali ke SPPG:</span>
                            <span class="font-bold text-[#172923] tabular-nums">{{ $trip->return_started_at ? $trip->return_started_at->format('H:i') . ' WIB' : 'Belum kembali' }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#E7ECE8]">
                            <span class="text-[#53645B]">Tiba di Dapur SPPG:</span>
                            <span class="font-bold text-[#172923] tabular-nums">{{ $trip->arrived_at_sppg_at ? $trip->arrived_at_sppg_at->format('H:i') . ' WIB' : 'Belum tiba di SPPG' }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#E7ECE8]">
                            <span class="text-[#53645B]">Selesai &amp; Ditutup:</span>
                            <span class="font-bold text-[#1C6654] tabular-nums">{{ $trip->completed_at ? $trip->completed_at->format('H:i') . ' WIB' : 'Belum ditutup' }}</span>
                        </div>
                        @if ($trip->closedBy)
                            <div class="flex justify-between py-1">
                                <span class="text-[#53645B]">Ditutup Oleh:</span>
                                <span class="font-bold text-[#172923]">{{ $trip->closedBy->name }} ({{ $trip->closedBy->role->label() }})</span>
                            </div>
                        @endif
                    </div>
                </section>

                <!-- Tray & Container Reconciliation -->
                <section aria-labelledby="tray-recon-heading" class="luxury-surface p-5">
                    <div class="flex items-center justify-between pb-3 border-b border-[#E7ECE8] mb-3.5">
                        <h2 id="tray-recon-heading" class="section-title">Rekonsiliasi Tray &amp; Kontainer</h2>
                        @if ($trip->total_trays_returned !== null)
                            <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-lg bg-[#EDF7F2] text-[#21684B] border border-[#BCE2D0]">
                                Terekonsiliasi
                            </span>
                        @else
                            <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-lg bg-[#FEF7EC] text-[#885D1C] border border-[#F7D7A3]">
                                Belum Direkonsiliasi
                            </span>
                        @endif
                    </div>

                    <div class="grid grid-cols-3 gap-2.5 text-center text-xs mb-3">
                        <div class="p-2.5 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0]">
                            <span class="text-[10px] font-semibold text-[#21684B] block mb-0.5">Kembali Baik</span>
                            <span class="text-base font-bold font-mono text-[#21684B] tabular-nums">{{ $trip->total_trays_returned !== null ? $trip->total_trays_returned : '-' }}</span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-[#FEF7EC] border border-[#F7D7A3]">
                            <span class="text-[10px] font-semibold text-[#885D1C] block mb-0.5">Rusak / Retak</span>
                            <span class="text-base font-bold font-mono text-[#885D1C] tabular-nums">{{ $trip->total_trays_damaged !== null ? $trip->total_trays_damaged : '-' }}</span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-[#FDF2F2] border border-[#F5C2C2]">
                            <span class="text-[10px] font-semibold text-[#9B3232] block mb-0.5">Hilang</span>
                            <span class="text-base font-bold font-mono text-[#9B3232] tabular-nums">{{ $trip->total_trays_missing !== null ? $trip->total_trays_missing : '-' }}</span>
                        </div>
                    </div>

                    @if ($trip->tray_reconciliation_notes || $trip->trayReturn?->notes)
                        <div class="inset-surface p-3 text-[11px] text-[#53645B] mb-3">
                            <span class="font-bold text-[#172923] block mb-0.5">Catatan Fisik Wadah:</span>
                            {{ $trip->tray_reconciliation_notes ?? $trip->trayReturn?->notes }}
                        </div>
                    @endif

                    @if ($trip->incidents->isNotEmpty())
                        <div class="pt-3 border-t border-[#E7ECE8] space-y-2">
                            <span class="eyebrow-label text-[#9B3232] block">Insiden Selisih / Kerusakan</span>
                            @foreach ($trip->incidents as $incident)
                                <div class="p-3 rounded-xl bg-[#FDF2F2] border border-[#F5C2C2] text-xs">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-mono font-bold text-[#9B3232] text-[11px]">{{ $incident->incident_code }}</span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-white text-[#9B3232] border border-[#F5C2C2] uppercase">{{ $incident->severity->value }}</span>
                                    </div>
                                    <p class="text-[11px] text-[#172923] leading-relaxed">{{ $incident->description }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

                <!-- Head SPPG Administrative Trip Closure (PBI-11) -->
                @if (auth()->user()->role === \App\Enums\UserRole::KEPALA_SPPG && $trip->status === \App\Enums\TripStatus::IN_PROGRESS && ($closureReadiness['all_stops_handled'] ?? false))
                    <section aria-labelledby="head-close-heading" class="p-5 rounded-2xl bg-[#FEF7EC] border border-[#F7D7A3] text-xs space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-[#885D1C] text-white flex items-center justify-center font-bold text-xs">!</span>
                            <h3 id="head-close-heading" class="font-bold text-[#885D1C] text-sm">Otorisasi Penutupan Administratif</h3>
                        </div>

                        @if ($closureReadiness['can_close'] ?? false)
                            <p class="text-[11px] text-[#885D1C] leading-relaxed">
                                Seluruh prasyarat operasional telah terpenuhi (armada tiba di SPPG dan ompreng terekonsiliasi). Sebagai Kepala SPPG, Anda dapat menutup perjalanan secara administratif.
                            </p>

                            @if (($closureReadiness['mode_b_unallocated_stops'] ?? 0) > 0)
                                <div class="p-3 rounded-xl bg-white border border-[#F7D7A3] text-[11px] text-[#885D1C]">
                                    <strong>Peringatan Mode B:</strong> Terdapat {{ $closureReadiness['mode_b_unallocated_stops'] }} sekolah dengan serah terima darurat tanpa alokasi porsi. Nilai null tidak diubah ke 0.
                                </div>
                            @endif

                            <form action="{{ route('head.distribution.trips.close', $trip) }}" method="POST" class="space-y-3"
                                  onsubmit="return confirm('Apakah Anda yakin ingin menutup perjalanan ini secara administratif?');">
                                @csrf
                                <div>
                                    <label for="head_completion_notes" class="block text-[11px] font-bold text-[#885D1C] mb-1">Catatan Penutupan Administratif (Opsional):</label>
                                    <input type="text" name="completion_notes" id="head_completion_notes" placeholder="Catatan Kepala SPPG..." class="min-h-[44px] w-full text-xs px-3.5 py-2 rounded-xl border border-[#F7D7A3] bg-white text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#885D1C]">
                                </div>
                                <button type="submit" class="min-h-[44px] w-full py-2.5 px-4 rounded-xl font-bold text-xs bg-[#885D1C] text-white hover:bg-[#6E4A15] smooth-transition subtle-shadow flex items-center justify-center gap-2 cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Tutup Perjalanan Administratif (Kepala SPPG)</span>
                                </button>
                            </form>
                        @else
                            <div class="p-3.5 rounded-xl bg-white border border-[#F7D7A3] space-y-2">
                                <span class="font-bold text-[#172923] block">Prasyarat Penutupan Belum Lengkap:</span>
                                <ul class="list-disc list-inside text-[11px] text-[#885D1C] space-y-1">
                                    @foreach ($closureReadiness['blockers'] ?? [] as $blocker)
                                        <li>{{ $blocker }}</li>
                                    @endforeach
                                </ul>
                                <p class="text-[11px] text-[#53645B] pt-1">
                                    Penutupan administratif diproses setelah armada mencatat kedatangan dan rekonsiliasi fisik ompreng selesai.
                                </p>
                            </div>
                        @endif
                    </section>
                @endif

                <!-- Telemetry Notice -->
                <details class="sop-disclosure">
                    <summary>
                        <span class="text-xs font-bold text-[#172923]">Telemetri &amp; Pelacakan Real-time (PBI-09 &amp; PBI-11)</span>
                        <span class="text-[11px] font-semibold text-[#1C6654]">Info</span>
                    </summary>
                    <div class="sop-disclosure-body text-xs text-[#53645B] leading-relaxed">
                        Pelacakan GPS aktif di lapangan dan kepulangan armada ke SPPG menggunakan waktu server aktual. Saat trip ditutup, sesi telemetri otomatis beralih ke status OFFLINE permanen.
                    </div>
                </details>
            </div>
        </div>
    </div>

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
