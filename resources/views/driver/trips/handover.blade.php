<x-app-shell title="Serah Terima Makanan — {{ $school->name }}">
    <div class="max-w-4xl mx-auto px-4 py-6 sm:px-6 space-y-6">

        <!-- Navigasi Balik ke Perjalanan Aktif -->
        <div class="flex items-center justify-between">
            <a href="{{ route('driver.trips.active', $trip) }}" 
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#53645B] hover:text-[#172923] touch-target">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>Kembali ke Navigasi Rute (D03)</span>
            </a>
            <div class="flex items-center gap-2">
                <span class="text-[11px] font-bold text-[#53645B] uppercase tracking-wider">Halaman D04</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full font-bold {{ $stop->status->badgeClass() }}">
                    {{ $stop->status->label() }}
                </span>
            </div>
        </div>

        <!-- Banner Ringkasan Pemberhentian -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-[#EDF2ED]">
                <div class="flex items-start gap-3.5">
                    <div class="w-9 h-9 rounded-full bg-[#1C6654] text-white flex items-center justify-center text-sm font-bold shrink-0 mt-0.5">
                        {{ $stop->sequence_number }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-lg font-bold text-[#172923]">{{ $school->name }}</h1>
                            <span class="text-[11px] text-[#53645B] bg-[#F5F7F3] px-2 py-0.5 rounded font-medium">NPSN: {{ $school->npsn }}</span>
                        </div>
                        <p class="text-xs text-[#53645B] mt-0.5">{{ $school->address }}</p>
                    </div>
                </div>
                <div class="text-left sm:text-right shrink-0">
                    <span class="text-[11px] text-[#53645B] block font-medium">Waktu Tiba Armada</span>
                    <span class="text-sm font-bold text-[#172923] tabular-nums">
                        {{ $stop->arrived_at ? $stop->arrived_at->setTimezone('Asia/Jakarta')->format('H:i:s \W\I\B') : 'Belum tercatat' }}
                    </span>
                </div>
            </div>

            <!-- Disclaimer Pemisahan Wewenang (Separation of Duties) -->
            <div class="mt-4 p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD] text-xs text-[#53645B] flex items-start gap-2.5">
                <svg class="w-4 h-4 text-[#1C6654] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="leading-relaxed">
                    <strong class="text-[#172923]">Integritas Serah Terima:</strong> Kurir hanya mencatat serah fisik dan mengunggah foto bukti serah terima armada. Jumlah porsi diterima dan kelayakan organoleptik diperiksa serta disahkan secara independen oleh PIC Sekolah di Page P04.
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- Kolom Kiri: Manifest Pengiriman (7 Kolom) -->
            <div class="lg:col-span-7 space-y-6">
                <!-- Manifest Delivery Items -->
                <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-[#EDF2ED]">
                        <h2 class="text-sm font-bold text-[#172923]">Manifest Muatan Dikirim</h2>
                        <span class="text-xs font-bold text-[#1C6654] tabular-nums">
                            Total: {{ $stop->loaded_portions ?: $stop->planned_portions }} Porsi
                        </span>
                    </div>

                    <div class="divide-y divide-[#EDF2ED]">
                        @forelse ($stop->deliveryItems as $item)
                            <div class="py-3 first:pt-0 last:pb-0 flex items-center justify-between text-xs">
                                <div>
                                    <span class="font-bold text-[#172923] block text-sm">
                                        {{ $item->foodBatch?->menu?->recipe_name ?? 'Menu MBG' }}
                                    </span>
                                    <span class="text-[11px] text-[#53645B] font-mono">
                                        Batch: {{ $item->foodBatch?->batch_code ?? '-' }}
                                    </span>
                                </div>
                                <div class="text-right">
                                    <span class="font-bold text-sm text-[#172923] tabular-nums">
                                        {{ $item->loaded_portions }}
                                    </span>
                                    <span class="text-[11px] text-[#53645B] block">Porsi</span>
                                </div>
                            </div>
                        @empty
                            <div class="py-4 text-center text-xs text-[#53645B]">
                                Tidak ada item muatan terdaftar pada pemberhentian ini.
                            </div>
                        @endforelse
                    </div>

                    <!-- Informasi Kendaraan & Trip -->
                    <div class="pt-3 border-t border-[#EDF2ED] flex flex-wrap gap-4 text-xs text-[#53645B]">
                        <div>
                            <span class="text-[#708177]">Nomor Trip:</span>
                            <span class="font-mono font-bold text-[#172923] ml-1">{{ $trip->trip_code }}</span>
                        </div>
                        <div>
                            <span class="text-[#708177]">Armada:</span>
                            <span class="font-bold text-[#172923] ml-1">{{ $trip->vehicle?->plate_number ?? 'Kendaraan SPPG' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Status Verifikasi PIC Sekolah -->
                <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 shadow-xs space-y-3">
                    <h2 class="text-sm font-bold text-[#172923]">Status Konfirmasi PIC Sekolah</h2>

                    @if ($serviceRecord && $serviceRecord->isConfirmed())
                        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 space-y-2">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="font-bold text-xs uppercase tracking-wide">
                                    {{ $serviceRecord->status->label() }}
                                </span>
                            </div>
                            <p class="text-xs leading-relaxed text-emerald-800">
                                Diterima dan diverifikasi oleh PIC <strong>{{ $serviceRecord->receiver?->name ?? 'Sekolah' }}</strong> pada {{ $serviceRecord->received_at?->setTimezone('Asia/Jakarta')->format('d/m/Y H:i \W\I\B') }}.
                            </p>
                            <div class="grid grid-cols-3 gap-2 pt-2 text-center text-xs">
                                <div class="p-2 rounded bg-white/70 border border-emerald-100">
                                    <span class="text-[10px] text-[#53645B] block">Diterima</span>
                                    <span class="font-bold text-emerald-800 tabular-nums">{{ $serviceRecord->received_portions }} Porsi</span>
                                </div>
                                <div class="p-2 rounded bg-white/70 border border-emerald-100">
                                    <span class="text-[10px] text-[#53645B] block">Rusak</span>
                                    <span class="font-bold text-amber-700 tabular-nums">{{ $serviceRecord->damaged_portions }} Porsi</span>
                                </div>
                                <div class="p-2 rounded bg-white/70 border border-emerald-100">
                                    <span class="text-[10px] text-[#53645B] block">Selisih</span>
                                    <span class="font-bold {{ $serviceRecord->shortage_portions > 0 ? 'text-rose-700' : 'text-emerald-800' }} tabular-nums">
                                        {{ $serviceRecord->shortage_portions }} Porsi
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Lanjut ke Stop Berikutnya jika ada -->
                        <div class="pt-2">
                            <a href="{{ route('driver.trips.active', $trip) }}" 
                               class="w-full py-3 px-4 rounded-xl font-bold text-xs bg-[#1C6654] text-white hover:bg-[#154E40] transition-colors shadow-xs flex items-center justify-center gap-2 touch-target">
                                <span>Kembali ke Navigasi Rute Aktif</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </a>
                        </div>
                    @else
                        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 space-y-1.5">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                                <span class="font-bold text-xs">Menunggu Pemeriksaan Fisik PIC Sekolah</span>
                            </div>
                            <p class="text-xs text-amber-800 leading-relaxed">
                                Mohon serahkan wadah makanan kepada petugas PIC Sekolah. PIC akan memverifikasi jumlah ompreng fisik dan memeriksa kualitas makanan (aroma, suhu, tampilan) sebelum mengesahkan serah terima.
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Kolom Kanan: Bukti Foto Serah Terima (5 Kolom) -->
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-[#EDF2ED]">
                        <h2 class="text-sm font-bold text-[#172923]">Bukti Foto Serah Terima</h2>
                        <span class="text-[11px] text-[#53645B] font-medium">Kamera Kurir</span>
                    </div>

                    @php
                        $handoverPhoto = $stop->evidencePhotos
                            ->where('evidence_type', 'SCHOOL_HANDOVER')
                            ->sortByDesc('created_at')
                            ->first();
                    @endphp

                    @if ($handoverPhoto)
                        <!-- Preview Foto yang Sudah Diunggah -->
                        <div class="space-y-3">
                            <div class="relative rounded-xl overflow-hidden border border-[#DCE5DD] bg-slate-100 aspect-video flex items-center justify-center">
                                <img src="{{ route('evidence.show', $handoverPhoto) }}" 
                                     alt="Foto Serah Terima di {{ $school->name }}" 
                                     class="w-full h-full object-cover">
                            </div>
                            <div class="text-xs text-[#53645B] space-y-1 bg-[#F5F7F3] p-3 rounded-xl border border-[#DCE5DD]">
                                <div class="flex justify-between">
                                    <span class="text-[#708177]">Waktu Unggah:</span>
                                    <span class="font-bold text-[#172923] tabular-nums">
                                        {{ $handoverPhoto->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s \W\I\B') }}
                                    </span>
                                </div>
                                @if ($handoverPhoto->notes)
                                    <div class="pt-1 border-t border-[#DCE5DD]">
                                        <span class="text-[#708177] block text-[10px]">Catatan Kurir:</span>
                                        <span class="text-[#172923]">{{ $handoverPhoto->notes }}</span>
                                    </div>
                                @endif
                            </div>
                            <span class="text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-lg flex items-center gap-1.5 font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Bukti foto berhasil tersimpan di server.
                            </span>
                        </div>
                    @else
                        <!-- Form Unggah Foto Bukti Serah Terima -->
                        <form action="{{ route('driver.trips.stops.handover-evidence', [$trip, $stop]) }}" 
                              method="POST" 
                              enctype="multipart/form-data" 
                              class="space-y-4">
                            @csrf

                            <div>
                                <label for="photo" class="block text-xs font-bold text-[#172923] mb-1.5">
                                    Ambil Foto Serah Terima di Titik Drop-off <span class="text-rose-600">*</span>
                                </label>
                                <input type="file" 
                                       name="photo" 
                                       id="photo" 
                                       accept="image/*" 
                                       capture="environment" 
                                       required
                                       class="block w-full text-xs text-[#172923] file:mr-3 file:py-2.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#1C6654] file:text-white hover:file:bg-[#154E40] file:cursor-pointer border border-[#DCE5DD] rounded-xl p-2 bg-[#F5F7F3] focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                <span class="text-[10px] text-[#708177] mt-1 block">
                                    Pastikan wadah makanan dan perwakilan sekolah tampak jelas (Format JPG/PNG, maks 5MB).
                                </span>
                                @error('photo')
                                    <span class="text-xs text-rose-600 font-medium block mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="notes" class="block text-xs font-bold text-[#172923] mb-1.5">
                                    Catatan Serah Terima (Opsional)
                                </label>
                                <textarea name="notes" 
                                          id="notes" 
                                          rows="2" 
                                          placeholder="Contoh: Diterima oleh Pak Budi di ruang guru lantai 1"
                                          class="w-full text-xs text-[#172923] rounded-xl border border-[#DCE5DD] p-2.5 focus:outline-none focus:ring-2 focus:ring-[#1C6654]"></textarea>
                                @error('notes')
                                    <span class="text-xs text-rose-600 font-medium block mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <button type="submit" 
                                    class="w-full py-3 px-4 rounded-xl font-bold text-xs bg-[#1C6654] text-white hover:bg-[#154E40] transition-colors shadow-xs flex items-center justify-center gap-2 touch-target">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                <span>Unggah Bukti Serah Terima</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>

        </div>

    </div>
</x-app-shell>
