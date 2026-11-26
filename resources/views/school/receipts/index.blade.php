<x-app-shell title="Penerimaan Makanan | {{ $school->name }}">
    <div class="space-y-6">
        <!-- Page Header -->
        <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-5 border-b border-[#DCE5DD]">
            <div class="space-y-1.5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="eyebrow-label text-[#1C6654] bg-[#EDF7F2] border border-[#BCE2D0] px-2.5 py-0.5 rounded-md">
                        Modul PIC Sekolah (P04)
                    </span>
                    <span class="text-xs font-mono text-[#53645B]">Kode: {{ $school->school_code }}</span>
                </div>
                <h1 class="page-title">Daftar Penerimaan Makanan</h1>
                <p class="text-xs sm:text-sm text-[#53645B]">{{ $school->name }} • {{ $school->address }}</p>
            </div>

            <a href="{{ route('school.dashboard') }}"
               class="min-h-[44px] self-start sm:self-auto inline-flex items-center gap-2 text-xs font-bold text-[#172923] px-4 py-2 rounded-xl border border-[#DCE5DD] bg-white hover:bg-[#FAFBF9] subtle-shadow smooth-transition">
                <svg class="w-4 h-4 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Beranda Sekolah</span>
            </a>
        </header>

        <!-- Progressive SOP Disclosure -->
        <details class="sop-disclosure">
            <summary>
                <span class="flex items-center gap-2 text-xs font-bold text-[#172923]">
                    <svg class="w-4 h-4 text-[#1C6654] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>Prosedur Wajib Penerimaan Makanan (SOP P04)</span>
                </span>
                <span class="text-[11px] font-semibold text-[#1C6654]">Baca SOP</span>
            </summary>
            <div class="sop-disclosure-body text-xs text-[#53645B] leading-relaxed">
                Saat armada kurir tiba, lakukan penghitungan fisik wadah ompreng serta periksa suhu, aroma, tekstur, dan tampilan makanan (uji organoleptik). Konfirmasi penerimaan mengesahkan berita acara serah terima dan membebaskan kurir melanjutkan rute.
            </div>
        </details>

        <!-- Delivery Schedule Cards -->
        <section aria-labelledby="receipt-schedule-heading" class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 id="receipt-schedule-heading" class="eyebrow-label">
                    Jadwal Pengiriman Sekolah Ini ({{ $stops->total() }})
                </h2>
            </div>

            @forelse ($stops as $stop)
                @php
                    $isAwaitingReceipt = in_array($stop->status, [
                        \App\Enums\TripStopStatus::ARRIVED,
                        \App\Enums\TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
                    ], true);
                    $isCompleted = $stop->status === \App\Enums\TripStopStatus::HANDED_OVER;
                    $serviceRecord = $stop->schoolServiceRecord;
                    $firstMenu = $stop->deliveryItems->first()?->foodBatch?->menu;
                @endphp

                <article class="luxury-surface p-5 space-y-4 {{ $isAwaitingReceipt ? 'border-[#885D1C] ring-1 ring-[#F7D7A3]' : '' }}">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 pb-3.5 border-b border-[#E7ECE8]">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-xl {{ $isAwaitingReceipt ? 'bg-[#885D1C] text-white' : 'bg-[#1C6654] text-white' }} text-xs font-bold flex items-center justify-center shrink-0 tabular-nums">
                                {{ $stop->sequence_number }}
                            </span>
                            <div>
                                <span class="font-mono text-xs sm:text-sm font-bold text-[#172923] block">Trip: {{ $stop->trip->trip_code }}</span>
                                <span class="text-[11px] text-[#53645B] tabular-nums">
                                    Tanggal Layanan: {{ $stop->trip->service_date?->format('d/m/Y') ?? 'Hari Ini' }}
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs px-2.5 py-1 rounded-lg font-bold border {{ $stop->status->badgeClass() }}">
                                {{ $stop->status->label() }}
                            </span>
                            @if ($serviceRecord)
                                <span class="text-xs px-2.5 py-1 rounded-lg font-bold border {{ $serviceRecord->status->badgeClass() }}">
                                    {{ $serviceRecord->status->label() }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Cargo Summary -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                        <div class="inset-surface p-3.5">
                            <span class="eyebrow-label block mb-1">Kurir &amp; Armada</span>
                            <span class="font-bold text-[#172923] block">{{ $stop->trip->driver?->name ?? 'Kurir SPPG' }}</span>
                            <span class="text-[11px] font-mono text-[#53645B] mt-0.5 block">{{ $stop->trip->vehicle?->plate_number ?? '-' }}</span>
                        </div>
                        <div class="inset-surface p-3.5">
                            <span class="eyebrow-label block mb-1">Muatan Dikirim</span>
                            <span class="font-bold text-[#1C6654] block text-sm tabular-nums">
                                {{ $stop->loaded_portions ?: $stop->planned_portions }} Porsi
                            </span>
                            <span class="text-[11px] text-[#53645B] mt-0.5 block truncate">
                                {{ $firstMenu?->name ?? $firstMenu?->recipe_name ?? 'Menu Standar MBG' }}
                            </span>
                        </div>
                        <div class="inset-surface p-3.5">
                            <span class="eyebrow-label block mb-1">Waktu Tiba Armada</span>
                            <span class="font-bold text-[#172923] block tabular-nums">
                                {{ $stop->arrived_at ? $stop->arrived_at->setTimezone('Asia/Jakarta')->format('H:i:s \W\I\B') : 'Dalam perjalanan' }}
                            </span>
                            <span class="text-[11px] text-[#53645B] mt-0.5 block">
                                {{ $stop->arrived_at ? 'Armada telah di lokasi' : 'Estimasi sesuai jadwal' }}
                            </span>
                        </div>
                    </div>

                    <!-- Action Bar -->
                    <div class="pt-1 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        @if ($isAwaitingReceipt)
                            <div class="text-xs text-[#885D1C] font-semibold flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-[#885D1C] shrink-0" aria-hidden="true"></span>
                                <span>Armada telah tiba di sekolah. Menunggu verifikasi fisik &amp; organoleptik.</span>
                            </div>
                            <a href="{{ route('school.receipts.show', $stop) }}"
                               class="min-h-[44px] py-2.5 px-4 rounded-xl font-bold text-xs bg-[#1C6654] text-white hover:bg-[#164D42] smooth-transition subtle-shadow flex items-center justify-center gap-2 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                                <span>Periksa &amp; Konfirmasi Penerimaan (P04)</span>
                            </a>
                        @elseif ($isCompleted)
                            <div class="text-xs text-[#21684B] font-semibold flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#21684B] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Penerimaan selesai diverifikasi ({{ $serviceRecord?->received_portions ?? '-' }} porsi diterima).</span>
                            </div>
                            <a href="{{ route('school.receipts.show', $stop) }}"
                               class="min-h-[44px] py-2 px-4 rounded-xl font-bold text-xs bg-white border border-[#DCE5DD] text-[#172923] hover:bg-[#FAFBF9] smooth-transition flex items-center justify-center gap-1.5 shrink-0">
                                <span>Lihat Berita Acara Penerimaan</span>
                                <svg class="w-3.5 h-3.5 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        @else
                            <p class="text-xs text-[#53645B]">
                                Konfirmasi penerimaan dibuka setelah kurir menandai kedatangan armada di sekolah.
                            </p>
                            <span class="text-xs font-semibold text-[#53645B] bg-[#FAFBF9] border border-[#E7ECE8] px-3 py-1.5 rounded-lg self-start sm:self-auto">
                                Belum dapat dikonfirmasi
                            </span>
                        @endif
                    </div>
                </article>
            @empty
                <div class="luxury-surface p-10 text-center space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-[#EDF7F2] text-[#1C6654] flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                    <h3 class="section-title">Belum Ada Pengiriman Makanan</h3>
                    <p class="text-xs text-[#53645B] max-w-sm mx-auto leading-relaxed">
                        Tidak ada catatan pengiriman makanan terjadwal untuk sekolah Anda saat ini.
                    </p>
                </div>
            @endforelse

            @if ($stops->hasPages())
                <div class="pt-2">
                    {{ $stops->links() }}
                </div>
            @endif
        </section>
    </div>
</x-app-shell>
