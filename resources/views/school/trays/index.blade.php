<x-app-shell title="Manajemen Ompreng & Wadah — {{ $school->name }}">
    <div class="max-w-5xl mx-auto px-4 py-6 sm:px-6 space-y-6">

        <!-- Header Halaman P06 -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-bold text-[#1C6654] uppercase tracking-wider bg-[#EDF2ED] px-2 py-0.5 rounded">
                        Modul PIC Sekolah (P06)
                    </span>
                    <span class="text-xs text-[#53645B] font-medium">NPSN: {{ $school->npsn }}</span>
                </div>
                <h1 class="text-xl font-bold text-[#172923] mt-1">Pelacakan & Kesiapan Ompreng</h1>
                <p class="text-xs text-[#53645B] mt-0.5">{{ $school->name }} &mdash; {{ $school->address }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('school.receipts.index') }}" 
                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#53645B] hover:text-[#172923] px-3 py-2 rounded-lg border border-[#DCE5DD] bg-white hover:bg-[#F5F7F3] touch-target">
                    <svg class="w-4 h-4 text-[#1C6654]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span>Penerimaan Makanan (P04)</span>
                </a>
            </div>
        </div>

        <!-- Banner Panduan PIC Sekolah -->
        <div class="p-4 rounded-2xl bg-white border border-[#DCE5DD] shadow-xs flex items-start gap-3 text-xs text-[#53645B]">
            <svg class="w-5 h-5 text-[#1C6654] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="leading-relaxed">
                <strong class="text-[#172923]">SOP Ompreng Balik (Reverse Logistics):</strong> Setelah jam makan siang selesai dan wadah makanan telah dibersihkan/dikumpulkan, PIC Sekolah wajib menghitung fisik wadah yang ada dan menandai status <em>Siap Dijemput</em>. Kurir SPPG hanya dapat mengambil wadah setelah konfirmasi kesiapan diterbitkan.
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
                <div class="font-bold">Gagal memproses data ompreng:</div>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Metrics Ribbon -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-center">
            <div class="p-3.5 rounded-2xl bg-white border border-[#DCE5DD] shadow-xs">
                <span class="text-[10px] font-bold text-[#708177] uppercase tracking-wider block">Dalam Penggunaan</span>
                <span class="text-xl font-bold text-[#172923] tabular-nums mt-1 block">{{ $metrics['in_use'] }}</span>
                <span class="text-[10px] text-[#53645B]">Wadah di sekolah</span>
            </div>
            <div class="p-3.5 rounded-2xl bg-amber-50/50 border border-amber-200 shadow-xs">
                <span class="text-[10px] font-bold text-amber-800 uppercase tracking-wider block">Siap Dijemput</span>
                <span class="text-xl font-bold text-amber-900 tabular-nums mt-1 block">{{ $metrics['ready'] }}</span>
                <span class="text-[10px] text-amber-700">Menunggu kurir</span>
            </div>
            <div class="p-3.5 rounded-2xl bg-indigo-50/50 border border-indigo-200 shadow-xs">
                <span class="text-[10px] font-bold text-indigo-800 uppercase tracking-wider block">Diambil Kurir</span>
                <span class="text-xl font-bold text-indigo-900 tabular-nums mt-1 block">{{ $metrics['picked_up'] }}</span>
                <span class="text-[10px] text-indigo-700">Dalam perjalanan</span>
            </div>
            <div class="p-3.5 rounded-2xl bg-emerald-50/50 border border-emerald-200 shadow-xs">
                <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider block">Kembali SPPG</span>
                <span class="text-xl font-bold text-emerald-900 tabular-nums mt-1 block">{{ $metrics['returned'] }}</span>
                <span class="text-[10px] text-emerald-700">Tiba di depot</span>
            </div>
            <div class="p-3.5 rounded-2xl bg-rose-50/50 border border-rose-200 shadow-xs">
                <span class="text-[10px] font-bold text-rose-800 uppercase tracking-wider block">Ada Selisih</span>
                <span class="text-xl font-bold text-rose-900 tabular-nums mt-1 block">{{ $metrics['discrepancy'] }}</span>
                <span class="text-[10px] text-rose-700">Perlu penanganan</span>
            </div>
        </div>

        <!-- Daftar Siklus Ompreng -->
        <div class="space-y-4">
            <h2 class="text-xs font-bold text-[#53645B] uppercase tracking-wider">
                Daftar Riwayat & Status Wadah ({{ $trayReturns->total() }})
            </h2>

            @forelse ($trayReturns as $tray)
                @php
                    $serviceRecord = $tray->schoolServiceRecord;
                    $tripStop = $serviceRecord?->tripStop;
                    $deliveryTrip = $tripStop?->trip;
                    $isInUse = $tray->status === \App\Enums\TrayReturnStatus::IN_USE;
                    $isDiscrepancy = $tray->status === \App\Enums\TrayReturnStatus::DISCREPANCY;
                @endphp

                <div class="bg-white rounded-2xl border {{ $isInUse ? 'border-amber-400 ring-2 ring-amber-100' : ($isDiscrepancy ? 'border-rose-300 ring-1 ring-rose-100' : 'border-[#DCE5DD]') }} p-5 shadow-xs space-y-4">
                    
                    <!-- Header Kartu -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-[#EDF2ED]">
                        <div class="flex items-center gap-2.5">
                            <span class="w-7 h-7 rounded-full {{ $isInUse ? 'bg-amber-500 text-white' : ($isDiscrepancy ? 'bg-rose-600 text-white' : 'bg-[#1C6654] text-white') }} text-xs font-bold flex items-center justify-center">
                                #{{ $tray->id }}
                            </span>
                            <div>
                                <span class="font-mono text-xs font-bold text-[#172923]">
                                    Pengiriman: {{ $deliveryTrip?->trip_code ?? 'TRIP-' . $tray->id }}
                                </span>
                                <span class="text-[11px] text-[#53645B] block sm:inline sm:ml-2">
                                    Tanggal: {{ $serviceRecord?->received_at ? $serviceRecord->received_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') : ($tray->created_at->format('d/m/Y')) }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2.5 py-0.5 rounded-full font-bold border {{ $tray->status->badgeClass() }}">
                                {{ $tray->status->label() }}
                            </span>
                            @if ($tray->incidents->isNotEmpty())
                                <a href="{{ route('incidents.show', $tray->incidents->first()) }}" 
                                   class="text-[11px] px-2 py-0.5 rounded-full font-bold bg-rose-100 text-rose-800 border border-rose-300 hover:underline">
                                    {{ $tray->incidents->count() }} Insiden
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Breakdown Hitungan Wadah Fisik -->
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5 text-center text-xs">
                        <div class="p-2.5 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD]">
                            <span class="text-[10px] text-[#708177] block font-medium">Ekspektasi (Kirim)</span>
                            <span class="text-base font-bold text-[#172923] tabular-nums mt-0.5 block">
                                {{ $tray->is_expected_verified && $tray->expected_trays !== null ? $tray->expected_trays : ($tray->expected_trays ?? '—') }}
                            </span>
                            <span class="text-[10px] text-[#53645B]">{{ $tray->is_expected_verified ? 'Wadah Terverifikasi' : 'Belum Terverifikasi' }}</span>
                        </div>
                        <div class="p-2.5 rounded-xl {{ $tray->ready_count !== null ? 'bg-amber-50 border border-amber-200' : 'bg-slate-50 border border-slate-200' }}">
                            <span class="text-[10px] {{ $tray->ready_count !== null ? 'text-amber-800' : 'text-slate-500' }} block font-medium">Siap Dijemput</span>
                            <span class="text-base font-bold {{ $tray->ready_count !== null ? 'text-amber-900' : 'text-slate-400' }} tabular-nums mt-0.5 block">
                                {{ $tray->ready_count !== null ? $tray->ready_count : '—' }}
                            </span>
                            <span class="text-[10px] {{ $tray->ready_count !== null ? 'text-amber-700' : 'text-slate-400' }}">
                                {{ $tray->ready_count !== null ? 'Fisik Sekolah' : 'Belum Dihitung' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl {{ $tray->picked_up_count !== null ? 'bg-indigo-50 border border-indigo-200' : 'bg-slate-50 border border-slate-200' }}">
                            <span class="text-[10px] {{ $tray->picked_up_count !== null ? 'text-indigo-800' : 'text-slate-500' }} block font-medium">Diambil Kurir</span>
                            <span class="text-base font-bold {{ $tray->picked_up_count !== null ? 'text-indigo-900' : 'text-slate-400' }} tabular-nums mt-0.5 block">
                                {{ $tray->picked_up_count !== null ? $tray->picked_up_count : '—' }}
                            </span>
                            <span class="text-[10px] {{ $tray->picked_up_count !== null ? 'text-indigo-700' : 'text-slate-400' }}">
                                {{ $tray->picked_up_count !== null ? 'Fisik Kurir' : 'Menunggu' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl {{ $tray->returned_count !== null ? 'bg-emerald-50 border border-emerald-200' : 'bg-slate-50 border border-slate-200' }}">
                            <span class="text-[10px] {{ $tray->returned_count !== null ? 'text-emerald-800' : 'text-slate-500' }} block font-medium">Diterima SPPG</span>
                            <span class="text-base font-bold {{ $tray->returned_count !== null ? 'text-emerald-900' : 'text-slate-400' }} tabular-nums mt-0.5 block">
                                {{ $tray->returned_count !== null ? $tray->returned_count : '—' }}
                            </span>
                            <span class="text-[10px] {{ $tray->returned_count !== null ? 'text-emerald-700' : 'text-slate-400' }}">
                                {{ $tray->returned_count !== null ? 'Fisik Depot' : 'Belum Tiba' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl {{ ($tray->damaged_count > 0 || $tray->missing_count > 0) ? 'bg-rose-50 border border-rose-200' : 'bg-[#F5F7F3] border border-[#DCE5DD]' }}">
                            <span class="text-[10px] {{ ($tray->damaged_count > 0 || $tray->missing_count > 0) ? 'text-rose-800' : 'text-[#708177]' }} block font-medium">Rusak / Hilang</span>
                            <span class="text-base font-bold {{ ($tray->damaged_count > 0 || $tray->missing_count > 0) ? 'text-rose-900' : 'text-[#172923]' }} tabular-nums mt-0.5 block">
                                {{ (int) $tray->damaged_count }} / {{ (int) $tray->missing_count }}
                            </span>
                            <span class="text-[10px] {{ ($tray->damaged_count > 0 || $tray->missing_count > 0) ? 'text-rose-700' : 'text-[#708177]' }}">
                                Rsk / Hlg
                            </span>
                        </div>
                    </div>

                    <!-- Informasi Kurir & Riwayat Jejak -->
                    <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD] text-xs text-[#53645B] space-y-1.5">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                            <div>
                                <span class="text-[#708177]">Kurir Pengantar Awal:</span>
                                <strong class="text-[#172923] ml-1">{{ $deliveryTrip?->driver?->name ?? 'Kurir SPPG' }}</strong>
                                <span class="text-[11px] ml-1">({{ $deliveryTrip?->vehicle?->plate_number ?? '-' }})</span>
                            </div>
                            @if ($tray->pickupTripStop?->trip?->driver)
                                <div>
                                    <span class="text-[#708177]">Kurir Penjemput:</span>
                                    <strong class="text-[#172923] ml-1">{{ $tray->pickupTripStop->trip->driver->name }}</strong>
                                </div>
                            @endif
                        </div>

                        <!-- Timestamp Timeline -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-2 border-t border-[#DCE5DD] text-[11px]">
                            <div>
                                <span class="text-[#708177]">Siap Dijemput:</span>
                                <span class="font-medium text-[#172923] block">
                                    {{ $tray->ready_at ? $tray->ready_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') : 'Belum dikonfirmasi' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[#708177]">Wadah Diambil:</span>
                                <span class="font-medium text-[#172923] block">
                                    {{ $tray->picked_up_at ? $tray->picked_up_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') : 'Menunggu pickup' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[#708177]">Tiba di SPPG:</span>
                                <span class="font-medium text-[#172923] block">
                                    {{ $tray->returned_at ? $tray->returned_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') : 'Belum rekonsiliasi' }}
                                </span>
                            </div>
                        </div>

                        @if ($tray->notes)
                            <div class="pt-2 border-t border-[#DCE5DD] text-[11px]">
                                <span class="text-[#708177] font-semibold">Catatan Operasional:</span>
                                <span class="text-[#172923] italic ml-1">"{{ $tray->notes }}"</span>
                            </div>
                        @endif
                    </div>

                    <!-- Bukti Foto jika ada -->
                    @if ($tray->evidencePhotos->isNotEmpty())
                        <div class="space-y-2">
                            <span class="text-[11px] font-bold text-[#53645B] uppercase tracking-wider block">Foto Bukti Fisik:</span>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($tray->evidencePhotos as $photo)
                                    <a href="{{ route('evidence-photos.show', $photo) }}" target="_blank" 
                                       class="group relative w-16 h-16 rounded-lg overflow-hidden border border-[#DCE5DD] bg-slate-100 flex items-center justify-center hover:ring-2 hover:ring-[#1C6654]">
                                        <img src="{{ route('evidence-photos.show', $photo) }}" alt="Bukti" class="w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-[10px]">
                                            Lihat
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- FORM AKSI PIC SEKOLAH: Tandai Siap Dijemput (Hanya jika IN_USE) -->
                    @if ($isInUse)
                        <div class="p-4 rounded-xl bg-amber-50/60 border border-amber-300 space-y-3">
                            <div class="flex items-center gap-2 text-xs font-bold text-amber-900">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Formulir Kesiapan Wadah Ompreng</span>
                            </div>

                            <form action="{{ route('school.trays.ready', $tray) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                                @csrf
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                    <div>
                                        <label for="ready_count_{{ $tray->id }}" class="block font-semibold text-[#172923] mb-1">
                                            Jumlah Wadah Siap Fisik <span class="text-rose-600">*</span>
                                        </label>
                                        <input type="number" 
                                               id="ready_count_{{ $tray->id }}"
                                               name="ready_count" 
                                               min="0" 
                                               value="{{ old('ready_count', $tray->expected_trays) }}" 
                                               required
                                               class="w-full px-3 py-2 rounded-lg border border-[#DCE5DD] bg-white text-[#172923] font-bold text-sm tabular-nums focus:outline-none focus:ring-2 focus:ring-[#1C6654] touch-target">
                                        <span class="text-[10px] text-[#708177] mt-0.5 block">Target awal diterima: {{ $tray->expected_trays }} ompreng.</span>
                                    </div>
                                    <div>
                                        <label for="damaged_count_{{ $tray->id }}" class="block font-semibold text-[#172923] mb-1">
                                            Jumlah Wadah Rusak/Pecah (Jika Ada)
                                        </label>
                                        <input type="number" 
                                               id="damaged_count_{{ $tray->id }}"
                                               name="damaged_count" 
                                               min="0" 
                                               value="{{ old('damaged_count', 0) }}" 
                                               class="w-full px-3 py-2 rounded-lg border border-[#DCE5DD] bg-white text-[#172923] text-sm tabular-nums focus:outline-none focus:ring-2 focus:ring-[#1C6654] touch-target">
                                        <span class="text-[10px] text-[#708177] mt-0.5 block">Diisi 0 jika seluruh wadah dalam kondisi utuh.</span>
                                    </div>
                                </div>

                                <div class="text-xs">
                                    <label for="notes_{{ $tray->id }}" class="block font-semibold text-[#172923] mb-1">
                                        Catatan / Keterangan Selisih
                                    </label>
                                    <input type="text" 
                                           id="notes_{{ $tray->id }}"
                                           name="notes" 
                                           placeholder="Contoh: 2 wadah ditahan di ruang UKS, atau wadah telah dicuci bersih" 
                                           class="w-full px-3 py-2 rounded-lg border border-[#DCE5DD] bg-white text-[#172923] text-xs focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                </div>

                                <div class="text-xs">
                                    <label for="photo_{{ $tray->id }}" class="block font-semibold text-[#172923] mb-1">
                                        Foto Bukti Wadah Siap (Opsional)
                                    </label>
                                    <input type="file" 
                                           id="photo_{{ $tray->id }}"
                                           name="photo" 
                                           accept="image/*"
                                           class="w-full text-xs text-[#53645B] file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-[#1C6654] file:text-white hover:file:bg-[#154d40]">
                                </div>

                                <div class="pt-1">
                                    <button type="submit" 
                                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-[#1C6654] text-white font-bold text-xs hover:bg-[#154d40] shadow-xs touch-target transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span>Konfirmasi Ompreng Siap Dijemput</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @elseif ($tray->status === \App\Enums\TrayReturnStatus::READY_FOR_PICKUP)
                        <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Ompreng telah dikonfirmasi siap ({{ $tray->ready_count }} wadah). Menunggu penjemputan oleh kurir.</span>
                            </div>
                        </div>
                    @endif

                </div>
            @empty
                <div class="p-8 text-center bg-white rounded-2xl border border-[#DCE5DD] text-xs text-[#53645B] space-y-2">
                    <svg class="w-10 h-10 text-[#708177] mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <p class="font-bold text-[#172923]">Belum Ada Riwayat Ompreng</p>
                    <p class="text-[#708177]">Riwayat siklus wadah akan otomatis dibuat setelah sekolah Anda menyelesaikan Berita Acara Penerimaan Makanan (P04).</p>
                </div>
            @endforelse

            <div class="pt-2">
                {{ $trayReturns->links() }}
            </div>
        </div>

    </div>
</x-app-shell>
