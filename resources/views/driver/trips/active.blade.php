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

    @if ($trip->isCompleted())
        <!-- Status GPS: Selesai & Nonaktif Permanen -->
        <div class="mb-6 p-5 rounded-2xl bg-white border border-[#DCE5DD] shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <div class="w-3.5 h-3.5 rounded-full bg-slate-400 shrink-0"></div>
                        <span class="text-sm font-bold text-[#172923]">Sesi Berbagi Lokasi GPS Telah Ditutup</span>
                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-md border bg-slate-100 text-slate-700 border-slate-300">
                            OFFLINE (Perjalanan Selesai)
                        </span>
                    </div>
                    <p class="text-xs text-[#53645B]">
                        Perjalanan ini telah selesai dan ditutup resmi. Pelacakan lokasi otomatis dimatikan demi privasi dan penghematan daya baterai kurir.
                    </p>
                </div>
                <div class="shrink-0">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#EDF7F2] text-[#1C6654] border border-[#BCE2D0]">
                        ✓ Sesi Berakhir Aman
                    </span>
                </div>
            </div>
        </div>
    @else
        <!-- Banner Status & Persetujuan Berbagi GPS (Critical Gate A) -->
        <div id="gps-status-card" class="mb-6 p-5 rounded-2xl bg-white border border-[#DCE5DD] shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <div id="gps-indicator-dot" class="w-3.5 h-3.5 rounded-full bg-slate-400 shrink-0"></div>
                        <span class="text-sm font-bold text-[#172923]">Berbagi Lokasi GPS Perjalanan</span>
                        <span id="gps-status-text" class="text-xs font-semibold px-2.5 py-0.5 rounded-md border bg-slate-100 text-slate-700 border-slate-300">
                            Menunggu Tindakan Kurir (Belum Aktif)
                        </span>
                    </div>
                    <p id="gps-status-detail" class="text-xs text-[#53645B]">
                        Klik tombol di samping untuk mulai membagikan lokasi armada selama pengantaran.
                    </p>
                </div>

                <!-- Kontrol Aksi Jelas Kurir (Critical Gate A) -->
                <div class="shrink-0 flex items-center gap-2">
                    <button type="button" id="btn-toggle-gps" 
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-[#1C6654] text-white hover:bg-[#154E40] transition-colors flex items-center gap-2 shadow-xs touch-target cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span id="btn-gps-label">Mulai Bagikan Lokasi</span>
                    </button>
                </div>
            </div>

            <!-- Penjelasan Transparansi Privasi & Keterbatasan Browser (Critical Gate A) -->
            <div class="mt-4 pt-3.5 border-t border-[#E8EEE9] grid grid-cols-1 md:grid-cols-2 gap-3 text-[11px] text-[#53645B]">
                <div class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-[#1C6654] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span><strong>Privasi & Keamanan:</strong> Lokasi digunakan khusus memantau rute pengantaran. Hanya Kepala SPPG dan sistem berwenang yang dapat melihat koordinat presisi.</span>
                </div>
                <div class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span><strong>Batasan Peramban:</strong> Pelacakan web dapat terhenti jika layar ponsel terkunci. Kurir dapat menghentikan berbagi lokasi sewaktu-waktu.</span>
                </div>
            </div>

            <div id="gps-accuracy-container" class="mt-2 text-right">
                <span id="gps-accuracy-display" class="font-mono text-[11px] text-[#1C6654]"></span>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Kolom Kiri (7 Kolom): Peta Rute Navigasi Aktif -->
        <div class="lg:col-span-7 space-y-6">
            <div class="p-5 rounded-2xl bg-white border border-[#DCE5DD]">
                <div class="flex items-center justify-between pb-3 border-b border-[#DCE5DD] mb-3">
                    <h2 class="text-sm sm:text-base font-bold text-[#172923]">Peta Perjalanan &amp; Posisi Armada</h2>
                    <span class="text-xs font-mono font-bold text-[#1C6654]">
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
                     class="w-full h-80 sm:h-96 rounded-xl overflow-hidden border border-[#DCE5DD] bg-slate-100 mb-3">
                </div>

                <div class="flex items-center justify-between text-[11px] text-[#708177]">
                    <span>Dapur: {{ $trip->sppg->name }}</span>
                    <span>
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
            </div>
        </div>

        <!-- Kolom Kanan (5 Kolom): Pemberhentian Aktif, Kepulangan Armada & Rekonsiliasi -->
        <div class="lg:col-span-5 space-y-6">
            <!-- Banner Jika Perjalanan Telah Selesai -->
            @if ($trip->isCompleted())
                <div class="p-5 rounded-2xl bg-[#EDF7F2] border-2 border-[#1C6654] shadow-xs">
                    <div class="flex items-center gap-2 pb-3 border-b border-[#BCE2D0] mb-3">
                        <span class="w-7 h-7 rounded-full bg-[#1C6654] text-white flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-sm sm:text-base font-bold text-[#172923]">Perjalanan Telah Ditutup Resmi</h3>
                            <span class="text-[11px] text-[#1B573E] font-medium">Status: {{ $trip->status->label() }} (Selesai Operasional)</span>
                        </div>
                    </div>

                    <div class="space-y-2 text-xs text-[#172923] mb-4">
                        <div class="flex justify-between py-1 border-b border-[#DCE5DD]">
                            <span class="text-[#53645B]">Waktu Penyelesaian:</span>
                            <span class="font-bold font-mono">{{ $trip->completed_at?->translatedFormat('d M Y, H:i') ?? '-' }} WIB</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#DCE5DD]">
                            <span class="text-[#53645B]">Ditutup Oleh:</span>
                            <span class="font-bold">{{ $trip->closedBy?->name ?? 'Kurir / Petugas' }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#DCE5DD]">
                            <span class="text-[#53645B]">Ompreng Kembali / Rusak / Hilang:</span>
                            <span class="font-bold font-mono text-[#1C6654]">
                                {{ $trip->total_trays_returned ?? 0 }} / {{ $trip->total_trays_damaged ?? 0 }} / {{ $trip->total_trays_missing ?? 0 }}
                            </span>
                        </div>
                    </div>

                    <a href="{{ route('trips.show', $trip) }}" 
                       class="inline-flex items-center justify-center gap-1.5 w-full min-h-[44px] py-2.5 px-4 rounded-xl font-bold text-xs bg-[#1C6654] text-white hover:bg-[#154E40] transition-colors shadow-xs touch-target">
                        <span>Lihat Dossier Lengkap &amp; Bukti Rekonsiliasi (C03)</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>

            <!-- Card Kepulangan Armada & Rekonsiliasi (PBI-11) saat Seluruh Stop Ditangani -->
            @elseif ($closureReadiness['all_stops_handled'])
                <div class="p-5 rounded-2xl bg-white border-2 border-[#1C6654] shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-[#DCE5DD]">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-full bg-[#1C6654] text-white flex items-center justify-center text-xs font-bold">
                                ✓
                            </span>
                            <div>
                                <h3 class="text-sm sm:text-base font-bold text-[#172923]">Kepulangan &amp; Rekonsiliasi SPPG</h3>
                                <span class="text-[11px] text-[#1C6654] font-semibold">Seluruh {{ $stops->count() }} Sekolah Telah Ditangani</span>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded border bg-amber-50 text-amber-800 border-amber-200">
                            PBI-11
                        </span>
                    </div>

                    <!-- Peringatan Integritas Mode B / Pengecualian Sah -->
                    @if ($closureReadiness['mode_b_unallocated_stops'] > 0)
                        <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs">
                            <span class="font-bold block mb-0.5">Catatan Integritas Data (Mode B):</span>
                            Terdapat {{ $closureReadiness['mode_b_unallocated_stops'] }} sekolah dengan serah terima darurat. Porsi belum teratribusi tetap tercatat jujur dan TIDAK dikonversi menjadi angka 0 palsu.
                        </div>
                    @endif

                    <!-- Tahap 1: Kepulangan Armada ke Pangkalan SPPG -->
                    <div class="p-4 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-[#172923] flex items-center gap-1.5">
                                <span class="w-5 h-5 rounded-full bg-[#172923] text-white text-[10px] flex items-center justify-center font-bold">1</span>
                                Perjalanan Kembali ke SPPG
                            </span>
                            @if ($trip->hasArrivedAtDepot())
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-200">Tiba di Pangkalan</span>
                            @elseif ($trip->isReturning())
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-sky-100 text-sky-800 border border-sky-200">Sedang Perjalanan</span>
                            @else
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-300">Belum Berangkat</span>
                            @endif
                        </div>

                        @if (! $trip->isReturning())
                            <p class="text-[11px] text-[#53645B] leading-relaxed">
                                Seluruh pengantaran selesai. Tekan tombol untuk mengarahkan peta kembali ke Dapur SPPG <strong>{{ $trip->sppg->name }}</strong> dan mencatat waktu awal kepulangan server.
                            </p>
                            <form action="{{ route('driver.trips.return', $trip) }}" method="POST" class="space-y-2.5">
                                @csrf
                                <div>
                                    <label for="return_notes" class="block text-[11px] font-semibold text-[#53645B] mb-1">Catatan Kepulangan (Opsional):</label>
                                    <input type="text" name="return_notes" id="return_notes" placeholder="Contoh: Kondisi armada aman, kembali ke SPPG..." class="w-full text-xs p-2.5 rounded-lg border border-[#DCE5DD] bg-white focus:ring-1 focus:ring-[#1C6654] outline-none">
                                </div>
                                <button type="submit" class="w-full min-h-[44px] py-2.5 px-4 rounded-xl font-bold text-xs bg-[#1C6654] text-white hover:bg-[#154E40] transition-colors shadow-xs flex items-center justify-center gap-2 touch-target cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                    </svg>
                                    <span>Mulai Perjalanan Kembali ke SPPG</span>
                                </button>
                            </form>
                        @elseif (! $trip->hasArrivedAtDepot())
                            <div class="space-y-2 text-xs">
                                <div class="flex items-center justify-between text-[11px] text-[#53645B]">
                                    <span>Waktu Mulai Kembali:</span>
                                    <span class="font-bold font-mono text-[#172923]">{{ $trip->return_started_at?->format('H:i') }} WIB</span>
                                </div>
                                <p class="text-[11px] text-sky-800 leading-relaxed">
                                    Armada sedang dalam perjalanan kembali. Setibanya di pangkalan SPPG, konfirmasikan kedatangan di bawah ini.
                                </p>
                                <form action="{{ route('driver.trips.arrive-depot', $trip) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full min-h-[44px] py-2.5 px-4 rounded-xl font-bold text-xs bg-[#1C6654] text-white hover:bg-[#154E40] transition-colors shadow-xs flex items-center justify-center gap-2 touch-target cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                        <span>Konfirmasi Tiba di Dapur SPPG</span>
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="flex items-center justify-between text-xs p-2.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-900">
                                <span class="font-semibold flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Armada Tiba di SPPG:
                                </span>
                                <span class="font-bold font-mono">{{ $trip->arrived_at_sppg_at?->format('H:i') }} WIB</span>
                            </div>
                        @endif
                    </div>

                    <!-- Tahap 2: Rekonsiliasi Ompreng & Kontainer -->
                    @if ($trip->hasArrivedAtDepot())
                        <div class="p-4 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-[#172923] flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-full bg-[#B78C56] text-white text-[10px] flex items-center justify-center font-bold">2</span>
                                    Rekonsiliasi Tray &amp; Kontainer
                                </span>
                                @if ($trip->trayReturn || $trip->total_trays_returned !== null)
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-200">Terekonsiliasi</span>
                                @else
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-200">Wajib Diisi</span>
                                @endif
                            </div>

                            <p class="text-[11px] text-[#53645B] leading-relaxed">
                                Catat jumlah wadah fisik/ompreng yang diserahkan kembali ke gudang SPPG. Jumlah wadah fisik independen dari jumlah porsi makanan.
                            </p>

                            <form action="{{ route('driver.trips.reconcile-trays', $trip) }}" method="POST" class="space-y-3">
                                @csrf
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <label for="trays_returned" class="block text-[10px] font-bold text-[#1C6654] mb-1">Kembali Baik</label>
                                        <input type="number" name="trays_returned" id="trays_returned" min="0" required
                                               value="{{ old('trays_returned', $trip->total_trays_returned ?? $trip->trayReturn?->trays_returned ?? '') }}" 
                                               class="w-full p-2 text-xs rounded-lg border border-[#DCE5DD] text-center font-bold text-[#1C6654] focus:ring-1 focus:ring-[#1C6654]">
                                    </div>
                                    <div>
                                        <label for="trays_damaged" class="block text-[10px] font-bold text-amber-800 mb-1">Rusak / Retak</label>
                                        <input type="number" name="trays_damaged" id="trays_damaged" min="0" 
                                               value="{{ old('trays_damaged', $trip->total_trays_damaged ?? $trip->trayReturn?->trays_damaged ?? 0) }}" 
                                               class="w-full p-2 text-xs rounded-lg border border-[#DCE5DD] text-center font-bold text-amber-700 focus:ring-1 focus:ring-[#1C6654]">
                                    </div>
                                    <div>
                                        <label for="trays_missing" class="block text-[10px] font-bold text-rose-800 mb-1">Hilang</label>
                                        <input type="number" name="trays_missing" id="trays_missing" min="0" 
                                               value="{{ old('trays_missing', $trip->total_trays_missing ?? $trip->trayReturn?->trays_missing ?? 0) }}" 
                                               class="w-full p-2 text-xs rounded-lg border border-[#DCE5DD] text-center font-bold text-rose-700 focus:ring-1 focus:ring-[#1C6654]">
                                    </div>
                                </div>
                                <div>
                                    <label for="notes" class="block text-[10px] font-semibold text-[#53645B] mb-1">Catatan Kondisi Fisik Ompreng (Opsional):</label>
                                    <input type="text" name="notes" id="notes" 
                                           value="{{ old('notes', $trip->tray_reconciliation_notes ?? $trip->trayReturn?->notes ?? '') }}" 
                                           placeholder="Contoh: 1 tray retak pada engsel pengunci..." 
                                           class="w-full text-xs p-2 rounded-lg border border-[#DCE5DD] bg-white">
                                </div>
                                <button type="submit" class="w-full min-h-[44px] py-2 px-3 rounded-xl font-bold text-xs bg-white border border-[#1C6654] text-[#1C6654] hover:bg-[#F0F7F4] transition-colors shadow-xs touch-target cursor-pointer">
                                    {{ ($trip->trayReturn || $trip->total_trays_returned !== null) ? 'Perbarui Rekonsiliasi Ompreng' : 'Simpan Rekonsiliasi Ompreng' }}
                                </button>
                            </form>
                        </div>

                        <!-- Tahap 3: Penutupan Akhir Perjalanan (Gate 5) -->
                        <div class="p-4 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] space-y-3">
                            <span class="text-xs font-bold text-[#172923] flex items-center gap-1.5">
                                <span class="w-5 h-5 rounded-full bg-[#1C6654] text-white text-[10px] flex items-center justify-center font-bold">3</span>
                                Penutupan Akhir Perjalanan Resmi
                            </span>
                            <p class="text-[11px] text-[#53645B] leading-relaxed">
                                Penutupan ini akan mengunci seluruh manifes distribusi, menonaktifkan telemetri GPS secara permanen (OFFLINE), dan mencatat milestone penutupan server.
                            </p>
                            <form action="{{ route('driver.trips.complete', $trip) }}" method="POST" class="space-y-3"
                                  onsubmit="return confirm('Apakah Anda yakin ingin menyelesaikan dan menutup perjalanan ini secara resmi? Data distribusi akan dikunci dan sesi GPS dihentikan.');">
                                @csrf
                                <div>
                                    <label for="completion_notes" class="block text-[10px] font-semibold text-[#53645B] mb-1">Catatan Penutupan Kurir (Opsional):</label>
                                    <textarea name="completion_notes" id="completion_notes" rows="2" placeholder="Catatan akhir operasional kurir..." class="w-full text-xs p-2.5 rounded-lg border border-[#DCE5DD] bg-white focus:ring-1 focus:ring-[#1C6654] outline-none"></textarea>
                                </div>
                                <button type="submit" class="w-full min-h-[44px] py-3 px-4 rounded-xl font-bold text-sm bg-[#B78C56] text-white hover:bg-[#9E7643] transition-colors shadow-sm flex items-center justify-center gap-2 touch-target cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Tutup &amp; Selesaikan Perjalanan Resmi</span>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

            <!-- 1. Card Pemberhentian Aktif Saat Ini (Jika Belum Selesai Semua) -->
            @elseif ($activeStop)
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
                                    class="w-full min-h-[44px] py-3 rounded-xl font-bold text-sm bg-[#1C6654] text-white hover:bg-[#154E40] transition-colors shadow-sm flex items-center justify-center gap-2 touch-target">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Tiba di {{ $activeStop->school->name }}</span>
                            </button>
                        </form>
                    @elseif (in_array($activeStop->status, [\App\Enums\TripStopStatus::ARRIVED, \App\Enums\TripStopStatus::ARRIVED_AWAITING_CONFIRMATION], true))
                        <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-medium text-center space-y-2.5">
                            <div>
                                <span class="font-bold block mb-0.5">Armada Telah Tiba</span>
                                Menunggu pemeriksaan organoleptik dan konfirmasi serah terima dari pihak PIC Sekolah.
                            </div>
                            <a href="{{ route('driver.trips.stops.handover', [$trip, $activeStop]) }}" 
                               class="inline-flex items-center justify-center gap-1.5 w-full min-h-[44px] py-2.5 px-3 rounded-lg font-bold text-xs bg-[#1C6654] text-white hover:bg-[#154E40] transition-colors shadow-xs touch-target">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span>Lembar Serah Terima & Unggah Foto (D04)</span>
                            </a>
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

            @if (!$trip->isCompleted())
            // Client Geolocation Telemetry Logic
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
                    btnToggleGps.className = 'px-4 py-2 rounded-xl text-xs font-bold bg-rose-700 text-white hover:bg-rose-800 transition-colors flex items-center gap-2 shadow-xs touch-target cursor-pointer';
                    btnToggleGps.disabled = false;
                } else if (mode === 'syncing') {
                    btnGpsLabel.textContent = 'Menyinkronkan...';
                    btnToggleGps.className = 'px-4 py-2 rounded-xl text-xs font-bold bg-amber-600 text-white opacity-80 transition-colors flex items-center gap-2 shadow-xs touch-target cursor-wait';
                    btnToggleGps.disabled = true;
                } else if (mode === 'retry_sync') {
                    btnGpsLabel.textContent = 'Coba Sinkronkan Ulang';
                    btnToggleGps.className = 'px-4 py-2 rounded-xl text-xs font-bold bg-amber-600 text-white hover:bg-amber-700 transition-colors flex items-center gap-2 shadow-xs touch-target cursor-pointer';
                    btnToggleGps.disabled = false;
                } else {
                    btnGpsLabel.textContent = 'Mulai Bagikan Lokasi';
                    btnToggleGps.className = 'px-4 py-2 rounded-xl text-xs font-bold bg-[#1C6654] text-white hover:bg-[#154E40] transition-colors flex items-center gap-2 shadow-xs touch-target cursor-pointer';
                    btnToggleGps.disabled = false;
                }
            }

            function syncStopToServer(stoppedAtIso, sessionId, retryCount = 0) {
                isSyncingStop = true;
                setButtonState('syncing');

                statusText.textContent = 'Sensor Dimatikan (Menyinkronkan...)';
                statusText.className = 'text-xs font-semibold px-2 py-0.5 rounded border bg-amber-50 text-amber-800 border-amber-200';
                statusDetail.textContent = 'Pengambilan GPS lokal telah dihentikan demi privasi. Sedang menyinkronkan status penghentian ke server...';
                indicatorDot.className = 'w-3.5 h-3.5 rounded-full bg-amber-400 animate-pulse shrink-0';

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
                    // If kurir started a NEW session while stop was pending, do not overwrite UI
                    if (watchId !== null) return;

                    if (data.success) {
                        statusText.textContent = 'Berbagi Lokasi Dihentikan (Terkonfirmasi Server)';
                        statusText.className = 'text-xs font-semibold px-2 py-0.5 rounded border bg-slate-100 text-slate-700 border-slate-300';
                        statusDetail.textContent = 'Pelacakan dihentikan oleh kurir dan telah dikonfirmasi oleh server SPPG.';
                        indicatorDot.className = 'w-3.5 h-3.5 rounded-full bg-slate-400 shrink-0';
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

                    // Status reconciliation before reporting permanent failure
                    reconcileServerStopStatus(stoppedAtIso, sessionId, retryCount);
                });
            }

            function reconcileServerStopStatus(stoppedAtIso, sessionId, retryCount) {
                statusText.textContent = 'Sensor Dimatikan (Memeriksa Ulang Status...)';
                statusText.className = 'text-xs font-semibold px-2 py-0.5 rounded border bg-amber-50 text-amber-800 border-amber-200';
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
                        statusText.className = 'text-xs font-semibold px-2 py-0.5 rounded border bg-slate-100 text-slate-700 border-slate-300';
                        statusDetail.textContent = 'Pelacakan dihentikan oleh kurir dan berhasil terekonsiliasi dengan server SPPG.';
                        indicatorDot.className = 'w-3.5 h-3.5 rounded-full bg-slate-400 shrink-0';
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
                statusText.className = 'text-xs font-semibold px-2 py-0.5 rounded border bg-rose-50 text-rose-700 border-rose-200';
                statusDetail.textContent = 'Sensor GPS ponsel tetap mati demi privasi. Sambungan server bermasalah. Klik untuk coba sinkronkan ulang.';
                indicatorDot.className = 'w-3.5 h-3.5 rounded-full bg-rose-400 shrink-0';
                setButtonState('retry_sync');

                btnToggleGps._lastFailedStop = { stoppedAtIso, sessionId };
            }

            function sendLocation(pos) {
                const sendingSessionId = currentSessionId;
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
                    // Stale response protection: Ignore if session changed or stopped
                    if (sendingSessionId !== currentSessionId || watchId === null) {
                        return;
                    }
                    if (data.tracking_status === 'SHARING_STOPPED' || data.is_stale_telemetry) {
                        return;
                    }
                    if (data.success) {
                        statusText.textContent = 'Terhubung (GPS Aktif)';
                        statusText.className = 'text-xs font-semibold px-2 py-0.5 rounded border bg-emerald-50 text-emerald-700 border-emerald-200';
                        statusDetail.textContent = 'Data lokasi diperbarui: ' + new Date().toLocaleTimeString('id-ID');
                        indicatorDot.className = 'w-4 h-4 rounded-full bg-emerald-500 animate-pulse shrink-0';
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

            // Critical Gate A: Tracking ONLY starts upon explicit user click
            // Never automatically invoke startTracking() on DOMContentLoaded!
            function startTracking() {
                if (!navigator.geolocation) {
                    statusText.textContent = 'Browser Tidak Mendukung GPS';
                    statusText.className = 'text-xs font-semibold px-2 py-0.5 rounded border bg-rose-50 text-rose-700 border-rose-200';
                    statusDetail.textContent = 'Perangkat peramban ini tidak mendukung Geolocation API.';
                    return;
                }

                statusText.textContent = 'Menghubungkan ke Server...';
                statusText.className = 'text-xs font-semibold px-2 py-0.5 rounded border bg-amber-50 text-amber-700 border-amber-200';
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
                    statusText.className = 'text-xs font-semibold px-2 py-0.5 rounded border bg-amber-50 text-amber-700 border-amber-200';
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
                    statusText.className = 'text-xs font-semibold px-2 py-0.5 rounded border bg-rose-50 text-rose-700 border-rose-200';
                    statusDetail.textContent = err.message || 'Tidak dapat menghubungi server untuk otorisasi sesi GPS.';
                    indicatorDot.className = 'w-4 h-4 rounded-full bg-rose-500 shrink-0';
                    setButtonState('idle');
                });
            }

            function stopTracking(notify = true) {
                const stoppedSessionId = currentSessionId;
                const stoppedAtIso = new Date().toISOString();

                // 1. Immediate local hardware sensor shutdown for privacy
                if (watchId !== null) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                }
                currentSessionId = null; // Invalidate current session

                if (notify) {
                    syncStopToServer(stoppedAtIso, stoppedSessionId, 0);
                } else {
                    setButtonState('idle');
                    statusText.textContent = 'Berbagi Lokasi Dihentikan';
                    statusText.className = 'text-xs font-semibold px-2 py-0.5 rounded border bg-slate-100 text-slate-700 border-slate-300';
                    statusDetail.textContent = 'Pelacakan dihentikan oleh kurir.';
                    indicatorDot.className = 'w-3.5 h-3.5 rounded-full bg-slate-400 shrink-0';
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

            // Clean up watcher when leaving page and notify server
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
