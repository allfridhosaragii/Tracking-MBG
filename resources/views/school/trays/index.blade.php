<x-app-shell title="Manajemen Ompreng & Wadah | {{ $school->name }}">
    <div class="space-y-6">
        <!-- Page Header -->
        <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-5 border-b border-[#DCE5DD]">
            <div class="space-y-1.5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="eyebrow-label text-[#1C6654] bg-[#EDF7F2] border border-[#BCE2D0] px-2.5 py-0.5 rounded-md">
                        Modul PIC Sekolah (P06)
                    </span>
                    <span class="text-xs font-mono text-[#53645B]">Kode: {{ $school->school_code }}</span>
                </div>
                <h1 class="page-title">Pelacakan & Kesiapan Ompreng</h1>
                <p class="text-xs sm:text-sm text-[#53645B]">{{ $school->name }} • {{ $school->address }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
                <a href="{{ route('school.meal-distribution.index') }}"
                   class="min-h-[44px] inline-flex items-center gap-1.5 text-xs font-bold text-[#172923] px-3.5 py-2 rounded-xl border border-[#DCE5DD] bg-white hover:bg-[#FAFBF9] smooth-transition">
                    <span>Pembagian (P05)</span>
                </a>
                <a href="{{ route('school.receipts.index') }}"
                   class="min-h-[44px] inline-flex items-center gap-1.5 text-xs font-bold text-[#53645B] hover:text-[#172923] px-3.5 py-2 rounded-xl border border-[#DCE5DD] bg-white hover:bg-[#FAFBF9] smooth-transition">
                    <span>Penerimaan (P04)</span>
                </a>
            </div>
        </header>

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="p-4 rounded-2xl bg-[#EDF7F2] border border-[#BCE2D0] text-xs font-semibold text-[#21684B] flex items-center gap-2.5">
                <svg class="w-4 h-4 text-[#21684B] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-2xl bg-[#FDF2F2] border border-[#F5C2C2] text-xs text-[#9B3232] space-y-1">
                <div class="font-bold">Gagal memproses data ompreng:</div>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Metrics Strip -->
        <section aria-label="Ringkasan Wadah Ompreng" class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            <div class="luxury-surface p-3.5">
                <span class="eyebrow-label block">Dalam Penggunaan</span>
                <span class="text-2xl font-bold text-[#172923] tabular-nums mt-1 block">{{ $metrics['in_use'] }}</span>
                <span class="text-[11px] text-[#53645B]">Wadah di sekolah</span>
            </div>
            <div class="luxury-surface p-3.5">
                <span class="eyebrow-label block">Siap Dijemput</span>
                <span class="text-2xl font-bold text-[#885D1C] tabular-nums mt-1 block">{{ $metrics['ready'] }}</span>
                <span class="text-[11px] text-[#53645B]">Menunggu kurir</span>
            </div>
            <div class="luxury-surface p-3.5">
                <span class="eyebrow-label block">Diambil Kurir</span>
                <span class="text-2xl font-bold text-[#285F80] tabular-nums mt-1 block">{{ $metrics['picked_up'] }}</span>
                <span class="text-[11px] text-[#53645B]">Dalam perjalanan</span>
            </div>
            <div class="luxury-surface p-3.5">
                <span class="eyebrow-label block">Kembali SPPG</span>
                <span class="text-2xl font-bold text-[#21684B] tabular-nums mt-1 block">{{ $metrics['returned'] }}</span>
                <span class="text-[11px] text-[#53645B]">Tiba di depot</span>
            </div>
            <div class="luxury-surface p-3.5 col-span-2 sm:col-span-1">
                <span class="eyebrow-label block">Ada Selisih</span>
                <span class="text-2xl font-bold text-[#9B3232] tabular-nums mt-1 block">{{ $metrics['discrepancy'] }}</span>
                <span class="text-[11px] text-[#53645B]">Perlu penanganan</span>
            </div>
        </section>

        <!-- Progressive SOP Disclosure -->
        <details class="sop-disclosure">
            <summary>
                <span class="flex items-center gap-2 text-xs font-bold text-[#172923]">
                    <svg class="w-4 h-4 text-[#1C6654] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>SOP Ompreng Balik / Reverse Logistics (P06)</span>
                </span>
                <span class="text-[11px] font-semibold text-[#1C6654]">Baca SOP</span>
            </summary>
            <div class="sop-disclosure-body text-xs text-[#53645B] leading-relaxed">
                Setelah kegiatan makan selesai dan wadah dibersihkan serta dikumpulkan, PIC Sekolah menghitung fisik wadah dan menandai status <strong>Siap Dijemput</strong>. Kurir SPPG mengambil wadah setelah konfirmasi kesiapan diterbitkan.
            </div>
        </details>

        <!-- Tray Return Cycle Cards -->
        <section aria-labelledby="tray-cycle-heading" class="space-y-4">
            <h2 id="tray-cycle-heading" class="eyebrow-label">
                Daftar Riwayat &amp; Status Wadah ({{ $trayReturns->total() }})
            </h2>

            @forelse ($trayReturns as $tray)
                @php
                    $serviceRecord = $tray->schoolServiceRecord;
                    $tripStop = $serviceRecord?->tripStop;
                    $deliveryTrip = $tripStop?->trip;
                    $isInUse = $tray->status === \App\Enums\TrayReturnStatus::IN_USE;
                    $isDiscrepancy = $tray->status === \App\Enums\TrayReturnStatus::DISCREPANCY;
                @endphp

                <article class="luxury-surface p-5 space-y-4 {{ $isInUse ? 'border-[#885D1C] ring-1 ring-[#F7D7A3]' : ($isDiscrepancy ? 'border-[#9B3232]' : '') }}">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 pb-3.5 border-b border-[#E7ECE8]">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-xl {{ $isInUse ? 'bg-[#885D1C] text-white' : ($isDiscrepancy ? 'bg-[#9B3232] text-white' : 'bg-[#1C6654] text-white') }} text-xs font-bold font-mono flex items-center justify-center shrink-0">
                                #{{ $tray->id }}
                            </span>
                            <div>
                                <span class="font-mono text-xs sm:text-sm font-bold text-[#172923] block">
                                    Pengiriman: {{ $deliveryTrip?->trip_code ?? 'TRIP-' . $tray->id }}
                                </span>
                                <span class="text-[11px] text-[#53645B] tabular-nums">
                                    Tanggal: {{ $serviceRecord?->received_at ? $serviceRecord->received_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') : $tray->created_at->format('d/m/Y') }}
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs px-2.5 py-1 rounded-lg font-bold border {{ $tray->status->badgeClass() }}">
                                {{ $tray->status->label() }}
                            </span>
                            @if ($tray->incidents->isNotEmpty())
                                <a href="{{ route('incidents.show', $tray->incidents->first()) }}"
                                   class="min-h-[32px] inline-flex items-center text-[11px] px-2.5 py-1 rounded-lg font-bold bg-[#FDF2F2] text-[#9B3232] border border-[#F5C2C2] hover:underline">
                                    {{ $tray->incidents->count() }} Insiden
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Physical Container Counts -->
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5 text-center text-xs">
                        <div class="inset-surface p-3">
                            <span class="text-[10px] text-[#53645B] block font-semibold">Ekspektasi (Kirim)</span>
                            <span class="text-base font-bold text-[#172923] tabular-nums mt-0.5 block">
                                {{ $tray->expected_trays ?? '-' }}
                            </span>
                            <span class="text-[10px] text-[#53645B]">{{ $tray->is_expected_verified ? 'Terverifikasi' : 'Belum Terverifikasi' }}</span>
                        </div>
                        <div class="p-3 rounded-xl border {{ $tray->ready_count !== null ? 'bg-[#FEF7EC] border-[#F7D7A3]' : 'bg-[#FAFBF9] border-[#E7ECE8]' }}">
                            <span class="text-[10px] {{ $tray->ready_count !== null ? 'text-[#885D1C]' : 'text-[#53645B]' }} block font-semibold">Siap Dijemput</span>
                            <span class="text-base font-bold {{ $tray->ready_count !== null ? 'text-[#885D1C]' : 'text-[#53645B]' }} tabular-nums mt-0.5 block">
                                {{ $tray->ready_count !== null ? $tray->ready_count : '-' }}
                            </span>
                            <span class="text-[10px] text-[#53645B]">
                                {{ $tray->ready_count !== null ? 'Fisik Sekolah' : 'Belum Dihitung' }}
                            </span>
                        </div>
                        <div class="p-3 rounded-xl border {{ $tray->picked_up_count !== null ? 'bg-[#F0F6FA] border-[#C2DBE8]' : 'bg-[#FAFBF9] border-[#E7ECE8]' }}">
                            <span class="text-[10px] {{ $tray->picked_up_count !== null ? 'text-[#285F80]' : 'text-[#53645B]' }} block font-semibold">Diambil Kurir</span>
                            <span class="text-base font-bold {{ $tray->picked_up_count !== null ? 'text-[#285F80]' : 'text-[#53645B]' }} tabular-nums mt-0.5 block">
                                {{ $tray->picked_up_count !== null ? $tray->picked_up_count : '-' }}
                            </span>
                            <span class="text-[10px] text-[#53645B]">
                                {{ $tray->picked_up_count !== null ? 'Fisik Kurir' : 'Menunggu' }}
                            </span>
                        </div>
                        <div class="p-3 rounded-xl border {{ $tray->returned_count !== null ? 'bg-[#EDF7F2] border-[#BCE2D0]' : 'bg-[#FAFBF9] border-[#E7ECE8]' }}">
                            <span class="text-[10px] {{ $tray->returned_count !== null ? 'text-[#21684B]' : 'text-[#53645B]' }} block font-semibold">Diterima SPPG</span>
                            <span class="text-base font-bold {{ $tray->returned_count !== null ? 'text-[#21684B]' : 'text-[#53645B]' }} tabular-nums mt-0.5 block">
                                {{ $tray->returned_count !== null ? $tray->returned_count : '-' }}
                            </span>
                            <span class="text-[10px] text-[#53645B]">
                                {{ $tray->returned_count !== null ? 'Fisik Depot' : 'Belum Tiba' }}
                            </span>
                        </div>
                        <div class="col-span-2 sm:col-span-1 p-3 rounded-xl border {{ ($tray->damaged_count > 0 || $tray->missing_count > 0) ? 'bg-[#FDF2F2] border-[#F5C2C2]' : 'bg-[#FAFBF9] border-[#E7ECE8]' }}">
                            <span class="text-[10px] {{ ($tray->damaged_count > 0 || $tray->missing_count > 0) ? 'text-[#9B3232]' : 'text-[#53645B]' }} block font-semibold">Rusak / Hilang</span>
                            <span class="text-base font-bold {{ ($tray->damaged_count > 0 || $tray->missing_count > 0) ? 'text-[#9B3232]' : 'text-[#172923]' }} tabular-nums mt-0.5 block">
                                {{ (int) $tray->damaged_count }} / {{ (int) $tray->missing_count }}
                            </span>
                            <span class="text-[10px] text-[#53645B]">Rsk / Hlg</span>
                        </div>
                    </div>

                    <!-- Courier & Timestamp Trace -->
                    <div class="inset-surface p-3.5 text-xs text-[#53645B] space-y-2">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                            <div>
                                <span>Kurir Pengantar Awal:</span>
                                <strong class="text-[#172923] ml-1">{{ $deliveryTrip?->driver?->name ?? 'Kurir SPPG' }}</strong>
                                <span class="font-mono text-[11px] ml-1">({{ $deliveryTrip?->vehicle?->plate_number ?? '-' }})</span>
                            </div>
                            @if ($tray->pickupTripStop?->trip?->driver)
                                <div>
                                    <span>Kurir Penjemput:</span>
                                    <strong class="text-[#172923] ml-1">{{ $tray->pickupTripStop->trip->driver->name }}</strong>
                                </div>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-2 border-t border-[#E7ECE8] text-[11px]">
                            <div>
                                <span>Siap Dijemput:</span>
                                <span class="font-semibold text-[#172923] tabular-nums block">
                                    {{ $tray->ready_at ? $tray->ready_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') : 'Belum dikonfirmasi' }}
                                </span>
                            </div>
                            <div>
                                <span>Wadah Diambil:</span>
                                <span class="font-semibold text-[#172923] tabular-nums block">
                                    {{ $tray->picked_up_at ? $tray->picked_up_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') : 'Menunggu pickup' }}
                                </span>
                            </div>
                            <div>
                                <span>Tiba di SPPG:</span>
                                <span class="font-semibold text-[#172923] tabular-nums block">
                                    {{ $tray->returned_at ? $tray->returned_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') : 'Belum rekonsiliasi' }}
                                </span>
                            </div>
                        </div>

                        @if ($tray->notes)
                            <div class="pt-2 border-t border-[#E7ECE8] text-[11px]">
                                <span class="font-semibold text-[#53645B]">Catatan Operasional:</span>
                                <span class="text-[#172923] italic ml-1">"{{ $tray->notes }}"</span>
                            </div>
                        @endif
                    </div>

                    @if ($tray->evidencePhotos->isNotEmpty())
                        <div class="space-y-2">
                            <span class="eyebrow-label block">Foto Bukti Fisik</span>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($tray->evidencePhotos as $photo)
                                    <a href="{{ route('evidence-photos.show', $photo) }}" target="_blank" rel="noopener"
                                       class="group relative w-16 h-16 rounded-xl overflow-hidden border border-[#DCE5DD] bg-[#FAFBF9] flex items-center justify-center hover:ring-2 hover:ring-[#1C6654]">
                                        <img src="{{ route('evidence-photos.show', $photo) }}" alt="Bukti Ompreng" class="w-full h-full object-cover">
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Mark Ready Action Form -->
                    @if ($isInUse)
                        <div class="p-4 rounded-xl bg-[#FEF7EC] border border-[#F7D7A3] space-y-3.5">
                            <div class="flex items-center gap-2 text-xs font-bold text-[#885D1C]">
                                <svg class="w-4 h-4 text-[#885D1C] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Formulir Kesiapan Wadah Ompreng</span>
                            </div>

                            <form action="{{ route('school.trays.ready', $tray) }}" method="POST" enctype="multipart/form-data" class="space-y-3.5">
                                @csrf
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                    <div>
                                        <label for="ready_count_{{ $tray->id }}" class="block font-semibold text-[#172923] mb-1">
                                            Jumlah Wadah Siap Fisik <span class="text-[#9B3232]">*</span>
                                        </label>
                                        <input type="number"
                                               id="ready_count_{{ $tray->id }}"
                                               name="ready_count"
                                               min="0"
                                               value="{{ old('ready_count', $tray->expected_trays) }}"
                                               required
                                               class="min-h-[44px] w-full px-3.5 py-2 rounded-xl border border-[#DCE5DD] bg-white text-[#172923] font-bold text-sm tabular-nums focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                        <span class="text-[11px] text-[#53645B] mt-1 block">Target awal diterima: {{ $tray->expected_trays }} ompreng.</span>
                                    </div>
                                    <div>
                                        <label for="damaged_count_{{ $tray->id }}" class="block font-semibold text-[#172923] mb-1">
                                            Jumlah Wadah Rusak / Pecah (Jika Ada)
                                        </label>
                                        <input type="number"
                                               id="damaged_count_{{ $tray->id }}"
                                               name="damaged_count"
                                               min="0"
                                               value="{{ old('damaged_count', 0) }}"
                                               class="min-h-[44px] w-full px-3.5 py-2 rounded-xl border border-[#DCE5DD] bg-white text-[#172923] text-sm tabular-nums focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                        <span class="text-[11px] text-[#53645B] mt-1 block">Diisi 0 jika seluruh wadah utuh.</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                    <div>
                                        <label for="notes_{{ $tray->id }}" class="block font-semibold text-[#172923] mb-1">
                                            Catatan / Keterangan Selisih
                                        </label>
                                        <input type="text"
                                               id="notes_{{ $tray->id }}"
                                               name="notes"
                                               placeholder="Contoh: Wadah telah dicuci bersih dan dikumpulkan"
                                               class="min-h-[44px] w-full px-3.5 py-2 rounded-xl border border-[#DCE5DD] bg-white text-[#172923] text-xs focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                    </div>
                                    <div>
                                        <label for="photo_{{ $tray->id }}" class="block font-semibold text-[#172923] mb-1">
                                            Foto Bukti Wadah Siap (Opsional)
                                        </label>
                                        <input type="file"
                                               id="photo_{{ $tray->id }}"
                                               name="photo"
                                               accept="image/*"
                                               class="min-h-[44px] w-full text-xs text-[#53645B] file:mr-3 file:py-2 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#1C6654] file:text-white hover:file:bg-[#164D42]">
                                    </div>
                                </div>

                                <div class="pt-1">
                                    <button type="submit"
                                            class="min-h-[44px] w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-[#1C6654] text-white font-bold text-xs hover:bg-[#164D42] subtle-shadow smooth-transition cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span>Konfirmasi Ompreng Siap Dijemput</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @elseif ($tray->status === \App\Enums\TrayReturnStatus::READY_FOR_PICKUP)
                        <div class="p-3.5 rounded-xl bg-[#FEF7EC] border border-[#F7D7A3] text-xs text-[#885D1C] flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-[#885D1C] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Ompreng telah dikonfirmasi siap ({{ $tray->ready_count }} wadah). Menunggu penjemputan kurir.</span>
                        </div>
                    @endif
                </article>
            @empty
                <div class="luxury-surface p-10 text-center text-xs text-[#53645B] space-y-2">
                    <div class="w-12 h-12 rounded-2xl bg-[#EDF7F2] text-[#1C6654] flex items-center justify-center mx-auto mb-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <p class="section-title">Belum Ada Riwayat Ompreng</p>
                    <p class="max-w-md mx-auto leading-relaxed">Riwayat siklus wadah otomatis dibuat setelah sekolah Anda menyelesaikan Berita Acara Penerimaan Makanan (P04).</p>
                </div>
            @endforelse

            @if ($trayReturns->hasPages())
                <div class="pt-2">
                    {{ $trayReturns->links() }}
                </div>
            @endif
        </section>
    </div>
</x-app-shell>
