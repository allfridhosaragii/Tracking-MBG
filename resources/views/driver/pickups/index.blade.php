<x-app-shell title="Pengambilan Ompreng — Kurir MBG">
    <div class="max-w-5xl mx-auto px-4 py-6 sm:px-6 space-y-6">

        <!-- Header Halaman D05 -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-bold text-[#1C6654] uppercase tracking-wider bg-[#EDF2ED] px-2 py-0.5 rounded">
                        Modul Kurir (D05)
                    </span>
                    <span class="text-xs text-[#53645B] font-medium">Armada Logistik Balik</span>
                </div>
                <h1 class="text-xl font-bold text-[#172923] mt-1">Penjemputan Wadah & Ompreng</h1>
                <p class="text-xs text-[#53645B] mt-0.5">Kurir: {{ $driver->name }} &bull; Unit SPPG: {{ $driver->sppg?->name ?? 'SPPG Pusat' }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('driver.trips.index') }}" 
                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#53645B] hover:text-[#172923] px-3 py-2 rounded-lg border border-[#DCE5DD] bg-white hover:bg-[#F5F7F3] touch-target">
                    <svg class="w-4 h-4 text-[#1C6654]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Daftar Rute Pengiriman</span>
                </a>
            </div>
        </div>

        <!-- Banner Info SOP Kurir -->
        <div class="p-4 rounded-2xl bg-white border border-[#DCE5DD] shadow-xs flex items-start gap-3 text-xs text-[#53645B]">
            <svg class="w-5 h-5 text-[#1C6654] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <div class="leading-relaxed">
                <strong class="text-[#172923]">SOP Penjemputan Ompreng:</strong> Hitung fisik wadah bersama PIC Sekolah sebelum dimuat ke dalam armada. Konfirmasikan jumlah aktual yang diangkut. Jika jumlah fisik berbeda dari yang dilaporkan sekolah, masukkan jumlah riil dan sertakan catatan selisih beserta foto bukti.
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 space-y-1">
                <div class="font-bold">Gagal memproses penjemputan ompreng:</div>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- SECTION 1: Tugas Penjemputan Siap Dijemput -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-bold text-[#53645B] uppercase tracking-wider">
                    Sekolah Menunggu Penjemputan Wadah ({{ count($pendingPickups) }})
                </h2>
                <span class="text-xs text-[#708177]">Prioritas Berdasarkan Waktu Siap</span>
            </div>

            @forelse ($pendingPickups as $tray)
                @php
                    $school = $tray->schoolServiceRecord?->tripStop?->school;
                    $origTrip = $tray->schoolServiceRecord?->tripStop?->trip;
                @endphp

                <div class="bg-white rounded-2xl border-2 border-amber-400 p-5 shadow-xs space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-[#EDF2ED]">
                        <div>
                            <span class="text-[10px] font-bold text-amber-800 uppercase tracking-wider bg-amber-100 px-2 py-0.5 rounded">
                                Siap Diambil
                            </span>
                            <h3 class="text-base font-bold text-[#172923] mt-1">{{ $school?->name ?? 'Sekolah MBG' }}</h3>
                            <p class="text-xs text-[#53645B]">{{ $school?->address ?? '-' }}</p>
                        </div>
                        <div class="text-right sm:text-right">
                            <span class="text-xs font-mono font-bold text-[#172923] block">ID Wadah: #{{ $tray->id }}</span>
                            <span class="text-[11px] text-[#708177] block">Trip Kirim: {{ $origTrip?->trip_code ?? '-' }}</span>
                        </div>
                    </div>

                    <!-- Ringkasan Angka Pelaporan Sekolah -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center text-xs">
                        <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD]">
                            <span class="text-[10px] text-[#708177] block">Terkirim Awal</span>
                            <span class="text-base font-bold text-[#172923] tabular-nums mt-0.5 block">
                                {{ $tray->expected_trays }}
                            </span>
                            <span class="text-[10px] text-[#53645B]">Wadah</span>
                        </div>
                        <div class="p-3 rounded-xl bg-amber-50 border border-amber-200">
                            <span class="text-[10px] text-amber-800 block font-semibold">Siap Fisik Sekolah</span>
                            <span class="text-base font-bold text-amber-900 tabular-nums mt-0.5 block">
                                {{ $tray->ready_count ?? 0 }}
                            </span>
                            <span class="text-[10px] text-amber-700">Dihitung PIC</span>
                        </div>
                        <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD]">
                            <span class="text-[10px] text-[#708177] block">Laporan Rusak</span>
                            <span class="text-base font-bold text-[#172923] tabular-nums mt-0.5 block">
                                {{ (int) $tray->damaged_count }}
                            </span>
                            <span class="text-[10px] text-[#708177]">Wadah Pecah/Cacat</span>
                        </div>
                        <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD]">
                            <span class="text-[10px] text-[#708177] block">Waktu Dikonfirmasi</span>
                            <span class="text-xs font-bold text-[#172923] mt-1 block">
                                {{ $tray->ready_at ? $tray->ready_at->setTimezone('Asia/Jakarta')->format('H:i \W\I\B') : '-' }}
                            </span>
                            <span class="text-[10px] text-[#708177]">Hari Ini</span>
                        </div>
                    </div>

                    @if ($tray->notes)
                        <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD] text-xs text-[#53645B]">
                            <strong class="text-[#172923]">Catatan dari PIC Sekolah:</strong>
                            <p class="mt-0.5 italic text-[#172923]">"{{ $tray->notes }}"</p>
                        </div>
                    @endif

                    <!-- Formulir Konfirmasi Penjemputan Fisik oleh Kurir -->
                    <div class="p-4 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD] space-y-3">
                        <div class="flex items-center gap-2 text-xs font-bold text-[#172923]">
                            <svg class="w-4 h-4 text-[#1C6654]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            <span>Input Fisik Muatan Balik</span>
                        </div>

                        <form action="{{ route('driver.tray-returns.pickup', $tray) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                <div>
                                    <label for="pickup_count_{{ $tray->id }}" class="block font-semibold text-[#172923] mb-1">
                                        Jumlah Wadah Dimuat ke Mobil <span class="text-rose-600">*</span>
                                    </label>
                                    <input type="number" 
                                           id="pickup_count_{{ $tray->id }}"
                                           name="picked_up_count" 
                                           min="0" 
                                           value="{{ old('picked_up_count', $tray->ready_count ?? $tray->expected_trays) }}" 
                                           required
                                           class="w-full px-3 py-2 rounded-lg border border-[#DCE5DD] bg-white text-[#172923] font-bold text-sm tabular-nums focus:outline-none focus:ring-2 focus:ring-[#1C6654] touch-target">
                                    <span class="text-[10px] text-[#708177] mt-0.5 block">Jumlah fisik ompreng yang riil dibawa armada kurir.</span>
                                </div>
                                <div>
                                    <label for="pickup_photo_{{ $tray->id }}" class="block font-semibold text-[#172923] mb-1">
                                        Foto Muatan di Mobil (Opsional)
                                    </label>
                                    <input type="file" 
                                           id="pickup_photo_{{ $tray->id }}"
                                           name="photo" 
                                           accept="image/*"
                                           class="w-full text-xs text-[#53645B] file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-[#1C6654] file:text-white hover:file:bg-[#154d40]">
                                </div>
                            </div>

                            <div class="text-xs">
                                <label for="pickup_notes_{{ $tray->id }}" class="block font-semibold text-[#172923] mb-1">
                                    Catatan Serah Terima Fisik (Wajib jika berbeda dari laporan sekolah)
                                </label>
                                <input type="text" 
                                       id="pickup_notes_{{ $tray->id }}"
                                       name="notes" 
                                       placeholder="Contoh: 1 ompreng tertinggal di lab, atau kondisi tumpukan wadah basah" 
                                       class="w-full px-3 py-2 rounded-lg border border-[#DCE5DD] bg-white text-[#172923] text-xs focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                            </div>

                            <div class="pt-1">
                                <button type="submit" 
                                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-[#1C6654] text-white font-bold text-xs hover:bg-[#154d40] shadow-xs touch-target transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Konfirmasi Wadah Berhasil Diambil</span>
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            @empty
                <div class="p-8 text-center bg-white rounded-2xl border border-[#DCE5DD] text-xs text-[#53645B] space-y-2">
                    <svg class="w-10 h-10 text-[#708177] mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="font-bold text-[#172923]">Tidak Ada Antrean Penjemputan</p>
                    <p class="text-[#708177]">Saat ini belum ada sekolah yang menandai wadah ompreng siap dijemput.</p>
                </div>
            @endforelse
        </div>

        <!-- SECTION 2: Riwayat Pengambilan Terakhir -->
        <div class="space-y-4 pt-6 border-t border-[#DCE5DD]">
            <h2 class="text-xs font-bold text-[#53645B] uppercase tracking-wider">
                Riwayat Pengambilan Wadah Sebelumnya ({{ $completedPickups->total() }})
            </h2>

            <div class="space-y-3">
                @forelse ($completedPickups as $completed)
                    @php
                        $school = $completed->schoolServiceRecord?->tripStop?->school;
                    @endphp
                    <div class="p-4 rounded-xl bg-white border border-[#DCE5DD] shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-[#172923] text-sm">{{ $school?->name ?? 'Sekolah MBG' }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full font-bold border {{ $completed->status->badgeClass() }}">
                                    {{ $completed->status->label() }}
                                </span>
                            </div>
                            <div class="text-[#53645B] text-[11px] flex flex-wrap gap-x-3">
                                <span>Waktu Ambil: <strong>{{ $completed->picked_up_at ? $completed->picked_up_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') : '-' }}</strong></span>
                                <span>Diambil: <strong>{{ $completed->picked_up_count ?? 0 }} wadah</strong></span>
                                @if ($completed->returned_count !== null)
                                    <span>Tiba SPPG: <strong>{{ $completed->returned_count }} wadah</strong></span>
                                @endif
                            </div>
                            @if ($completed->notes)
                                <p class="text-[11px] text-[#708177] italic">"{{ $completed->notes }}"</p>
                            @endif
                        </div>

                        @if ($completed->evidencePhotos->isNotEmpty())
                            <div class="flex gap-1.5 shrink-0">
                                @foreach ($completed->evidencePhotos as $photo)
                                    <a href="{{ route('evidence-photos.show', $photo) }}" target="_blank" 
                                       class="w-10 h-10 rounded border border-[#DCE5DD] overflow-hidden bg-slate-100 flex items-center justify-center hover:ring-2 hover:ring-[#1C6654]">
                                        <img src="{{ route('evidence-photos.show', $photo) }}" alt="Bukti" class="w-full h-full object-cover">
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-[#708177] italic text-center py-4 bg-white rounded-xl border border-[#DCE5DD]">
                        Belum ada riwayat penjemputan sebelumnya.
                    </p>
                @endforelse

                <div class="pt-2">
                    {{ $completedPickups->links() }}
                </div>
            </div>
        </div>

    </div>
</x-app-shell>
