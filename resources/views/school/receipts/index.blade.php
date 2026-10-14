<x-app-shell title="Penerimaan Makanan — {{ $school->name }}">
    <div class="max-w-5xl mx-auto px-4 py-6 sm:px-6 space-y-6">

        <!-- Header Halaman P04 -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-bold text-[#1C6654] uppercase tracking-wider bg-[#EDF2ED] px-2 py-0.5 rounded">
                        Modul PIC Sekolah (P04)
                    </span>
                    <span class="text-xs text-[#53645B] font-medium">NPSN: {{ $school->npsn }}</span>
                </div>
                <h1 class="text-xl font-bold text-[#172923] mt-1">Daftar Penerimaan Makanan</h1>
                <p class="text-xs text-[#53645B] mt-0.5">{{ $school->name }} &mdash; {{ $school->address }}</p>
            </div>
            <div>
                <a href="{{ route('school.dashboard') }}" 
                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#53645B] hover:text-[#172923] px-3 py-2 rounded-lg border border-[#DCE5DD] bg-white hover:bg-[#F5F7F3] touch-target">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Dashboard Sekolah</span>
                </a>
            </div>
        </div>

        <!-- Banner Info Alur Serah Terima -->
        <div class="p-4 rounded-2xl bg-white border border-[#DCE5DD] shadow-xs flex items-start gap-3 text-xs text-[#53645B]">
            <svg class="w-5 h-5 text-[#1C6654] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <div class="leading-relaxed">
                <strong class="text-[#172923]">Prosedur Wajib PIC Sekolah:</strong> Saat armada kurir tiba, lakukan penghitungan fisik wadah ompreng, periksa kesesuaian suhu, aroma, dan tampilan makanan (uji organoleptik). Konfirmasi penerimaan mengesahkan serah terima dan membebaskan kurir untuk melanjutkan rute.
            </div>
        </div>

        <!-- Daftar Pengiriman Makanan -->
        <div class="space-y-4">
            <h2 class="text-xs font-bold text-[#53645B] uppercase tracking-wider">
                Jadwal Pengiriman Sekolah Ini ({{ $stops->total() }})
            </h2>

            @forelse ($stops as $stop)
                @php
                    $isAwaitingReceipt = in_array($stop->status, [
                        \App\Enums\TripStopStatus::ARRIVED,
                        \App\Enums\TripStopStatus::ARRIVED_AWAITING_CONFIRMATION
                    ], true);
                    $isCompleted = $stop->status === \App\Enums\TripStopStatus::HANDED_OVER;
                    $serviceRecord = $stop->schoolServiceRecord;
                @endphp

                <div class="bg-white rounded-2xl border {{ $isAwaitingReceipt ? 'border-amber-400 ring-2 ring-amber-100' : 'border-[#DCE5DD]' }} p-5 shadow-xs space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-[#EDF2ED]">
                        <div class="flex items-center gap-2.5">
                            <span class="w-7 h-7 rounded-full {{ $isAwaitingReceipt ? 'bg-amber-500 text-white' : 'bg-[#1C6654] text-white' }} text-xs font-bold flex items-center justify-center">
                                {{ $stop->sequence_number }}
                            </span>
                            <div>
                                <span class="font-mono text-xs font-bold text-[#172923]">Trip: {{ $stop->trip->trip_code }}</span>
                                <span class="text-[11px] text-[#53645B] block sm:inline sm:ml-2">
                                    Tanggal Layanan: {{ $stop->trip->service_date?->format('d/m/Y') ?? 'Hari Ini' }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2.5 py-0.5 rounded-full font-bold {{ $stop->status->badgeClass() }}">
                                {{ $stop->status->label() }}
                            </span>
                            @if ($serviceRecord)
                                <span class="text-xs px-2 py-0.5 rounded font-bold {{ $serviceRecord->status->badgeClass() }}">
                                    {{ $serviceRecord->status->label() }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Informasi Muatan & Logistik -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                        <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD]">
                            <span class="text-[#708177] block text-[10px]">Kurir & Armada</span>
                            <span class="font-bold text-[#172923] block mt-0.5">{{ $stop->trip->driver?->name ?? 'Kurir SPPG' }}</span>
                            <span class="text-[11px] text-[#53645B]">{{ $stop->trip->vehicle?->plate_number ?? '-' }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD]">
                            <span class="text-[#708177] block text-[10px]">Muatan Dikirim</span>
                            <span class="font-bold text-[#1C6654] block mt-0.5 text-sm tabular-nums">
                                {{ $stop->loaded_portions ?: $stop->planned_portions }} Porsi
                            </span>
                            <span class="text-[11px] text-[#53645B]">
                                {{ $stop->deliveryItems->first()?->foodBatch?->menu?->recipe_name ?? 'Menu Standar MBG' }}
                            </span>
                        </div>
                        <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD]">
                            <span class="text-[#708177] block text-[10px]">Waktu Tiba Armada</span>
                            <span class="font-bold text-[#172923] block mt-0.5 tabular-nums">
                                {{ $stop->arrived_at ? $stop->arrived_at->setTimezone('Asia/Jakarta')->format('H:i:s \W\I\B') : 'Dalam perjalanan' }}
                            </span>
                            <span class="text-[11px] text-[#53645B]">
                                {{ $stop->arrived_at ? 'Armada telah di lokasi' : 'Estimasi sesuai jadwal' }}
                            </span>
                        </div>
                    </div>

                    <!-- Action Bar -->
                    <div class="pt-2 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        @if ($isAwaitingReceipt)
                            <div class="text-xs text-amber-800 font-medium flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                                <span>Armada telah tiba di sekolah. Menunggu verifikasi fisik & organoleptik.</span>
                            </div>
                            <a href="{{ route('school.receipts.show', $stop) }}" 
                               class="py-2.5 px-4 rounded-xl font-bold text-xs bg-[#1C6654] text-white hover:bg-[#154E40] transition-colors shadow-xs flex items-center justify-center gap-2 touch-target">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                                <span>Periksa & Konfirmasi Penerimaan (P04)</span>
                            </a>
                        @elseif ($isCompleted)
                            <div class="text-xs text-emerald-800 font-medium flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Penerimaan selesai diverifikasi ({{ $serviceRecord?->received_portions ?? '-' }} porsi diterima).</span>
                            </div>
                            <a href="{{ route('school.receipts.show', $stop) }}" 
                               class="py-2 px-3 rounded-lg font-bold text-xs bg-white border border-[#DCE5DD] text-[#172923] hover:bg-[#F5F7F3] transition-colors flex items-center justify-center gap-1.5 touch-target">
                                <span>Lihat Berita Acara Penerimaan</span>
                                <svg class="w-3.5 h-3.5 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        @else
                            <div class="text-xs text-[#53645B]">
                                Konfirmasi penerimaan dibuka setelah kurir menandai kedatangan armada di sekolah.
                            </div>
                            <span class="text-xs font-medium text-[#708177] italic">
                                Belum dapat dikonfirmasi
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-2xl border border-[#DCE5DD] p-10 text-center space-y-3">
                    <div class="w-12 h-12 rounded-full bg-[#EDF2ED] text-[#1C6654] flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-[#172923]">Belum Ada Pengiriman Makanan</h3>
                    <p class="text-xs text-[#53645B] max-w-sm mx-auto leading-relaxed">
                        Tidak ada catatan pengiriman makanan terjadwal untuk sekolah Anda saat ini.
                    </p>
                </div>
            @endforelse

            <div class="pt-2">
                {{ $stops->links() }}
            </div>
        </div>

    </div>
</x-app-shell>
