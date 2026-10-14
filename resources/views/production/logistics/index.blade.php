<x-app-shell title="Logistik & Rekonsiliasi Wadah — MBG">
    <div class="max-w-6xl mx-auto px-4 py-6 sm:px-6 space-y-6">

        <!-- Header Halaman A03 -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-bold text-[#1C6654] uppercase tracking-wider bg-[#EDF2ED] px-2 py-0.5 rounded">
                        Modul Logistik SPPG (A03)
                    </span>
                    <span class="text-xs text-[#53645B] font-medium">Rekonsiliasi Ompreng Balik</span>
                </div>
                <h1 class="text-xl font-bold text-[#172923] mt-1">Penerimaan & Rekonsiliasi Ompreng SPPG</h1>
                <p class="text-xs text-[#53645B] mt-0.5">Petugas: {{ $user->name }} &bull; SPPG: {{ $user->sppg?->name ?? 'SPPG Pusat' }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('incidents.index') }}" 
                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#53645B] hover:text-[#172923] px-3 py-2 rounded-lg border border-[#DCE5DD] bg-white hover:bg-[#F5F7F3] touch-target">
                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>Pusat Insiden (C01)</span>
                </a>
            </div>
        </div>

        <!-- Banner SOP Rekonsiliasi Fisik Depot -->
        <div class="p-4 rounded-2xl bg-white border border-[#DCE5DD] shadow-xs flex items-start gap-3 text-xs text-[#53645B]">
            <svg class="w-5 h-5 text-[#1C6654] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            <div class="leading-relaxed">
                <strong class="text-[#172923]">SOP Rekonsiliasi Depot SPPG:</strong> Saat armada kurir tiba kembali di SPPG, bongkar wadah ompreng dan lakukan penghitungan fisik secara teliti. Klasifikasikan wadah yang <em>Baik (Kembali)</em>, <em>Rusak</em>, dan <em>Hilang</em>. Jika terjadi selisih antara hitungan depot dengan catatan penjemputan kurir, sistem akan otomatis mencatat insiden selisih wadah (Discrepancy) tanpa mengubah catatan fisik asli kurir.
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
                <div class="font-bold">Gagal memproses rekonsiliasi:</div>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- SECTION 1: Ompreng Masuk Menunggu Rekonsiliasi Depot -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-bold text-[#53645B] uppercase tracking-wider">
                    Wadah Masuk Menunggu Pemeriksaan Depot ({{ count($incomingReturns) }})
                </h2>
                <span class="text-xs text-[#708177]">Wadah Diambil Kurir Belum Dihitung SPPG</span>
            </div>

            @forelse ($incomingReturns as $incoming)
                @php
                    $school = $incoming->schoolServiceRecord?->tripStop?->school;
                    $origTrip = $incoming->schoolServiceRecord?->tripStop?->trip;
                @endphp

                <div class="bg-white rounded-2xl border-2 border-indigo-400 p-5 shadow-xs space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-[#EDF2ED]">
                        <div>
                            @if ($incoming->status === \App\Enums\TrayReturnStatus::DISCREPANCY || ($incoming->picked_up_count !== null && $incoming->ready_count !== null && $incoming->picked_up_count !== $incoming->ready_count))
                                <span class="text-[10px] font-bold text-amber-800 uppercase tracking-wider bg-amber-100 border border-amber-300 px-2 py-0.5 rounded">
                                    Dalam Muatan Balik (Selisih Pickup: {{ $incoming->picked_up_count }} vs {{ $incoming->ready_count }})
                                </span>
                            @else
                                <span class="text-[10px] font-bold text-indigo-800 uppercase tracking-wider bg-indigo-100 px-2 py-0.5 rounded">
                                    Dalam Muatan Balik
                                </span>
                            @endif
                            <h3 class="text-base font-bold text-[#172923] mt-1">{{ $school?->name ?? 'Sekolah MBG' }}</h3>
                            <p class="text-xs text-[#53645B]">{{ $school?->address ?? '-' }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-mono font-bold text-[#172923] block">ID Wadah: #{{ $incoming->id }}</span>
                            <span class="text-[11px] text-[#708177] block">Trip: {{ $origTrip?->trip_code ?? '-' }}</span>
                        </div>
                    </div>

                    <!-- Ringkasan Angka Pengambilan Kurir -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center text-xs">
                        <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD]">
                            <span class="text-[10px] text-[#708177] block">Ekspektasi Kirim</span>
                            <span class="text-base font-bold text-[#172923] tabular-nums mt-0.5 block">
                                {{ $incoming->is_expected_verified && $incoming->expected_trays !== null ? $incoming->expected_trays : ($incoming->expected_trays ?? 'T/D') }}
                            </span>
                            <span class="text-[10px] text-[#53645B]">{{ $incoming->is_expected_verified ? 'Wadah Terverifikasi' : 'Belum Terverifikasi' }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD]">
                            <span class="text-[10px] text-[#708177] block">Laporan Siap Sekolah</span>
                            <span class="text-base font-bold text-[#172923] tabular-nums mt-0.5 block">
                                {{ $incoming->ready_count ?? 0 }}
                            </span>
                            <span class="text-[10px] text-[#53645B]">Dihitung PIC</span>
                        </div>
                        <div class="p-3 rounded-xl bg-indigo-50 border border-indigo-200">
                            <span class="text-[10px] text-indigo-800 block font-semibold">Diambil Kurir</span>
                            <span class="text-base font-bold text-indigo-900 tabular-nums mt-0.5 block">
                                {{ $incoming->picked_up_count ?? 0 }}
                            </span>
                            <span class="text-[10px] text-indigo-700">Fisik Muatan Armada</span>
                        </div>
                        <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD]">
                            <span class="text-[10px] text-[#708177] block">Waktu Penjemputan</span>
                            <span class="text-xs font-bold text-[#172923] mt-1 block">
                                {{ $incoming->picked_up_at ? $incoming->picked_up_at->setTimezone('Asia/Jakarta')->format('H:i \W\I\B') : '-' }}
                            </span>
                            <span class="text-[10px] text-[#708177]">Hari Ini</span>
                        </div>
                    </div>

                    @if ($incoming->notes)
                        <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD] text-xs text-[#53645B]">
                            <strong class="text-[#172923]">Catatan Penjemputan Kurir:</strong>
                            <p class="mt-0.5 italic text-[#172923]">"{{ $incoming->notes }}"</p>
                        </div>
                    @endif

                    <!-- Formulir Rekonsiliasi Fisik Depot SPPG -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-300 space-y-3">
                        <div class="flex items-center gap-2 text-xs font-bold text-[#172923]">
                            <svg class="w-4 h-4 text-[#1C6654]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Pencatatan Fisik Ompreng Tiba di SPPG</span>
                        </div>

                        <form action="{{ route('production.tray-returns.receive', $incoming) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                                <div>
                                    <label for="returned_{{ $incoming->id }}" class="block font-semibold text-[#172923] mb-1">
                                        Jumlah Kondisi Baik <span class="text-rose-600">*</span>
                                    </label>
                                    <input type="number" 
                                           id="returned_{{ $incoming->id }}"
                                           name="returned_count" 
                                           min="0" 
                                           value="{{ old('returned_count', $incoming->picked_up_count ?? $incoming->expected_trays) }}" 
                                           required
                                           class="w-full px-3 py-2 rounded-lg border border-[#DCE5DD] bg-white text-[#172923] font-bold text-sm tabular-nums focus:outline-none focus:ring-2 focus:ring-[#1C6654] touch-target">
                                    <span class="text-[10px] text-[#708177] mt-0.5 block">Wadah utuh siap dicuci ulang.</span>
                                </div>
                                <div>
                                    <label for="damaged_{{ $incoming->id }}" class="block font-semibold text-[#172923] mb-1">
                                        Jumlah Rusak/Pecah
                                    </label>
                                    <input type="number" 
                                           id="damaged_{{ $incoming->id }}"
                                           name="damaged_count" 
                                           min="0" 
                                           value="{{ old('damaged_count', (int)$incoming->damaged_count) }}" 
                                           class="w-full px-3 py-2 rounded-lg border border-[#DCE5DD] bg-white text-[#172923] text-sm tabular-nums focus:outline-none focus:ring-2 focus:ring-[#1C6654] touch-target">
                                    <span class="text-[10px] text-[#708177] mt-0.5 block">Wadah retak/rusak saat diperiksa.</span>
                                </div>
                                <div>
                                    <label for="missing_{{ $incoming->id }}" class="block font-semibold text-[#172923] mb-1">
                                        Jumlah Hilang
                                    </label>
                                    <input type="number" 
                                           id="missing_{{ $incoming->id }}"
                                           name="missing_count" 
                                           min="0" 
                                           value="{{ old('missing_count', 0) }}" 
                                           class="w-full px-3 py-2 rounded-lg border border-[#DCE5DD] bg-white text-[#172923] text-sm tabular-nums focus:outline-none focus:ring-2 focus:ring-[#1C6654] touch-target">
                                    <span class="text-[10px] text-[#708177] mt-0.5 block">Wadah tidak ditemukan / kurang.</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                <div>
                                    <label for="receive_notes_{{ $incoming->id }}" class="block font-semibold text-[#172923] mb-1">
                                        Catatan Pemeriksaan Depot
                                    </label>
                                    <input type="text" 
                                           id="receive_notes_{{ $incoming->id }}"
                                           name="notes" 
                                           placeholder="Contoh: Wadah telah dihitung dan diserahkan ke tim cuci" 
                                           class="w-full px-3 py-2 rounded-lg border border-[#DCE5DD] bg-white text-[#172923] text-xs focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                </div>
                                <div>
                                    <label for="receive_photo_{{ $incoming->id }}" class="block font-semibold text-[#172923] mb-1">
                                        Foto Bukti Rekonsiliasi (Opsional)
                                    </label>
                                    <input type="file" 
                                           id="receive_photo_{{ $incoming->id }}"
                                           name="photo" 
                                           accept="image/*"
                                           class="w-full text-xs text-[#53645B] file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-[#1C6654] file:text-white hover:file:bg-[#154d40]">
                                </div>
                            </div>

                            <div class="pt-1">
                                <button type="submit" 
                                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-[#1C6654] text-white font-bold text-xs hover:bg-[#154d40] shadow-xs touch-target transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Simpan Rekonsiliasi Depot</span>
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
                    <p class="font-bold text-[#172923]">Tidak Ada Wadah Menunggu Pemeriksaan</p>
                    <p class="text-[#708177]">Seluruh wadah yang dijemput oleh kurir telah selesai direkonsiliasi di SPPG.</p>
                </div>
            @endforelse
        </div>

        <!-- SECTION 2: Buku Ledger Rekonsiliasi Selesai -->
        <div class="space-y-4 pt-6 border-t border-[#DCE5DD]">
            <h2 class="text-xs font-bold text-[#53645B] uppercase tracking-wider">
                Buku Ledger Rekonsiliasi Ompreng ({{ $completedReturns->total() }})
            </h2>

            <div class="overflow-x-auto bg-white rounded-2xl border border-[#DCE5DD] shadow-xs">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-[#F5F7F3] border-b border-[#DCE5DD] text-[#708177] font-semibold text-[11px] uppercase tracking-wider">
                            <th class="py-3 px-4">ID & Sekolah</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Kirim</th>
                            <th class="py-3 px-4 text-center">Diambil</th>
                            <th class="py-3 px-4 text-center">Kembali Baik</th>
                            <th class="py-3 px-4 text-center">Rusak / Hilang</th>
                            <th class="py-3 px-4">Waktu Kembali</th>
                            <th class="py-3 px-4 text-right">Aksi / Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EDF2ED]">
                        @forelse ($completedReturns as $record)
                            @php
                                $school = $record->schoolServiceRecord?->tripStop?->school;
                            @endphp
                            <tr class="hover:bg-[#F5F7F3]/50 transition-colors">
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-[#172923] block">{{ $school?->name ?? 'Sekolah MBG' }}</span>
                                    <span class="font-mono text-[10px] text-[#708177]">Wadah #{{ $record->id }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="text-[11px] px-2.5 py-0.5 rounded-full font-bold border {{ $record->status->badgeClass() }}">
                                        {{ $record->status->label() }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold text-[#172923] tabular-nums">
                                    {{ $record->expected_trays }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold text-indigo-900 tabular-nums">
                                    {{ $record->picked_up_count ?? '—' }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold text-emerald-900 tabular-nums">
                                    {{ $record->returned_count ?? '—' }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold {{ ($record->damaged_count > 0 || $record->missing_count > 0) ? 'text-rose-700' : 'text-[#708177]' }} tabular-nums">
                                    {{ (int)$record->damaged_count }} / {{ (int)$record->missing_count }}
                                </td>
                                <td class="py-3.5 px-4 text-[#53645B] text-[11px]">
                                    {{ $record->returned_at ? $record->returned_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    @if ($record->incidents->isNotEmpty())
                                        <a href="{{ route('incidents.show', $record->incidents->first()) }}" 
                                           class="inline-flex items-center gap-1 text-[11px] px-2 py-1 rounded bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 font-semibold">
                                            <span>Lihat Insiden</span>
                                        </a>
                                    @else
                                        <span class="text-[10px] text-emerald-700 font-medium">Cocok</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-6 text-[#708177] italic">
                                    Belum ada rekonsiliasi yang diselesaikan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pt-2">
                {{ $completedReturns->links() }}
            </div>
        </div>

    </div>
</x-app-shell>
