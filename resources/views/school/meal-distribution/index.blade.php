<x-app-shell title="Pembagian Makanan (P05) : {{ $school->name }}">
    <div class="max-w-5xl mx-auto px-4 py-6 sm:px-6 space-y-6">

        <!-- Header Halaman P05 -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-bold text-[#1C6654] uppercase tracking-wider bg-[#EDF2ED] px-2 py-0.5 rounded">
                        Modul PIC Sekolah (P05)
                    </span>
                    <span class="text-xs text-[#53645B] font-medium">NPSN: {{ $school->npsn }}</span>
                </div>
                <h1 class="text-xl font-bold text-[#172923] mt-1">Pemeriksaan Sebelum Makan & Realisasi Pembagian</h1>
                <p class="text-xs text-[#53645B] mt-0.5">{{ $school->name }} : {{ $school->address }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('school.receipts.index') }}" 
                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#53645B] hover:text-[#172923] px-3 py-2 rounded-lg border border-[#DCE5DD] bg-white hover:bg-[#F5F7F3] touch-target">
                    <svg class="w-4 h-4 text-[#708177]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Penerimaan (P04)</span>
                </a>
                <a href="{{ route('school.trays.index') }}" 
                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#1C6654] hover:text-[#172923] px-3 py-2 rounded-lg border border-[#1C6654]/30 bg-[#EDF2ED] hover:bg-[#EDF2ED]/80 touch-target">
                    <span>Ompreng (P06)</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Ringkasan Status Layanan -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            <div class="bg-white p-3.5 rounded-xl border border-[#DCE5DD] shadow-xs">
                <span class="text-[11px] font-semibold text-[#53645B] block">Perlu Uji ke-2</span>
                <span class="text-xl font-bold text-amber-700 font-mono mt-0.5 block">{{ $metrics['waiting_recheck'] }}</span>
                <span class="text-[10px] text-[#708177]">Sebelum Siswa Makan</span>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-[#DCE5DD] shadow-xs">
                <span class="text-[11px] font-semibold text-[#53645B] block">Siap Dibagikan</span>
                <span class="text-xl font-bold text-emerald-700 font-mono mt-0.5 block">{{ $metrics['ready_to_serve'] }}</span>
                <span class="text-[10px] text-[#708177]">Lolos Uji Konsumsi</span>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-[#DCE5DD] shadow-xs">
                <span class="text-[11px] font-semibold text-[#53645B] block">Sedang Dibagikan</span>
                <span class="text-xl font-bold text-blue-700 font-mono mt-0.5 block">{{ $metrics['distributed'] }}</span>
                <span class="text-[10px] text-[#708177]">Proses Makan Aktif</span>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-[#DCE5DD] shadow-xs">
                <span class="text-[11px] font-semibold text-[#53645B] block">Selesai Makan</span>
                <span class="text-xl font-bold text-[#172923] font-mono mt-0.5 block">{{ $metrics['completed'] }}</span>
                <span class="text-[10px] text-[#708177]">Layanan Ditutup</span>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-[#DCE5DD] shadow-xs col-span-2 sm:col-span-1">
                <span class="text-[11px] font-semibold text-[#53645B] block">Ditahan / Kendala</span>
                <span class="text-xl font-bold text-rose-700 font-mono mt-0.5 block">{{ $metrics['held'] }}</span>
                <span class="text-[10px] text-[#708177]">Isu Keamanan Pangan</span>
            </div>
        </div>

        <!-- Banner Info Gate Operasional P05 -->
        <div class="p-4 rounded-2xl bg-[#EDF2ED]/60 border border-[#DCE5DD] shadow-xs flex items-start gap-3 text-xs text-[#172923]">
            <svg class="w-5 h-5 text-[#1C6654] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="leading-relaxed">
                <strong class="font-bold text-[#1C6654]">Protokol Keselamatan Pangan Sekolah:</strong> Makanan yang telah diterima fisik dari kurir wajib melalui pemeriksaan kedua sesaat sebelum dibagikan kepada siswa (uji organoleptik: rupa, aroma, tekstur, rasa). Pembagian dilarang sebelum status lolos uji (CLEARED_TO_SERVE). Setelah makan selesai, catat porsi aktual dan porsi sisa sebelum menyiapkan ompreng (P06).
            </div>
        </div>

        <!-- Daftar Catatan Layanan Sekolah -->
        <div class="space-y-4">
            <h2 class="text-xs font-bold text-[#53645B] uppercase tracking-wider">
                Catatan Layanan Makanan ({{ $serviceRecords->total() }})
            </h2>

            @forelse ($serviceRecords as $record)
                @php
                    $stop = $record->tripStop;
                    $trip = $stop?->trip;
                    $usablePortions = max(0, (int) $record->received_portions - (int) $record->damaged_portions);
                    $needsSecondCheck = in_array($record->status, [
                        \App\Enums\SchoolReceiptStatus::RECEIVED,
                        \App\Enums\SchoolReceiptStatus::RECEIVED_WITH_DISCREPANCY,
                    ], true);
                    $isReadyToServe = $record->status === \App\Enums\SchoolReceiptStatus::CLEARED_TO_SERVE;
                    $isDistributed = $record->status === \App\Enums\SchoolReceiptStatus::DISTRIBUTED;
                    $isCompleted = $record->status === \App\Enums\SchoolReceiptStatus::MEAL_COMPLETED;
                    $isHeld = in_array($record->status, [
                        \App\Enums\SchoolReceiptStatus::HELD,
                        \App\Enums\SchoolReceiptStatus::REJECTED,
                    ], true);
                @endphp

                <div class="bg-white rounded-2xl border {{ $needsSecondCheck ? 'border-amber-400 ring-2 ring-amber-100' : 'border-[#DCE5DD]' }} p-5 shadow-xs space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-[#EDF2ED]">
                        <div class="flex items-center gap-2.5">
                            <span class="w-7 h-7 rounded-full bg-[#1C6654] text-white text-xs font-bold flex items-center justify-center font-mono">
                                #{{ $record->id }}
                            </span>
                            <div>
                                <span class="font-mono text-xs font-bold text-[#172923]">Trip: {{ $trip?->trip_code ?? 'TIDAK TERDAFTAR' }}</span>
                                <span class="text-xs text-[#708177] ml-2">
                                    {{ $record->received_at ? $record->received_at->translatedFormat('d M Y, H:i') . ' WIB' : 'Waktu belum tercatat' }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $record->status->badgeClass() }}">
                                {{ $record->status->label() }}
                            </span>
                        </div>
                    </div>

                    <!-- Detail Porsi Layak & Menu -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs bg-[#F5F7F3] p-3.5 rounded-xl border border-[#EDF2ED]">
                        <div>
                            <span class="text-[#708177] block text-[11px]">Diterima Fisik</span>
                            <span class="font-bold text-[#172923] font-mono text-sm">{{ $record->received_portions ?? 0 }} porsi</span>
                        </div>
                        <div>
                            <span class="text-[#708177] block text-[11px]">Layak Konsumsi</span>
                            <span class="font-bold text-emerald-800 font-mono text-sm">{{ $usablePortions }} porsi</span>
                        </div>
                        <div>
                            <span class="text-[#708177] block text-[11px]">Realisasi Terbagi</span>
                            <span class="font-bold text-[#172923] font-mono text-sm">
                                {{ $record->served_portions !== null ? $record->served_portions . ' porsi' : 'Belum diisi' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[#708177] block text-[11px]">Sisa Porsi</span>
                            <span class="font-bold text-[#172923] font-mono text-sm">
                                {{ $record->leftover_portions !== null ? $record->leftover_portions . ' porsi' : 'Belum diisi' }}
                            </span>
                        </div>
                    </div>

                    <!-- Menu & Alokasi Item -->
                    @if ($stop && $stop->deliveryItems->isNotEmpty())
                        <div class="space-y-1.5">
                            <span class="text-[11px] font-bold text-[#53645B] uppercase tracking-wider block">Menu & Batch Terdaftar:</span>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($stop->deliveryItems as $item)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white border border-[#DCE5DD] text-xs text-[#172923]">
                                        <span class="font-semibold">{{ $item->foodBatch?->menu?->name ?? 'Menu Tanpa Nama' }}</span>
                                        <span class="font-mono text-[#708177]">({{ $item->foodBatch?->batch_code ?? 'Batch' }})</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Aksi & Tombol Navigasi -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
                        <div class="text-xs text-[#53645B]">
                            @if ($needsSecondCheck)
                                <span class="text-amber-800 font-semibold flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                    Tahap berikutnya: Lakukan pemeriksaan uji organoleptik sebelum makan
                                </span>
                            @elseif ($isReadyToServe)
                                <span class="text-emerald-800 font-semibold flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Lolos uji: Siap memulai pembagian makanan kepada siswa
                                </span>
                            @elseif ($isDistributed)
                                <span class="text-blue-800 font-semibold flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                    Sedang dibagikan: Catat realisasi porsi setelah kegiatan makan selesai
                                </span>
                            @elseif ($isCompleted)
                                <span class="text-slate-700 font-semibold flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                    Kegiatan makan selesai. Wadah ompreng dapat disiapkan untuk dijemput
                                </span>
                            @elseif ($isHeld)
                                <span class="text-rose-800 font-semibold flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                    Layanan makanan ditahan karena kendala keamanan pangan
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('school.meal-distribution.show', $record) }}" 
                               class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg text-xs font-bold text-white bg-[#1C6654] hover:bg-[#172923] touch-target smooth-transition shadow-xs">
                                <span>Buka Layanan Pembagian</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <!-- Empty State -->
                <div class="bg-white rounded-2xl border border-[#DCE5DD] p-10 text-center space-y-4 shadow-xs">
                    <div class="w-12 h-12 rounded-full bg-[#EDF2ED] text-[#1C6654] flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-sm font-bold text-[#172923]">Belum Ada Pengiriman yang Diterima</h3>
                        <p class="text-xs text-[#53645B] max-w-md mx-auto leading-relaxed">
                            Pemeriksaan sebelum makan dan pencatatan pembagian baru dapat dilakukan setelah armada kurir tiba dan serah terima makanan dikonfirmasi pada modul Penerimaan.
                        </p>
                    </div>
                    <div>
                        <a href="{{ route('school.receipts.index') }}" 
                           class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-lg bg-[#1C6654] text-white hover:bg-[#172923] touch-target">
                            <span>Buka Modul Penerimaan (P04)</span>
                        </a>
                    </div>
                </div>
            @endforelse

            <!-- Pagination -->
            @if ($serviceRecords->hasPages())
                <div class="pt-2">
                    {{ $serviceRecords->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-shell>
