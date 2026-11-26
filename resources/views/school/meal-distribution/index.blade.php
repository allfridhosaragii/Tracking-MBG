<x-app-shell title="Pembagian Makanan (P05) | {{ $school->name }}">
    <div class="space-y-6">
        <!-- Page Header -->
        <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-5 border-b border-[#DCE5DD]">
            <div class="space-y-1.5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="eyebrow-label text-[#1C6654] bg-[#EDF7F2] border border-[#BCE2D0] px-2.5 py-0.5 rounded-md">
                        Modul PIC Sekolah (P05)
                    </span>
                    <span class="text-xs font-mono text-[#53645B]">Kode: {{ $school->school_code }}</span>
                </div>
                <h1 class="page-title">Pemeriksaan Sebelum Makan &amp; Realisasi Pembagian</h1>
                <p class="text-xs sm:text-sm text-[#53645B]">{{ $school->name }} • {{ $school->address }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
                <a href="{{ route('school.receipts.index') }}"
                   class="min-h-[44px] inline-flex items-center gap-1.5 text-xs font-bold text-[#172923] px-3.5 py-2 rounded-xl border border-[#DCE5DD] bg-white hover:bg-[#FAFBF9] smooth-transition">
                    <svg class="w-4 h-4 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Penerimaan (P04)</span>
                </a>
                <a href="{{ route('school.trays.index') }}"
                   class="min-h-[44px] inline-flex items-center gap-1.5 text-xs font-bold text-[#1C6654] px-3.5 py-2 rounded-xl border border-[#BCE2D0] bg-[#EDF7F2] hover:bg-[#DCF0E6] smooth-transition">
                    <span>Ompreng (P06)</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            </div>
        </header>

        <!-- Service Status Metrics -->
        <section aria-label="Ringkasan Status Pembagian" class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            <div class="luxury-surface p-3.5">
                <span class="eyebrow-label block">Perlu Uji ke-2</span>
                <span class="text-2xl font-bold text-[#885D1C] tabular-nums mt-1 block">{{ $metrics['waiting_recheck'] }}</span>
                <span class="text-[11px] text-[#53645B]">Sebelum makan</span>
            </div>
            <div class="luxury-surface p-3.5">
                <span class="eyebrow-label block">Siap Dibagikan</span>
                <span class="text-2xl font-bold text-[#21684B] tabular-nums mt-1 block">{{ $metrics['ready_to_serve'] }}</span>
                <span class="text-[11px] text-[#53645B]">Lolos uji</span>
            </div>
            <div class="luxury-surface p-3.5">
                <span class="eyebrow-label block">Sedang Dibagikan</span>
                <span class="text-2xl font-bold text-[#285F80] tabular-nums mt-1 block">{{ $metrics['distributed'] }}</span>
                <span class="text-[11px] text-[#53645B]">Proses makan</span>
            </div>
            <div class="luxury-surface p-3.5">
                <span class="eyebrow-label block">Selesai Makan</span>
                <span class="text-2xl font-bold text-[#172923] tabular-nums mt-1 block">{{ $metrics['completed'] }}</span>
                <span class="text-[11px] text-[#53645B]">Layanan ditutup</span>
            </div>
            <div class="luxury-surface p-3.5 col-span-2 sm:col-span-1">
                <span class="eyebrow-label block">Ditahan / Kendala</span>
                <span class="text-2xl font-bold text-[#9B3232] tabular-nums mt-1 block">{{ $metrics['held'] }}</span>
                <span class="text-[11px] text-[#53645B]">Keamanan pangan</span>
            </div>
        </section>

        <!-- Progressive SOP Disclosure -->
        <details class="sop-disclosure">
            <summary>
                <span class="flex items-center gap-2 text-xs font-bold text-[#172923]">
                    <svg class="w-4 h-4 text-[#1C6654] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Protokol Keselamatan Pangan Sekolah (SOP P05)</span>
                </span>
                <span class="text-[11px] font-semibold text-[#1C6654]">Baca SOP</span>
            </summary>
            <div class="sop-disclosure-body text-xs text-[#53645B] leading-relaxed">
                Makanan yang telah diterima fisik dari kurir wajib melalui pemeriksaan kedua sesaat sebelum dibagikan kepada siswa (rupa, aroma, tekstur, rasa). Pembagian dilarang sebelum status lolos uji (<span class="font-mono font-semibold text-[#172923]">CLEARED_TO_SERVE</span>). Setelah makan selesai, catat porsi aktual dan porsi sisa sebelum menyiapkan ompreng (P06).
            </div>
        </details>

        <!-- Service Records List -->
        <section aria-labelledby="service-records-heading" class="space-y-4">
            <h2 id="service-records-heading" class="eyebrow-label">
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

                <article class="luxury-surface p-5 space-y-4 {{ $needsSecondCheck ? 'border-[#885D1C] ring-1 ring-[#F7D7A3]' : '' }}">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 pb-3.5 border-b border-[#E7ECE8]">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-xl bg-[#1C6654] text-white text-xs font-bold font-mono flex items-center justify-center shrink-0">
                                #{{ $record->id }}
                            </span>
                            <div>
                                <span class="font-mono text-xs sm:text-sm font-bold text-[#172923] block">Trip: {{ $trip?->trip_code ?? 'TIDAK TERDAFTAR' }}</span>
                                <span class="text-[11px] text-[#53645B] tabular-nums">
                                    {{ $record->received_at ? $record->received_at->translatedFormat('d M Y, H:i') . ' WIB' : 'Waktu belum tercatat' }}
                                </span>
                            </div>
                        </div>

                        <span class="inline-flex items-center self-start sm:self-auto px-2.5 py-1 rounded-lg text-xs font-bold border {{ $record->status->badgeClass() }}">
                            {{ $record->status->label() }}
                        </span>
                    </div>

                    <!-- Portion Breakdown -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs inset-surface p-3.5">
                        <div>
                            <span class="text-[#53645B] block text-[11px]">Diterima Fisik</span>
                            <span class="font-bold text-[#172923] tabular-nums text-sm mt-0.5 block">{{ $record->received_portions ?? 0 }} porsi</span>
                        </div>
                        <div>
                            <span class="text-[#53645B] block text-[11px]">Layak Konsumsi</span>
                            <span class="font-bold text-[#21684B] tabular-nums text-sm mt-0.5 block">{{ $usablePortions }} porsi</span>
                        </div>
                        <div>
                            <span class="text-[#53645B] block text-[11px]">Realisasi Terbagi</span>
                            <span class="font-bold text-[#172923] tabular-nums text-sm mt-0.5 block">
                                {{ $record->served_portions !== null ? $record->served_portions . ' porsi' : 'Belum diisi' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[#53645B] block text-[11px]">Sisa Porsi</span>
                            <span class="font-bold text-[#172923] tabular-nums text-sm mt-0.5 block">
                                {{ $record->leftover_portions !== null ? $record->leftover_portions . ' porsi' : 'Belum diisi' }}
                            </span>
                        </div>
                    </div>

                    @if ($stop && $stop->deliveryItems->isNotEmpty())
                        <div class="space-y-1.5">
                            <span class="eyebrow-label block">Menu &amp; Batch Terdaftar</span>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($stop->deliveryItems as $item)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#FAFBF9] border border-[#DCE5DD] text-xs text-[#172923]">
                                        <span class="font-semibold">{{ $item->foodBatch?->menu?->name ?? $item->foodBatch?->menu?->recipe_name ?? 'Menu MBG' }}</span>
                                        <span class="font-mono text-[#53645B]">({{ $item->foodBatch?->batch_code ?? 'Batch' }})</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Next Step Indicator & CTA -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-1">
                        <div class="text-xs">
                            @if ($needsSecondCheck)
                                <span class="text-[#885D1C] font-semibold flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-[#885D1C] shrink-0" aria-hidden="true"></span>
                                    <span>Tahap berikutnya: Lakukan pemeriksaan uji organoleptik sebelum makan</span>
                                </span>
                            @elseif ($isReadyToServe)
                                <span class="text-[#21684B] font-semibold flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-[#21684B] shrink-0" aria-hidden="true"></span>
                                    <span>Lolos uji: Siap memulai pembagian makanan kepada siswa</span>
                                </span>
                            @elseif ($isDistributed)
                                <span class="text-[#285F80] font-semibold flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-[#285F80] shrink-0" aria-hidden="true"></span>
                                    <span>Sedang dibagikan: Catat realisasi porsi setelah kegiatan makan selesai</span>
                                </span>
                            @elseif ($isCompleted)
                                <span class="text-[#53645B] font-semibold flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-[#53645B] shrink-0" aria-hidden="true"></span>
                                    <span>Kegiatan makan selesai. Wadah ompreng dapat disiapkan untuk dijemput</span>
                                </span>
                            @elseif ($isHeld)
                                <span class="text-[#9B3232] font-semibold flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-[#9B3232] shrink-0" aria-hidden="true"></span>
                                    <span>Layanan makanan ditahan karena kendala keamanan pangan</span>
                                </span>
                            @endif
                        </div>

                        <a href="{{ route('school.meal-distribution.show', $record) }}"
                           class="min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-[#1C6654] hover:bg-[#164D42] smooth-transition subtle-shadow shrink-0">
                            <span>Buka Layanan Pembagian</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </article>
            @empty
                <div class="luxury-surface p-10 text-center space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-[#EDF7F2] text-[#1C6654] flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <h3 class="section-title">Belum Ada Pengiriman yang Diterima</h3>
                        <p class="text-xs text-[#53645B] max-w-md mx-auto leading-relaxed">
                            Pemeriksaan sebelum makan dan pencatatan pembagian tersedia setelah serah terima makanan dikonfirmasi pada modul Penerimaan (P04).
                        </p>
                    </div>
                    <div>
                        <a href="{{ route('school.receipts.index') }}"
                           class="min-h-[44px] inline-flex items-center gap-2 px-5 py-2.5 text-xs font-bold rounded-xl bg-[#1C6654] text-white hover:bg-[#164D42] smooth-transition">
                            <span>Buka Modul Penerimaan (P04)</span>
                        </a>
                    </div>
                </div>
            @endforelse

            @if ($serviceRecords->hasPages())
                <div class="pt-2">
                    {{ $serviceRecords->links() }}
                </div>
            @endif
        </section>
    </div>
</x-app-shell>
