<x-app-shell title="Perjalanan Aktif | {{ $trip->trip_code }}">
    <div class="space-y-6">
        <!-- Page Header (D03) -->
        <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-5 border-b border-[#DCE5DD]">
            <div class="space-y-1.5">
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('driver.trips.index') }}" class="min-h-[36px] text-xs font-bold text-[#1C6654] hover:underline inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span>Kembali ke Daftar Tugas (D01)</span>
                    </a>
                    <span class="text-xs font-mono text-[#53645B] bg-white border border-[#DCE5DD] px-2.5 py-0.5 rounded-lg">
                        Trip: {{ $trip->trip_code }}
                    </span>
                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-lg border {{ $trip->status->badgeClass() }}">
                        Status: {{ $trip->status->label() }}
                    </span>
                </div>
                <h1 class="page-title">Perjalanan Aktif &amp; Navigasi Multi-Stop</h1>
                <p class="text-xs sm:text-sm text-[#53645B]">
                    Armada: <strong>{{ $trip->vehicle->plate_number }} ({{ $trip->vehicle->vehicle_name ?: $trip->vehicle->vehicle_type }})</strong> •
                    Dapur SPPG: <strong>{{ $trip->sppg->name }}</strong>
                </p>
            </div>

            <div class="inset-surface px-3.5 py-2.5 text-[11px] text-[#53645B] sm:max-w-xs">
                <span class="font-bold text-[#172923] block">Keselamatan Kurir:</span>
                Operasikan aplikasi hanya saat kendaraan berhenti aman di titik tujuan.
            </div>
        </header>

        <!-- Flash Notifications -->
        @if (session('success'))
            <div class="p-4 rounded-2xl bg-[#EDF7F2] border border-[#BCE2D0] text-[#21684B] text-xs sm:text-sm font-semibold flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 rounded-2xl bg-[#FDF2F2] border border-[#F5C2C2] text-[#9B3232] text-xs sm:text-sm font-semibold flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if ($trip->isCompleted())
            <!-- Completed GPS Session Card -->
            <section aria-label="Status Sesi GPS" class="luxury-surface p-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-[#53645B] shrink-0"></div>
                            <span class="text-sm font-bold text-[#172923]">Sesi Berbagi Lokasi GPS Telah Ditutup</span>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-md border bg-[#FAFBF9] text-[#53645B] border-[#DCE5DD]">
                                OFFLINE (Perjalanan Selesai)
                            </span>
                        </div>
                        <p class="text-xs text-[#53645B]">
                            Perjalanan ini telah selesai dan ditutup resmi. Pelacakan lokasi otomatis dimatikan demi privasi dan penghematan baterai.
                        </p>
                    </div>
                    <span class="inline-flex items-center self-start sm:self-auto gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#EDF7F2] text-[#21684B] border border-[#BCE2D0] shrink-0">
                        ✓ Sesi Berakhir Aman
                    </span>
                </div>
            </section>
        @else
            <!-- Explicit Driver GPS Consent & Control Banner (Critical Gate A) -->
            <section id="gps-status-card" aria-label="Kontrol Berbagi Lokasi GPS" class="luxury-surface p-4 sm:p-5 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <div id="gps-indicator-dot" class="w-3 h-3 rounded-full bg-[#53645B] shrink-0"></div>
                            <span class="text-sm font-bold text-[#172923]">Berbagi Lokasi GPS Perjalanan</span>
                            <span id="gps-status-text" class="text-xs font-semibold px-2.5 py-0.5 rounded-md border bg-[#FAFBF9] text-[#53645B] border-[#DCE5DD]">
                                Menunggu Tindakan Kurir (Belum Aktif)
                            </span>
                        </div>
                        <p id="gps-status-detail" class="text-xs text-[#53645B]">
                            Tekan tombol untuk mulai membagikan lokasi armada selama pengantaran berlangsung.
                        </p>
                    </div>

                    <div class="shrink-0 flex items-center gap-2">
                        <button type="button" id="btn-toggle-gps"
                                class="min-h-[44px] w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-bold bg-[#1C6654] text-white hover:bg-[#164D42] smooth-transition flex items-center justify-center gap-2 subtle-shadow cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span id="btn-gps-label">Mulai Bagikan Lokasi</span>
                        </button>
                    </div>
                </div>

                <div id="gps-accuracy-container" class="flex items-center justify-between gap-2">
                    <details class="sop-disclosure flex-1">
                        <summary>
                            <span class="text-xs font-semibold text-[#53645B]">Privasi Lokasi &amp; Batasan Peramban</span>
                            <span class="text-[11px] font-semibold text-[#1C6654]">Detail</span>
                        </summary>
                        <div class="sop-disclosure-body grid grid-cols-1 md:grid-cols-2 gap-3 text-[11px] text-[#53645B]">
                            <div>
                                <strong class="text-[#172923]">Privasi &amp; Keamanan:</strong> Lokasi digunakan khusus memantau rute pengantaran. Hanya Kepala SPPG dan sistem berwenang yang dapat melihat koordinat presisi.
                            </div>
                            <div>
                                <strong class="text-[#172923]">Batasan Peramban:</strong> Pelacakan web dapat terhenti jika layar ponsel terkunci. Kurir dapat menghentikan berbagi lokasi sewaktu-waktu.
                            </div>
                        </div>
                    </details>
                    <span id="gps-accuracy-display" class="font-mono text-[11px] font-semibold text-[#1C6654] tabular-nums shrink-0"></span>
                </div>
            </section>
        @endif

        <!-- Main Operational Grid: Action Card FIRST on Mobile, Map Left / Stop Right on Desktop -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Left 7 Columns on Desktop (Order 2 on Mobile so Primary Stop CTA stays on First Fold) -->
            <div class="order-2 lg:order-1 lg:col-span-7 space-y-6">
                <section aria-labelledby="active-map-heading" class="luxury-surface p-4 sm:p-5">
                    <div class="flex items-center justify-between pb-3 border-b border-[#E7ECE8] mb-3">
                        <h2 id="active-map-heading" class="section-title">Peta Perjalanan &amp; Posisi Armada</h2>
                        <span class="text-xs font-mono font-bold text-[#1C6654] tabular-nums">
                            @if ($trip->isCompleted())
                                Perjalanan Selesai
                            @elseif ($trip->hasArrivedAtDepot())
                                Tiba di SPPG (Rekonsiliasi)
                            @elseif ($trip->isReturning())
                                Rute Kembali ke SPPG
                            @elseif ($activeStop)
                                Stop Aktif: #{{ $activeStop->sequence_number }}
                            @else
                                Seluruh Stop Ditangani
                            @endif
                        </span>
                    </div>

                    <div id="driver-active-map"
                         class="w-full h-72 sm:h-96 rounded-xl overflow-hidden border border-[#DCE5DD] bg-[#EDF2ED] mb-3">
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-[#53645B]">
                        <span>Dapur: {{ $trip->sppg->name }}</span>
                        <span class="font-medium text-[#172923]">
                            @if ($trip->isCompleted())
                                Selesai di Pangkalan SPPG
                            @elseif ($trip->hasArrivedAtDepot())
                                Pangkalan SPPG (Tahap Rekonsiliasi)
                            @elseif ($trip->isReturning())
                                Tujuan: Dapur SPPG (Kepulangan Armada)
                            @elseif ($activeStop)
                                Tujuan Berikutnya: {{ $activeStop->school->name }}
                            @else
                                Siap Kembali ke SPPG
                            @endif
                        </span>
                    </div>
                </section>
            </div>

            <!-- Right 5 Columns on Desktop (Order 1 on Mobile: Primary Thumb Zone) -->
            <div class="order-1 lg:order-2 lg:col-span-5 space-y-5">
                @if ($trip->isCompleted())
                    <section aria-label="Ringkasan Penutupan Perjalanan" class="p-5 rounded-2xl bg-[#EDF7F2] border-2 border-[#1C6654] space-y-4">
                        <div class="flex items-center gap-2.5 pb-3 border-b border-[#BCE2D0]">
                            <span class="w-8 h-8 rounded-xl bg-[#1C6654] text-white flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            <div>
                                <h3 class="section-title">Perjalanan Telah Ditutup Resmi</h3>
                                <span class="text-[11px] text-[#21684B] font-semibold">Status: {{ $trip->status->label() }} (Selesai Operasional)</span>
                            </div>
                        </div>

                        <div class="space-y-2 text-xs text-[#172923]">
                            <div class="flex justify-between py-1 border-b border-[#BCE2D0]/70">
                                <span class="text-[#53645B]">Waktu Penyelesaian:</span>
                                <span class="font-bold font-mono tabular-nums">{{ $trip->completed_at?->translatedFormat('d M Y, H:i') ?? '-' }} WIB</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-[#BCE2D0]/70">
                                <span class="text-[#53645B]">Ditutup Oleh:</span>
                                <span class="font-bold">{{ $trip->closedBy?->name ?? 'Kurir / Petugas' }}</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-[#53645B]">Ompreng Kembali / Rusak / Hilang:</span>
                                <span class="font-bold font-mono text-[#21684B] tabular-nums">
                                    {{ $trip->total_trays_returned ?? 0 }} / {{ $trip->total_trays_damaged ?? 0 }} / {{ $trip->total_trays_missing ?? 0 }}
                                </span>
                            </div>
                        </div>

                        <a href="{{ route('trips.show', $trip) }}"
                           class="min-h-[44px] inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 rounded-xl font-bold text-xs bg-[#1C6654] text-white hover:bg-[#164D42] smooth-transition subtle-shadow">
                            <span>Lihat Dossier Lengkap &amp; Bukti Rekonsiliasi (C03)</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </a>
                    </section>

                @elseif ($closureReadiness['all_stops_handled'])
                    <!-- Return to Depot & Container Reconciliation (PBI-11) -->
                    <section aria-label="Kepulangan dan Rekonsiliasi SPPG" class="luxury-surface p-5 border-2 border-[#1C6654] space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-[#E7ECE8]">
                            <div class="flex items-center gap-2.5">
                                <span class="w-7 h-7 rounded-xl bg-[#1C6654] text-white flex items-center justify-center text-xs font-bold shrink-0">
                                    ✓
                                </span>
                                <div>
                                    <h3 class="section-title">Kepulangan &amp; Rekonsiliasi SPPG</h3>
                                    <span class="text-[11px] text-[#21684B] font-semibold">Seluruh {{ $stops->count() }} Sekolah Telah Ditangani</span>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md border bg-[#FEF7EC] text-[#885D1C] border-[#F7D7A3]">
                                PBI-11
                            </span>
                        </div>

                        @if ($closureReadiness['mode_b_unallocated_stops'] > 0)
                            <div class="p-3.5 rounded-xl bg-[#FEF7EC] border border-[#F7D7A3] text-[#885D1C] text-xs">
                                <span class="font-bold block mb-0.5">Catatan Integritas Data (Mode B):</span>
                                Terdapat {{ $closureReadiness['mode_b_unallocated_stops'] }} sekolah dengan serah terima darurat. Porsi belum teratribusi tetap tercatat jujur dan TIDAK dikonversi menjadi angka 0 palsu.
                            </div>
                        @endif

                        <!-- Stage 1: Return to Depot -->
                        <div class="inset-surface p-4 space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-bold text-[#172923] flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-[#172923] text-white text-[10px] flex items-center justify-center font-bold">1</span>
                                    <span>Perjalanan Kembali ke SPPG</span>
                                </span>
                                @if ($trip->hasArrivedAtDepot())
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-[#EDF7F2] text-[#21684B] border border-[#BCE2D0]">Tiba di Pangkalan</span>
                                @elseif ($trip->isReturning())
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-[#F0F6FA] text-[#285F80] border border-[#C2DBE8]">Sedang Perjalanan</span>
                                @else
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-white text-[#53645B] border border-[#DCE5DD]">Belum Berangkat</span>
                                @endif
                            </div>

                            @if (! $trip->isReturning())
                                <p class="text-[11px] text-[#53645B] leading-relaxed">
                                    Seluruh pengantaran selesai. Tekan tombol untuk mengarahkan peta kembali ke Dapur SPPG <strong>{{ $trip->sppg->name }}</strong> dan mencatat waktu awal kepulangan.
                                </p>
                                <form action="{{ route('driver.trips.return', $trip) }}" method="POST" class="space-y-3">
                                    @csrf
                                    <div>
                                        <label for="return_notes" class="block text-[11px] font-semibold text-[#53645B] mb-1">Catatan Kepulangan (Opsional):</label>
                                        <input type="text" name="return_notes" id="return_notes" placeholder="Contoh: Kondisi armada aman, kembali ke SPPG..." class="min-h-[44px] w-full text-xs px-3.5 py-2 rounded-xl border border-[#DCE5DD] bg-white text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                    </div>
                                    <button type="submit" class="min-h-[44px] w-full py-2.5 px-4 rounded-xl font-bold text-xs bg-[#1C6654] text-white hover:bg-[#164D42] smooth-transition subtle-shadow flex items-center justify-center gap-2 cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                        </svg>
                                        <span>Mulai Perjalanan Kembali ke SPPG</span>
                                    </button>
                                </form>
                            @elseif (! $trip->hasArrivedAtDepot())
                                <div class="space-y-2.5 text-xs">
                                    <div class="flex items-center justify-between text-[11px] text-[#53645B]">
                                        <span>Waktu Mulai Kembali:</span>
                                        <span class="font-bold font-mono text-[#172923] tabular-nums">{{ $trip->return_started_at?->format('H:i') }} WIB</span>
                                    </div>
                                    <p class="text-[11px] text-[#285F80] leading-relaxed">
                                        Armada sedang dalam perjalanan kembali. Setibanya di pangkalan SPPG, konfirmasikan kedatangan di bawah ini.
                                    </p>
                                    <form action="{{ route('driver.trips.arrive-depot', $trip) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="min-h-[44px] w-full py-2.5 px-4 rounded-xl font-bold text-xs bg-[#1C6654] text-white hover:bg-[#164D42] smooth-transition subtle-shadow flex items-center justify-center gap-2 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                            <span>Konfirmasi Tiba di Dapur SPPG</span>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <div class="flex items-center justify-between text-xs p-3 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-[#21684B]">
                                    <span class="font-semibold flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-[#21684B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>Armada Tiba di SPPG:</span>
                                    </span>
                                    <span class="font-bold font-mono tabular-nums">{{ $trip->arrived_at_sppg_at?->format('H:i') }} WIB</span>
                                </div>
                            @endif
                        </div>

                        <!-- Stage 2: Tray Reconciliation -->
                        @if ($trip->hasArrivedAtDepot())
                            <div class="inset-surface p-4 space-y-3">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-[#172923] flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full bg-[#B78C56] text-white text-[10px] flex items-center justify-center font-bold">2</span>
                                        <span>Rekonsiliasi Tray &amp; Kontainer</span>
                                    </span>
                                    @if ($trip->total_trays_returned !== null)
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-[#EDF7F2] text-[#21684B] border border-[#BCE2D0]">Terekonsiliasi ({{ $trip->total_trays_returned }} Kembali)</span>
                                    @else
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-[#FEF7EC] text-[#885D1C] border border-[#F7D7A3]">Wajib Diisi</span>
                                    @endif
                                </div>

                                <p class="text-[11px] text-[#53645B] leading-relaxed">
                                    Catat jumlah wadah fisik/ompreng yang diserahkan kembali ke gudang SPPG.
                                </p>

                                <form action="{{ route('driver.trips.reconcile-trays', $trip) }}" method="POST" class="space-y-3">
                                    @csrf
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <label for="trays_returned" class="block text-[10px] font-bold text-[#21684B] mb-1">Kembali Baik</label>
                                            <input type="number" name="trays_returned" id="trays_returned" min="0" required
                                                   value="{{ old('trays_returned', $trip->total_trays_returned ?? $trip->trayReturn?->trays_returned ?? '') }}"
                                                   class="min-h-[44px] w-full p-2 text-xs rounded-xl border border-[#DCE5DD] bg-white text-center font-bold text-[#21684B] tabular-nums focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                        </div>
                                        <div>
                                            <label for="trays_damaged" class="block text-[10px] font-bold text-[#885D1C] mb-1">Rusak / Retak</label>
                                            <input type="number" name="trays_damaged" id="trays_damaged" min="0"
                                                   value="{{ old('trays_damaged', $trip->total_trays_damaged ?? $trip->trayReturn?->trays_damaged ?? 0) }}"
                                                   class="min-h-[44px] w-full p-2 text-xs rounded-xl border border-[#DCE5DD] bg-white text-center font-bold text-[#885D1C] tabular-nums focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                        </div>
                                        <div>
                                            <label for="trays_missing" class="block text-[10px] font-bold text-[#9B3232] mb-1">Hilang</label>
                                            <input type="number" name="trays_missing" id="trays_missing" min="0"
                                                   value="{{ old('trays_missing', $trip->total_trays_missing ?? $trip->trayReturn?->trays_missing ?? 0) }}"
                                                   class="min-h-[44px] w-full p-2 text-xs rounded-xl border border-[#DCE5DD] bg-white text-center font-bold text-[#9B3232] tabular-nums focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                        </div>
                                    </div>
                                    <div>
                                        <label for="notes" class="block text-[11px] font-semibold text-[#53645B] mb-1">Catatan Kondisi Fisik Ompreng (Opsional):</label>
                                        <input type="text" name="notes" id="notes"
                                               value="{{ old('notes', $trip->tray_reconciliation_notes ?? $trip->trayReturn?->notes ?? '') }}"
                                               placeholder="Contoh: 1 tray retak pada engsel pengunci..."
                                               class="min-h-[44px] w-full text-xs px-3.5 py-2 rounded-xl border border-[#DCE5DD] bg-white text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                    </div>
                                    <button type="submit" class="min-h-[44px] w-full py-2.5 px-3 rounded-xl font-bold text-xs bg-white border border-[#1C6654] text-[#1C6654] hover:bg-[#EDF7F2] smooth-transition cursor-pointer">
                                        {{ ($trip->trayReturn || $trip->total_trays_returned !== null) ? 'Perbarui Rekonsiliasi Ompreng' : 'Simpan Rekonsiliasi Ompreng' }}
                                    </button>
                                </form>
                            </div>

                            <!-- Stage 3: Final Trip Closure -->
                            <div class="inset-surface p-4 space-y-3">
                                <span class="text-xs font-bold text-[#172923] flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-[#1C6654] text-white text-[10px] flex items-center justify-center font-bold">3</span>
                                    <span>Penutupan Akhir Perjalanan Resmi</span>
                                </span>

                                @if (! ($closureReadiness['trays_reconciled'] ?? false))
                                    <div class="p-3 rounded-xl bg-[#FEF7EC] border border-[#F7D7A3] text-xs text-[#885D1C] space-y-1">
                                        <span class="font-bold block">Prasyarat Penutupan Belum Terpenuhi:</span>
                                        <p class="text-[11px] leading-relaxed">
                                            Rekonsiliasi fisik ompreng (Tahap 2) wajib diselesaikan sebelum menutup perjalanan.
                                        </p>
                                    </div>
                                @elseif ($closureReadiness['has_open_incidents'] ?? false)
                                    <div class="p-3.5 rounded-xl bg-[#FDF2F2] border border-[#F5C2C2] text-xs text-[#9B3232] space-y-1.5">
                                        <span class="font-bold flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-[#9B3232] shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            <span>Pemisahan Kewenangan (Separation of Duties)</span>
                                        </span>
                                        <p class="text-[11px] leading-relaxed">
                                            Terdapat tiket insiden terbuka (OPEN) pada perjalanan ini. Penutupan administratif wajib disahkan langsung oleh <strong>Kepala SPPG</strong> melalui halaman Dossier Trip (C03).
                                        </p>
                                        <a href="{{ route('trips.show', $trip) }}" class="min-h-[40px] inline-flex items-center gap-1.5 text-xs font-bold text-[#1C6654] hover:underline pt-1">
                                            <span>Buka Dossier Trip di C03</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                        </a>
                                    </div>
                                @else
                                    <p class="text-[11px] text-[#53645B] leading-relaxed">
                                        Penutupan ini mengunci seluruh manifes distribusi, menonaktifkan telemetri GPS secara permanen (OFFLINE), dan mencatat milestone penutupan server.
                                    </p>
                                    <form action="{{ route('driver.trips.complete', $trip) }}" method="POST" class="space-y-3"
                                          onsubmit="return confirm('Apakah Anda yakin ingin menyelesaikan dan menutup perjalanan ini secara resmi? Data distribusi akan dikunci dan sesi GPS dihentikan.');">
                                        @csrf
                                        <div>
                                            <label for="completion_notes" class="block text-[11px] font-semibold text-[#53645B] mb-1">Catatan Penutupan Kurir (Opsional):</label>
                                            <textarea name="completion_notes" id="completion_notes" rows="2" placeholder="Catatan akhir operasional kurir..." class="w-full text-xs p-3 rounded-xl border border-[#DCE5DD] bg-white text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654]"></textarea>
                                        </div>
                                        <button type="submit" class="min-h-[48px] w-full py-3 px-4 rounded-xl font-bold text-sm bg-[#1C6654] text-white hover:bg-[#164D42] smooth-transition subtle-shadow flex items-center justify-center gap-2 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span>Tutup &amp; Selesaikan Perjalanan Resmi</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </section>

                @elseif ($activeStop)
                    <!-- Primary Active Stop Card (Dominant First-Fold Action on Mobile) -->
                    <section aria-label="Tujuan Utama Aktif" class="p-5 rounded-2xl bg-[#EDF7F2] border-2 border-[#1C6654] space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-[#BCE2D0]">
                            <div class="flex items-center gap-2">
                                <span class="w-7 h-7 rounded-xl bg-[#1C6654] text-white text-xs font-bold flex items-center justify-center tabular-nums">
                                    {{ $activeStop->sequence_number }}
                                </span>
                                <span class="eyebrow-label text-[#1C6654]">Tujuan Utama Aktif</span>
                            </div>
                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-lg border {{ $activeStop->status->badgeClass() }}">
                                {{ $activeStop->status->label() }}
                            </span>
                        </div>

                        <div>
                            <h3 class="text-base sm:text-lg font-bold text-[#172923]">
                                {{ $activeStop->school->name }}
                            </h3>
                            <p class="text-xs text-[#53645B] mt-1 leading-relaxed">
                                {{ $activeStop->school->address }}
                            </p>
                        </div>

                        <div class="p-3.5 rounded-xl bg-white border border-[#BCE2D0] text-xs space-y-1.5">
                            <div class="flex justify-between">
                                <span class="text-[#53645B]">Muatan Direncanakan:</span>
                                <span class="font-bold text-[#172923] tabular-nums">{{ $activeStop->planned_portions ?: $activeStop->total_loaded_portions }} Porsi</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#53645B]">Muatan Terverifikasi:</span>
                                <span class="font-bold text-[#21684B] tabular-nums">{{ $activeStop->loaded_portions ?: $activeStop->total_loaded_portions }} Porsi</span>
                            </div>
                        </div>

                        @if (in_array($activeStop->status, [\App\Enums\TripStopStatus::PENDING, \App\Enums\TripStopStatus::EN_ROUTE], true))
                            <form action="{{ route('driver.trips.stops.arrive', [$trip, $activeStop]) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="min-h-[48px] w-full py-3 px-4 rounded-xl font-bold text-sm bg-[#1C6654] text-white hover:bg-[#164D42] smooth-transition subtle-shadow flex items-center justify-center gap-2 cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Tiba di {{ $activeStop->school->name }}</span>
                                </button>
                            </form>
                        @elseif (in_array($activeStop->status, [\App\Enums\TripStopStatus::ARRIVED, \App\Enums\TripStopStatus::ARRIVED_AWAITING_CONFIRMATION], true))
                            <div class="p-4 rounded-xl bg-[#FEF7EC] border border-[#F7D7A3] text-[#885D1C] text-xs space-y-3">
                                <div>
                                    <span class="font-bold block mb-0.5">Armada Telah Tiba</span>
                                    <span>Menunggu pemeriksaan organoleptik dan konfirmasi serah terima dari PIC Sekolah.</span>
                                </div>
                                <a href="{{ route('driver.trips.stops.handover', [$trip, $activeStop]) }}"
                                   class="min-h-[48px] inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 rounded-xl font-bold text-xs bg-[#1C6654] text-white hover:bg-[#164D42] smooth-transition subtle-shadow">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span>Lembar Serah Terima &amp; Unggah Foto (D04)</span>
                                </a>
                            </div>
                        @else
                            <div class="p-3.5 rounded-xl bg-white border border-[#BCE2D0] text-[#21684B] text-xs font-medium text-center">
                                <span class="font-bold block mb-0.5">Serah Terima Selesai</span>
                                Pemberhentian ini telah diselesaikan.
                            </div>
                        @endif
                    </section>
                @endif

                <!-- All Stops Sequence Summary -->
                <section aria-labelledby="all-stops-heading" class="luxury-surface p-5">
                    <h3 id="all-stops-heading" class="eyebrow-label mb-3">
                        Urutan Rute Seluruh Sekolah ({{ $stops->count() }})
                    </h3>

                    <div class="space-y-2.5">
                        @foreach ($stops as $stop)
                            <div class="p-3 rounded-xl border {{ $activeStop && $activeStop->id === $stop->id ? 'bg-[#EDF7F2] border-[#1C6654]' : 'inset-surface' }} flex items-center justify-between gap-2 text-xs">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-6 h-6 rounded-lg {{ $activeStop && $activeStop->id === $stop->id ? 'bg-[#1C6654] text-white' : 'bg-[#EDF2ED] text-[#172923]' }} text-[11px] font-bold flex items-center justify-center shrink-0 tabular-nums">
                                        {{ $stop->sequence_number }}
                                    </span>
                                    <div class="min-w-0">
                                        <span class="font-bold text-[#172923] block truncate">{{ $stop->school->name }}</span>
                                        <span class="text-[11px] text-[#53645B] tabular-nums">{{ $stop->loaded_portions ?: $stop->planned_portions }} Porsi</span>
                                    </div>
                                </div>
                                <span class="text-[10px] px-2 py-0.5 rounded-md border font-semibold shrink-0 {{ $stop->status->badgeClass() }}">
                                    {{ $stop->status->label() }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- Driver Geolocation Telemetry & Dispatch Map -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.initDispatchMap) {
                window.initDispatchMap('driver-active-map', {
                    dataUrl: '{{ route('trips.map-data', $trip) }}'
                });
            }

            @if (!$trip->isCompleted())
            const telemetryStartUrl = '{{ route('driver.trips.telemetry.start', $trip) }}';
            const telemetryUrl = '{{ route('driver.trips.telemetry', $trip) }}';
            const telemetryStopUrl = '{{ route('driver.trips.telemetry.stop', $trip) }}';
            const telemetryStatusUrl = '{{ route('driver.trips.telemetry.status', $trip) }}';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const statusText = document.getElementById('gps-status-text');
            const statusDetail = document.getElementById('gps-status-detail');
            const accuracyDisplay = document.getElementById('gps-accuracy-display');
            const btnToggleGps = document.getElementById('btn-toggle-gps');
            const btnGpsLabel = document.getElementById('btn-gps-label');
            const indicatorDot = document.getElementById('gps-indicator-dot');

            let watchId = null;
            let lastSentTime = 0;
            let currentSessionId = null;
            let isSyncingStop = false;

            function setButtonState(mode) {
                if (mode === 'active') {
                    btnGpsLabel.textContent = 'Hentikan Berbagi Lokasi';
                    btnToggleGps.className = 'min-h-[44px] w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-bold bg-[#9B3232] text-white hover:bg-[#7D2828] smooth-transition flex items-center justify-center gap-2 subtle-shadow cursor-pointer';
                    btnToggleGps.disabled = false;
                } else if (mode === 'syncing') {
                    btnGpsLabel.textContent = 'Menyinkronkan...';
                    btnToggleGps.className = 'min-h-[44px] w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-bold bg-[#885D1C] text-white opacity-80 smooth-transition flex items-center justify-center gap-2 subtle-shadow cursor-wait';
                    btnToggleGps.disabled = true;
                } else if (mode === 'retry_sync') {
                    btnGpsLabel.textContent = 'Coba Sinkronkan Ulang';
                    btnToggleGps.className = 'min-h-[44px] w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-bold bg-[#885D1C] text-white hover:bg-[#6E4A15] smooth-transition flex items-center justify-center gap-2 subtle-shadow cursor-pointer';
                    btnToggleGps.disabled = false;
                } else {
                    btnGpsLabel.textContent = 'Mulai Bagikan Lokasi';
                    btnToggleGps.className = 'min-h-[44px] w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-bold bg-[#1C6654] text-white hover:bg-[#164D42] smooth-transition flex items-center justify-center gap-2 subtle-shadow cursor-pointer';
                    btnToggleGps.disabled = false;
                }
            }

            function syncStopToServer(stoppedAtIso, sessionId, retryCount = 0) {
                isSyncingStop = true;
                setButtonState('syncing');

                statusText.textContent = 'Sensor Dimatikan (Menyinkronkan...)';
                statusText.className = 'text-xs font-semibold px-2.5 py-0.5 rounded-md border bg-[#FEF7EC] text-[#885D1C] border-[#F7D7A3]';
                statusDetail.textContent = 'Pengambilan GPS lokal telah dihentikan demi privasi. Sedang menyinkronkan status penghentian ke server...';
                indicatorDot.className = 'w-3 h-3 rounded-full bg-[#885D1C] shrink-0';

                fetch(telemetryStopUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        stopped_at: stoppedAtIso,
                        session_id: sessionId
                    })
                })
                .then(async (res) => {
                    const contentType = res.headers.get('content-type') || '';
                    if (!res.ok || !contentType.includes('application/json')) {
                        throw new Error(`Server returned HTTP ${res.status}`);
                    }
                    return res.json();
                })
                .then((data) => {
                    isSyncingStop = false;
                    if (watchId !== null) return;

                    if (data.success) {
                        statusText.textContent = 'Berbagi Lokasi Dihentikan (Terkonfirmasi Server)';
                        statusText.className = 'text-xs font-semibold px-2.5 py-0.5 rounded-md border bg-[#FAFBF9] text-[#53645B] border-[#DCE5DD]';
                        statusDetail.textContent = 'Pelacakan dihentikan oleh kurir dan telah dikonfirmasi oleh server SPPG.';
                        indicatorDot.className = 'w-3 h-3 rounded-full bg-[#53645B] shrink-0';
                        if (accuracyDisplay) accuracyDisplay.textContent = '';
                        setButtonState('idle');
                    } else {
                        throw new Error(data.message || 'Server did not acknowledge stop.');
                    }
                })
                .catch((err) => {
                    console.warn('Gagal sinkronisasi penghentian GPS ke server:', err);
                    if (watchId !== null) {
                        isSyncingStop = false;
                        return;
                    }

                    reconcileServerStopStatus(stoppedAtIso, sessionId, retryCount);
                });
            }

            function reconcileServerStopStatus(stoppedAtIso, sessionId, retryCount) {
                statusText.textContent = 'Sensor Dimatikan (Memeriksa Ulang Status...)';
                statusText.className = 'text-xs font-semibold px-2.5 py-0.5 rounded-md border bg-[#FEF7EC] text-[#885D1C] border-[#F7D7A3]';
                statusDetail.textContent = 'Sensor GPS lokal mati. Memeriksa ulang rekonsiliasi status ke server SPPG...';

                fetch(telemetryStatusUrl, {
                    headers: { 'Accept': 'application/json' }
                })
                .then(async (res) => {
                    if (!res.ok) throw new Error(`Reconciliation HTTP ${res.status}`);
                    return res.json();
                })
                .then((statusData) => {
                    isSyncingStop = false;
                    if (watchId !== null) return;

                    if (statusData.is_stopped || statusData.tracking_status === 'SHARING_STOPPED') {
                        statusText.textContent = 'Berbagi Lokasi Dihentikan (Terekonsiliasi Server)';
                        statusText.className = 'text-xs font-semibold px-2.5 py-0.5 rounded-md border bg-[#FAFBF9] text-[#53645B] border-[#DCE5DD]';
                        statusDetail.textContent = 'Pelacakan dihentikan oleh kurir dan berhasil terekonsiliasi dengan server SPPG.';
                        indicatorDot.className = 'w-3 h-3 rounded-full bg-[#53645B] shrink-0';
                        if (accuracyDisplay) accuracyDisplay.textContent = '';
                        setButtonState('idle');
                    } else if (retryCount < 1) {
                        setTimeout(() => syncStopToServer(stoppedAtIso, sessionId, retryCount + 1), 1500);
                    } else {
                        showSyncFailureState(stoppedAtIso, sessionId);
                    }
                })
                .catch(() => {
                    isSyncingStop = false;
                    if (watchId !== null) return;
                    showSyncFailureState(stoppedAtIso, sessionId);
                });
            }

            function showSyncFailureState(stoppedAtIso, sessionId) {
                statusText.textContent = 'Sensor Dimatikan (Sinkronisasi Gagal)';
                statusText.className = 'text-xs font-semibold px-2.5 py-0.5 rounded-md border bg-[#FDF2F2] text-[#9B3232] border-[#F5C2C2]';
                statusDetail.textContent = 'Sensor GPS ponsel tetap mati demi privasi. Sambungan server bermasalah. Klik untuk coba sinkronkan ulang.';
                indicatorDot.className = 'w-3 h-3 rounded-full bg-[#9B3232] shrink-0';
                setButtonState('retry_sync');

                btnToggleGps._lastFailedStop = { stoppedAtIso, sessionId };
            }

            function sendLocation(pos) {
                const sendingSessionId = currentSessionId;
                const now = Date.now();
                if (now - lastSentTime < 10000) return;
                lastSentTime = now;

                const payload = {
                    latitude: pos.coords.latitude,
                    longitude: pos.coords.longitude,
                    accuracy: pos.coords.accuracy,
                    speed: pos.coords.speed,
                    heading: pos.coords.heading,
                    recorded_at: new Date(pos.timestamp).toISOString(),
                    session_id: sendingSessionId
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
                .then(async (res) => {
                    const contentType = res.headers.get('content-type') || '';
                    if (!res.ok || !contentType.includes('application/json')) {
                        throw new Error(`HTTP ${res.status}`);
                    }
                    return res.json();
                })
                .then(data => {
                    if (sendingSessionId !== currentSessionId || watchId === null) {
                        return;
                    }
                    if (data.tracking_status === 'SHARING_STOPPED' || data.is_stale_telemetry) {
                        return;
                    }
                    if (data.success) {
                        statusText.textContent = 'Terhubung (GPS Aktif)';
                        statusText.className = 'text-xs font-semibold px-2.5 py-0.5 rounded-md border bg-[#EDF7F2] text-[#21684B] border-[#BCE2D0]';
                        statusDetail.textContent = 'Data lokasi diperbarui: ' + new Date().toLocaleTimeString('id-ID');
                        indicatorDot.className = 'w-3 h-3 rounded-full bg-[#21684B] shrink-0';
                        if (accuracyDisplay) {
                            accuracyDisplay.textContent = 'Akurasi: ±' + Math.round(pos.coords.accuracy) + 'm';
                        }
                        setButtonState('active');
                    }
                })
                .catch(err => {
                    console.warn('Gagal mengirim koordinat:', err);
                });
            }

            function handleGeoError(err) {
                indicatorDot.className = 'w-3 h-3 rounded-full bg-[#9B3232] shrink-0';
                statusText.className = 'text-xs font-semibold px-2.5 py-0.5 rounded-md border bg-[#FDF2F2] text-[#9B3232] border-[#F5C2C2]';
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
                    statusText.className = 'text-xs font-semibold px-2.5 py-0.5 rounded-md border bg-[#FDF2F2] text-[#9B3232] border-[#F5C2C2]';
                    statusDetail.textContent = 'Perangkat peramban ini tidak mendukung Geolocation API.';
                    return;
                }

                statusText.textContent = 'Menghubungkan ke Server...';
                statusText.className = 'text-xs font-semibold px-2.5 py-0.5 rounded-md border bg-[#FEF7EC] text-[#885D1C] border-[#F7D7A3]';
                statusDetail.textContent = 'Meminta identitas sesi pelacakan resmi dari server SPPG...';
                btnGpsLabel.textContent = 'Menghubungkan...';
                setButtonState('syncing');

                fetch(telemetryStartUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(async (res) => {
                    const contentType = res.headers.get('content-type') || '';
                    if (!res.ok || !contentType.includes('application/json')) {
                        const err = await res.json().catch(() => ({}));
                        throw new Error(err.message || `Gagal otorisasi sesi (HTTP ${res.status})`);
                    }
                    return res.json();
                })
                .then((data) => {
                    if (!data.success || !data.session_id) {
                        throw new Error(data.message || 'Server tidak mengembalikan session_id.');
                    }

                    currentSessionId = data.session_id;

                    statusText.textContent = 'Meminta Izin Lokasi...';
                    statusText.className = 'text-xs font-semibold px-2.5 py-0.5 rounded-md border bg-[#FEF7EC] text-[#885D1C] border-[#F7D7A3]';
                    statusDetail.textContent = 'Harap izinkan akses lokasi pada dialog peramban.';
                    setButtonState('active');

                    watchId = navigator.geolocation.watchPosition(
                        (pos) => {
                            sendLocation(pos);
                        },
                        (err) => {
                            handleGeoError(err);
                        },
                        {
                            enableHighAccuracy: true,
                            maximumAge: 10000,
                            timeout: 20000
                        }
                    );
                })
                .catch((err) => {
                    console.error('Gagal menginisialisasi sesi GPS:', err);
                    statusText.textContent = 'Gagal Memulai Sesi GPS';
                    statusText.className = 'text-xs font-semibold px-2.5 py-0.5 rounded-md border bg-[#FDF2F2] text-[#9B3232] border-[#F5C2C2]';
                    statusDetail.textContent = err.message || 'Tidak dapat menghubungi server untuk otorisasi sesi GPS.';
                    indicatorDot.className = 'w-3 h-3 rounded-full bg-[#9B3232] shrink-0';
                    setButtonState('idle');
                });
            }

            function stopTracking(notify = true) {
                const stoppedSessionId = currentSessionId;
                const stoppedAtIso = new Date().toISOString();

                if (watchId !== null) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                }
                currentSessionId = null;

                if (notify) {
                    syncStopToServer(stoppedAtIso, stoppedSessionId, 0);
                } else {
                    setButtonState('idle');
                    statusText.textContent = 'Berbagi Lokasi Dihentikan';
                    statusText.className = 'text-xs font-semibold px-2.5 py-0.5 rounded-md border bg-[#FAFBF9] text-[#53645B] border-[#DCE5DD]';
                    statusDetail.textContent = 'Pelacakan dihentikan oleh kurir.';
                    indicatorDot.className = 'w-3 h-3 rounded-full bg-[#53645B] shrink-0';
                    if (accuracyDisplay) accuracyDisplay.textContent = '';
                }
            }

            btnToggleGps?.addEventListener('click', function() {
                if (isSyncingStop) return;

                if (btnToggleGps._lastFailedStop) {
                    const failed = btnToggleGps._lastFailedStop;
                    btnToggleGps._lastFailedStop = null;
                    syncStopToServer(failed.stoppedAtIso, failed.sessionId, 0);
                    return;
                }

                if (watchId !== null) {
                    stopTracking(true);
                } else {
                    startTracking();
                }
            });

            window.addEventListener('beforeunload', function() {
                if (watchId !== null) {
                    const leavingSessionId = currentSessionId;
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                    currentSessionId = null;
                    if (navigator.sendBeacon) {
                        const blob = new Blob([JSON.stringify({
                            _token: csrfToken,
                            session_id: leavingSessionId,
                            stopped_at: new Date().toISOString()
                        })], { type: 'application/json' });
                        navigator.sendBeacon(telemetryStopUrl, blob);
                    }
                }
            });
            @endif
        });
    </script>
</x-app-shell>
