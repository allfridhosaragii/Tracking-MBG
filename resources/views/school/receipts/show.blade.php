<x-app-shell title="Pemeriksaan & Penerimaan Makanan | {{ $school->name }}">
    <div class="space-y-6">
        <!-- Back Navigation & Status -->
        <header class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('school.receipts.index') }}"
               class="min-h-[44px] inline-flex items-center gap-1.5 text-xs font-bold text-[#53645B] hover:text-[#172923] smooth-transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>Daftar Penerimaan Makanan (P04)</span>
            </a>
            <div class="flex items-center gap-2">
                <span class="eyebrow-label">Halaman P04</span>
                <span class="text-xs px-2.5 py-1 rounded-lg font-bold border {{ $stop->status->badgeClass() }}">
                    {{ $stop->status->label() }}
                </span>
            </div>
        </header>

        <!-- Arrival Identity Card -->
        <section aria-labelledby="arrival-school-heading" class="luxury-surface p-5 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-[#E7ECE8]">
                <div class="flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-[#1C6654] text-white flex items-center justify-center text-sm font-bold shrink-0 mt-0.5 tabular-nums">
                        {{ $stop->sequence_number }}
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 id="arrival-school-heading" class="page-title">{{ $school->name }}</h1>
                            <span class="text-xs font-mono text-[#53645B] bg-[#FAFBF9] border border-[#DCE5DD] px-2.5 py-0.5 rounded-lg">Kode: {{ $school->school_code }}</span>
                        </div>
                        <p class="text-xs text-[#53645B] mt-0.5 leading-relaxed">{{ $school->address }}</p>
                    </div>
                </div>
                <div class="text-left sm:text-right shrink-0">
                    <span class="eyebrow-label block">Waktu Kedatangan Armada</span>
                    <span class="text-sm font-bold text-[#172923] tabular-nums block mt-0.5">
                        {{ $stop->arrived_at ? $stop->arrived_at->setTimezone('Asia/Jakarta')->format('H:i:s \W\I\B') : 'Belum tiba' }}
                    </span>
                    <span class="text-[11px] text-[#53645B] block font-mono mt-0.5">Trip: {{ $trip->trip_code }}</span>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs text-[#53645B]">
                <div>
                    <span class="eyebrow-label block mb-0.5">Kurir Pengantar</span>
                    <span class="font-bold text-[#172923]">{{ $trip->driver?->name ?? 'Kurir SPPG' }}</span>
                </div>
                <div>
                    <span class="eyebrow-label block mb-0.5">Nomor Plat Armada</span>
                    <span class="font-bold font-mono text-[#172923]">{{ $trip->vehicle?->plate_number ?? '-' }}</span>
                </div>
                <div>
                    <span class="eyebrow-label block mb-0.5">Menu Makanan</span>
                    <span class="font-bold text-[#172923]">{{ $primaryMenu?->name ?? $primaryMenu?->recipe_name ?? 'Menu Standar MBG' }}</span>
                </div>
                <div>
                    <span class="eyebrow-label block mb-0.5">Kode Batch Produksi</span>
                    <span class="font-bold font-mono text-[#1C6654]">{{ $primaryBatch?->batch_code ?? '-' }}</span>
                </div>
            </div>
        </section>

        @php
            $isConfirmed = $serviceRecord && $serviceRecord->isConfirmed();
            $arrivalQc = $stop->qualityChecks->where('check_stage', \App\Enums\QualityCheckStage::ON_ARRIVAL)->sortByDesc('created_at')->first();
            $driverPhoto = $stop->evidencePhotos->where('evidence_type', 'SCHOOL_HANDOVER')->sortByDesc('created_at')->first();
            $expectedPortions = $serviceRecord?->expected_portions ?: ((int) ($stop->deliveryItems->sum('loaded_portions') ?: $stop->loaded_portions));
        @endphp

        @if ($isConfirmed)
            <!-- Confirmed Receipt Certificate (Read-Only) -->
            <section aria-labelledby="receipt-certificate-heading" class="luxury-surface border-2 {{ $serviceRecord->status === \App\Enums\SchoolReceiptStatus::REJECTED || $serviceRecord->status === \App\Enums\SchoolReceiptStatus::HELD ? 'border-[#9B3232]' : 'border-[#1C6654]' }} p-5 sm:p-6 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-[#E7ECE8]">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl {{ $serviceRecord->status === \App\Enums\SchoolReceiptStatus::REJECTED || $serviceRecord->status === \App\Enums\SchoolReceiptStatus::HELD ? 'bg-[#FDF2F2] text-[#9B3232] border border-[#F5C2C2]' : 'bg-[#EDF7F2] text-[#21684B] border border-[#BCE2D0]' }} flex items-center justify-center font-bold shrink-0">
                            @if ($serviceRecord->status === \App\Enums\SchoolReceiptStatus::REJECTED || $serviceRecord->status === \App\Enums\SchoolReceiptStatus::HELD)
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @endif
                        </div>
                        <div>
                            <span class="eyebrow-label block">Berita Acara Penerimaan Tercatat</span>
                            <h2 id="receipt-certificate-heading" class="text-lg font-bold text-[#172923]">{{ $serviceRecord->status->label() }}</h2>
                        </div>
                    </div>
                    <div class="text-left sm:text-right">
                        <span class="text-xs px-3 py-1 rounded-lg font-bold border {{ $serviceRecord->status->badgeClass() }}">
                            Status: {{ $serviceRecord->status->value }}
                        </span>
                        <span class="text-[11px] text-[#53645B] block mt-1.5 tabular-nums">
                            Konfirmasi Elektronik: {{ $serviceRecord->received_at?->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s \W\I\B') }}
                        </span>
                    </div>
                </div>

                @if ($serviceRecord->status === \App\Enums\SchoolReceiptStatus::HELD || $serviceRecord->status === \App\Enums\SchoolReceiptStatus::REJECTED)
                    <div class="p-4 rounded-xl bg-[#FDF2F2] border border-[#F5C2C2] text-[#9B3232] text-xs space-y-1">
                        <strong class="font-bold flex items-center gap-1.5">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>PERINGATAN KESELAMATAN PANGAN : DISTRIBUSI DITAHAN</span>
                        </strong>
                        <p class="leading-relaxed">
                            Hasil pemeriksaan organoleptik kedatangan menunjukkan ketidaklayakan pangan. Makanan TIDAK BOLEH didistribusikan kepada peserta didik. Insiden otomatis diteruskan ke Pengawas Gizi dan Kepala SPPG.
                        </p>
                    </div>
                @endif

                @php
                    $overageCount = max(0, $serviceRecord->received_portions - $serviceRecord->expected_portions);
                @endphp
                <div class="grid grid-cols-2 {{ $overageCount > 0 ? 'sm:grid-cols-5' : 'sm:grid-cols-4' }} gap-3 text-center">
                    <div class="inset-surface p-3.5">
                        <span class="text-[10px] text-[#53645B] block font-semibold">Target / Dikirim</span>
                        <span class="text-base font-bold text-[#172923] tabular-nums mt-0.5 block">{{ $serviceRecord->expected_portions }}</span>
                        <span class="text-[10px] text-[#53645B]">Porsi</span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0]">
                        <span class="text-[10px] text-[#21684B] block font-semibold">Fisik Diterima</span>
                        <span class="text-base font-bold text-[#21684B] tabular-nums mt-0.5 block">{{ $serviceRecord->received_portions }}</span>
                        <span class="text-[10px] text-[#21684B]">Porsi</span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-[#FEF7EC] border border-[#F7D7A3]">
                        <span class="text-[10px] text-[#885D1C] block font-semibold">Porsi Rusak</span>
                        <span class="text-base font-bold text-[#885D1C] tabular-nums mt-0.5 block">{{ $serviceRecord->damaged_portions }}</span>
                        <span class="text-[10px] text-[#885D1C]">Porsi</span>
                    </div>
                    <div class="p-3.5 rounded-xl border {{ $serviceRecord->shortage_portions > 0 ? 'bg-[#FDF2F2] border-[#F5C2C2] text-[#9B3232]' : 'inset-surface text-[#172923]' }}">
                        <span class="text-[10px] block font-semibold">Selisih Kurang</span>
                        <span class="text-base font-bold tabular-nums mt-0.5 block">{{ $serviceRecord->shortage_portions }}</span>
                        <span class="text-[10px]">Porsi</span>
                    </div>
                    @if ($overageCount > 0)
                        <div class="p-3.5 rounded-xl bg-[#F0F6FA] border border-[#C2DBE8] text-[#285F80]">
                            <span class="text-[10px] block font-semibold">Selisih Lebih (Surplus)</span>
                            <span class="text-base font-bold tabular-nums mt-0.5 block">+{{ $overageCount }}</span>
                            <span class="text-[10px]">Porsi Ekstra</span>
                        </div>
                    @endif
                </div>

                @if ($stop->deliveryItems->count() > 1)
                    <div class="inset-surface p-4 space-y-2.5">
                        <span class="eyebrow-label block">Status Rekonsiliasi Per Batch</span>
                        <div class="space-y-2">
                            @foreach ($stop->deliveryItems as $dItem)
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-3 rounded-xl bg-white border border-[#DCE5DD] text-xs gap-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-mono font-bold text-[#1C6654]">{{ $dItem->foodBatch?->batch_code ?? 'BATCH-' . $dItem->id }}</span>
                                        <span class="text-[#53645B]">({{ $dItem->foodBatch?->menu?->name ?? $dItem->foodBatch?->menu?->recipe_name ?? 'Menu MBG' }})</span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span class="text-[#53645B]">Manifes: <strong class="text-[#172923] tabular-nums">{{ $dItem->loaded_portions ?? 0 }}</strong></span>
                                        @if ($dItem->delivered_portions !== null)
                                            <span class="text-[#21684B] font-bold bg-[#EDF7F2] px-2.5 py-0.5 rounded-md border border-[#BCE2D0] tabular-nums">
                                                Terkirim: {{ $dItem->delivered_portions }} Porsi
                                            </span>
                                        @else
                                            <span class="text-[#885D1C] font-bold bg-[#FEF7EC] px-2.5 py-0.5 rounded-md border border-[#F7D7A3]">
                                                Atribusi Belum Terekonsiliasi (Unresolved)
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($arrivalQc)
                    <div class="inset-surface p-4 space-y-3">
                        <div class="flex items-center justify-between pb-2.5 border-b border-[#E7ECE8]">
                            <h3 class="text-xs font-bold text-[#172923] uppercase tracking-wider">Hasil Uji Organoleptik Kedatangan</h3>
                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-md border {{ $arrivalQc->status->badgeClass() }}">
                                {{ $arrivalQc->status->label() }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5 text-xs">
                            <div class="p-2.5 rounded-lg bg-white border border-[#DCE5DD]">
                                <span class="text-[10px] text-[#53645B] block">Tampilan</span>
                                <span class="font-bold text-[#172923]">{{ $arrivalQc->appearance_status ?? '-' }}</span>
                            </div>
                            <div class="p-2.5 rounded-lg bg-white border border-[#DCE5DD]">
                                <span class="text-[10px] text-[#53645B] block">Aroma</span>
                                <span class="font-bold text-[#172923]">{{ $arrivalQc->aroma_status ?? '-' }}</span>
                            </div>
                            <div class="p-2.5 rounded-lg bg-white border border-[#DCE5DD]">
                                <span class="text-[10px] text-[#53645B] block">Tekstur</span>
                                <span class="font-bold text-[#172923]">{{ $arrivalQc->texture_status ?? '-' }}</span>
                            </div>
                            <div class="p-2.5 rounded-lg bg-white border border-[#DCE5DD]">
                                <span class="text-[10px] text-[#53645B] block">Uji Rasa</span>
                                <span class="font-bold text-[#172923]">{{ $arrivalQc->taste_status ?? ($arrivalQc->taste_tested ? 'Normal' : 'Dilewati') }}</span>
                            </div>
                            <div class="p-2.5 rounded-lg bg-white border border-[#DCE5DD] col-span-2 sm:col-span-1">
                                <span class="text-[10px] text-[#53645B] block">Suhu Kedatangan</span>
                                <span class="font-bold text-[#172923] tabular-nums">{{ $arrivalQc->temperature_c ? "{$arrivalQc->temperature_c} °C" : '-' }}</span>
                            </div>
                        </div>
                        @if ($arrivalQc->taste_skipped_reason)
                            <div class="text-[11px] text-[#885D1C] bg-[#FEF7EC] p-2.5 rounded-lg border border-[#F7D7A3]">
                                <strong>Alasan Uji Rasa Dilewati:</strong> {{ $arrivalQc->taste_skipped_reason }}
                            </div>
                        @endif
                    </div>
                @endif

                <div class="space-y-2 pt-3 border-t border-[#E7ECE8]">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs text-[#53645B]">
                        <div>
                            <span>Petugas Penerima (Autentikasi Akun):</span>
                            <strong class="text-[#172923] ml-1">{{ $serviceRecord->receiver?->name ?? 'PIC Sekolah' }}</strong>
                        </div>
                        @if ($serviceRecord->notes)
                            <div>
                                <span>Catatan:</span>
                                <span class="text-[#172923] ml-1">{{ $serviceRecord->notes }}</span>
                            </div>
                        @endif
                    </div>
                    <p class="text-[11px] text-[#53645B] leading-relaxed">
                        Konfirmasi Penerimaan Elektronik tercatat resmi berdasarkan autentikasi akun PIC Sekolah, stempel waktu server, dan riwayat log audit transaksi.
                    </p>
                </div>

                @if ($serviceRecord->isConfirmed())
                    <div class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="text-xs text-[#172923] leading-relaxed">
                            <strong>Langkah Selanjutnya:</strong> Serah terima makanan selesai. Lakukan pemeriksaan kualitas sesaat sebelum makan dan realisasi pembagian di halaman P05.
                        </div>
                        <a href="{{ route('school.meal-distribution.show', $serviceRecord) }}"
                           class="min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-[#1C6654] hover:bg-[#164D42] smooth-transition shrink-0 subtle-shadow">
                            <span>Lanjut ke Pembagian Makanan (P05)</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </a>
                    </div>
                @endif
            </section>
        @else
            <!-- Receipt Inspection & Confirmation Form (P04) -->
            <form action="{{ route('school.receipts.confirm', $stop) }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="space-y-6"
                  id="receipt-confirmation-form">
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    <!-- Left 7 Columns: Portion Count & Organoleptic Check -->
                    <div class="lg:col-span-7 space-y-6">
                        @if ($expectedPortions <= 0)
                            <div class="p-4 rounded-2xl bg-[#FDF2F2] border border-[#F5C2C2] text-[#9B3232] text-xs space-y-1">
                                <strong class="font-bold flex items-center gap-1.5">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <span>MANIFEST MUATAN TIDAK VALID / BELUM DIMUAT</span>
                                </strong>
                                <p class="leading-relaxed">Muatan porsi terverifikasi pada perhentian ini tercatat 0. Koordinasikan dengan kurir atau Kepala SPPG bila terdapat kekeliruan manifes.</p>
                            </div>
                        @endif

                        @if ($stop->deliveryItems->count() > 1)
                            <section aria-labelledby="multi-batch-heading" class="luxury-surface p-5 space-y-4">
                                <div class="flex items-center justify-between pb-3 border-b border-[#E7ECE8]">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-full bg-[#1C6654] text-white text-xs font-bold flex items-center justify-center">A</span>
                                        <h2 id="multi-batch-heading" class="section-title">Metode Verifikasi Muatan Multi-Batch</h2>
                                    </div>
                                    <span class="text-xs font-bold text-[#1C6654] tabular-nums">{{ $stop->deliveryItems->count() }} Batch ({{ $expectedPortions }} Porsi)</span>
                                </div>

                                <p class="text-xs text-[#53645B] leading-relaxed">
                                    Armada ini membawa beberapa batch makanan berbeda. Pilih metode penghitungan fisik yang benar-benar Anda lakukan untuk menjaga integritas data rantai pasok:
                                </p>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" role="radiogroup" aria-label="Metode Verifikasi Muatan">
                                    <label id="mode-b-card" class="relative flex items-start gap-3 p-4 rounded-xl border-2 smooth-transition cursor-pointer {{ old('verification_mode', 'AGGREGATE') === 'AGGREGATE' ? 'border-[#1C6654] bg-[#EDF7F2]' : 'border-[#DCE5DD] bg-white hover:bg-[#FAFBF9]' }}">
                                        <input type="radio"
                                               name="verification_mode"
                                               id="mode_aggregate"
                                               value="AGGREGATE"
                                               {{ old('verification_mode', 'AGGREGATE') === 'AGGREGATE' ? 'checked' : '' }}
                                               onchange="switchVerificationMode('AGGREGATE')"
                                               class="mt-1 text-[#1C6654] focus:ring-[#1C6654]">
                                        <div class="text-xs">
                                            <span class="font-bold text-[#172923] block mb-0.5">Mode B : Verifikasi Total Fisik Saja</span>
                                            <span class="text-[11px] text-[#53645B] block leading-relaxed">
                                                Hanya memastikan total fisik seluruh kontainer yang diterima tanpa memilah batch satu per satu.
                                            </span>
                                        </div>
                                    </label>

                                    <label id="mode-a-card" class="relative flex items-start gap-3 p-4 rounded-xl border-2 smooth-transition cursor-pointer {{ old('verification_mode') === 'PER_BATCH' ? 'border-[#1C6654] bg-[#EDF7F2]' : 'border-[#DCE5DD] bg-white hover:bg-[#FAFBF9]' }}">
                                        <input type="radio"
                                               name="verification_mode"
                                               id="mode_per_batch"
                                               value="PER_BATCH"
                                               {{ old('verification_mode') === 'PER_BATCH' ? 'checked' : '' }}
                                               onchange="switchVerificationMode('PER_BATCH')"
                                               class="mt-1 text-[#1C6654] focus:ring-[#1C6654]">
                                        <div class="text-xs">
                                            <span class="font-bold text-[#172923] block mb-0.5">Mode A : Verifikasi Rinci Tiap Batch</span>
                                            <span class="text-[11px] text-[#53645B] block leading-relaxed">
                                                Memeriksa, membedakan, dan menghitung fisik porsi tiap kontainer batch secara terpisah.
                                            </span>
                                        </div>
                                    </label>
                                </div>

                                <div id="mode-b-notice" class="inset-surface p-3.5 text-xs space-y-1.5 {{ old('verification_mode', 'AGGREGATE') === 'PER_BATCH' ? 'hidden' : '' }}">
                                    <div class="flex items-center gap-1.5 font-bold text-[#172923]">
                                        <svg class="w-4 h-4 text-[#1C6654] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>Mode B Aktif: Penghitungan Total Langsung</span>
                                    </div>
                                    <p class="text-[11px] text-[#53645B] leading-relaxed">
                                        Input per-batch dinonaktifkan. Masukkan total fisik yang Anda terima langsung pada Langkah 1 di bawah. Jika terjadi selisih, sistem mencatat status atribusi sebagai <strong class="text-[#172923]">Unresolved Discrepancy</strong> tanpa membuat data distribusi fiktif.
                                    </p>
                                </div>

                                <div id="mode-a-container" class="space-y-3 {{ old('verification_mode', 'AGGREGATE') === 'PER_BATCH' ? '' : 'hidden opacity-50' }}">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-[#172923]">Rincian Pemeriksaan Fisik Tiap Batch</span>
                                        <span class="text-[11px] text-[#53645B]">Total disinkronkan otomatis</span>
                                    </div>

                                    <div id="batch-discrepancy-warning" class="hidden p-3.5 rounded-xl bg-[#FEF7EC] border border-[#F7D7A3] text-xs text-[#885D1C] space-y-1">
                                        <div class="font-bold flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-[#885D1C] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            <span>Perhatian: Terdeteksi Selisih Fisik Pada Batch</span>
                                        </div>
                                        <p class="text-[11px]" id="batch-discrepancy-desc">
                                            Terdapat batch dengan porsi diterima tidak sama dengan muatan atau terdapat porsi rusak. Catatan keterangan wajib diisi.
                                        </p>
                                    </div>

                                    <div class="space-y-3" id="batch-items-list">
                                        @foreach ($stop->deliveryItems as $item)
                                            <div class="inset-surface p-3.5 space-y-2.5 batch-card" data-item-id="{{ $item->id }}" data-loaded="{{ $item->loaded_portions ?? 0 }}">
                                                <div class="flex items-center justify-between text-xs">
                                                    <div>
                                                        <span class="font-mono font-bold text-[#1C6654]">{{ $item->foodBatch?->batch_code ?? 'BATCH-' . $item->id }}</span>
                                                        <span class="text-[11px] text-[#53645B] block">{{ $item->foodBatch?->menu?->name ?? $item->foodBatch?->menu?->recipe_name ?? 'Menu MBG' }}</span>
                                                    </div>
                                                    <div class="text-right">
                                                        <span class="font-bold text-[#172923] tabular-nums">{{ $item->loaded_portions ?? 0 }} Porsi</span>
                                                        <span class="text-[10px] text-[#53645B] block">Dimuat di SPPG</span>
                                                    </div>
                                                </div>
                                                <div class="grid grid-cols-2 gap-3 pt-2 border-t border-[#E7ECE8]">
                                                    <div>
                                                        <label for="item_rec_{{ $item->id }}" class="block text-[10px] font-bold text-[#53645B] mb-1">Porsi Diterima Fisik</label>
                                                        <input type="number"
                                                               name="items[{{ $item->id }}][received_portions]"
                                                               id="item_rec_{{ $item->id }}"
                                                               value="{{ old('items.' . $item->id . '.received_portions', $item->loaded_portions ?? 0) }}"
                                                               min="0"
                                                               class="min-h-[44px] batch-item-received w-full text-xs text-[#172923] rounded-xl border border-[#DCE5DD] px-3 py-2 bg-white font-bold tabular-nums focus:outline-none focus:ring-2 focus:ring-[#1C6654]"
                                                               oninput="syncBatchTotals()">
                                                    </div>
                                                    <div>
                                                        <label for="item_dmg_{{ $item->id }}" class="block text-[10px] font-bold text-[#53645B] mb-1">Porsi Rusak / Tumpah</label>
                                                        <input type="number"
                                                               name="items[{{ $item->id }}][damaged_portions]"
                                                               id="item_dmg_{{ $item->id }}"
                                                               value="{{ old('items.' . $item->id . '.damaged_portions', 0) }}"
                                                               min="0"
                                                               class="min-h-[44px] batch-item-damaged w-full text-xs text-[#172923] rounded-xl border border-[#DCE5DD] px-3 py-2 bg-white font-bold tabular-nums focus:outline-none focus:ring-2 focus:ring-[#1C6654]"
                                                               oninput="syncBatchTotals()">
                                                    </div>
                                                </div>
                                                <div class="flex items-center justify-between text-[11px] pt-1">
                                                    <span class="text-[#53645B]">Status Batch:</span>
                                                    <span class="font-bold tabular-nums batch-status-badge text-[#1C6654]" id="batch_status_{{ $item->id }}">Sesuai Manifest</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </section>
                        @else
                            <input type="hidden" name="verification_mode" value="SINGLE_BATCH">
                        @endif

                        <!-- Step 1: Physical Portion Count -->
                        <section aria-labelledby="physical-count-heading" class="luxury-surface p-5 space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-[#E7ECE8]">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-[#1C6654] text-white text-xs font-bold flex items-center justify-center">1</span>
                                    <h2 id="physical-count-heading" class="section-title">Penghitungan Porsi Fisik</h2>
                                </div>
                                <span class="text-xs font-bold text-[#1C6654] tabular-nums">
                                    Muatan SPPG: {{ $expectedPortions }} Porsi
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="received_portions" class="block text-xs font-bold text-[#172923] mb-1.5">
                                        Total Porsi Fisik Diterima <span class="text-[#9B3232]">*</span>
                                    </label>
                                    <input type="number"
                                           name="received_portions"
                                           id="received_portions"
                                           min="0"
                                           max="5000"
                                           value="{{ old('received_portions', $expectedPortions) }}"
                                           required
                                           class="min-h-[44px] w-full text-sm font-bold text-[#172923] rounded-xl border border-[#DCE5DD] px-3.5 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] tabular-nums"
                                           oninput="calculateDiscrepancy()">
                                    <span class="text-[11px] text-[#53645B] mt-1 block" id="rec-hint">Total ompreng yang diserahkan kurir.</span>
                                    @error('received_portions')
                                        <span class="text-xs text-[#9B3232] font-medium block mt-1">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label for="damaged_portions" class="block text-xs font-bold text-[#172923] mb-1.5">
                                        Porsi Rusak / Tumpah (Bagian Diterima)
                                    </label>
                                    <input type="number"
                                           name="damaged_portions"
                                           id="damaged_portions"
                                           min="0"
                                           max="5000"
                                           value="{{ old('damaged_portions', 0) }}"
                                           class="min-h-[44px] w-full text-sm font-bold text-[#172923] rounded-xl border border-[#DCE5DD] px-3.5 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] tabular-nums"
                                           oninput="calculateDiscrepancy()">
                                    <span class="text-[11px] text-[#53645B] mt-1 block">Wadah pecah/isi tercemar saat perjalanan.</span>
                                    @error('damaged_portions')
                                        <span class="text-xs text-[#9B3232] font-medium block mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Live Derived Discrepancy Preview -->
                            <div class="inset-surface p-4 flex items-center justify-between gap-3 text-xs" id="discrepancy-card">
                                <div>
                                    <span class="font-bold text-[#172923] block" id="discrepancy-title">Status Rekonsiliasi Muatan:</span>
                                    <span class="text-[11px] text-[#53645B]" id="discrepancy-desc">Diterima lengkap sesuai manifest muatan.</span>
                                    <span class="text-[11px] text-[#885D1C] font-semibold block mt-1 hidden" id="attribution-mode-note">
                                        Catatan atribusi batch: Unresolved (penghitungan Mode B total gabungan).
                                    </span>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-lg font-bold tabular-nums text-[#1C6654]" id="discrepancy-value">0</span>
                                    <span class="text-[10px] text-[#53645B] block" id="discrepancy-label">Selisih Porsi</span>
                                </div>
                            </div>
                        </section>

                        <!-- Step 2: Organoleptic & Food Safety Check -->
                        <section aria-labelledby="organoleptic-heading" class="luxury-surface p-5 space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-[#E7ECE8]">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-[#1C6654] text-white text-xs font-bold flex items-center justify-center">2</span>
                                    <h2 id="organoleptic-heading" class="section-title">Uji Organoleptik Kedatangan</h2>
                                </div>
                                <span class="text-[11px] text-[#53645B] font-medium">Standar Gizi &amp; Keamanan</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="appearance_status" class="block text-xs font-bold text-[#172923] mb-1.5">
                                        Tampilan Fisik <span class="text-[#9B3232]">*</span>
                                    </label>
                                    <select name="appearance_status"
                                            id="appearance_status"
                                            required
                                            class="min-h-[44px] w-full text-xs text-[#172923] rounded-xl border border-[#DCE5DD] px-3.5 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                        <option value="FRESH" {{ old('appearance_status') === 'FRESH' ? 'selected' : '' }}>Segar &amp; Bersih (Sesuai)</option>
                                        <option value="ABNORMAL" {{ old('appearance_status') === 'ABNORMAL' ? 'selected' : '' }}>Abnormal / Berubah Warna</option>
                                        <option value="SPOILED" {{ old('appearance_status') === 'SPOILED' ? 'selected' : '' }}>Basi / Berlendir / Rusak Parah</option>
                                    </select>
                                    @error('appearance_status')
                                        <span class="text-xs text-[#9B3232] font-medium block mt-1">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label for="aroma_status" class="block text-xs font-bold text-[#172923] mb-1.5">
                                        Aroma / Bau <span class="text-[#9B3232]">*</span>
                                    </label>
                                    <select name="aroma_status"
                                            id="aroma_status"
                                            required
                                            class="min-h-[44px] w-full text-xs text-[#172923] rounded-xl border border-[#DCE5DD] px-3.5 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                        <option value="NORMAL" {{ old('aroma_status') === 'NORMAL' ? 'selected' : '' }}>Harum Masakan Normal (Segar)</option>
                                        <option value="SOUR" {{ old('aroma_status') === 'SOUR' ? 'selected' : '' }}>Asam / Menyengat</option>
                                        <option value="RANCID" {{ old('aroma_status') === 'RANCID' ? 'selected' : '' }}>Tengik / Basi</option>
                                        <option value="CHEMICAL" {{ old('aroma_status') === 'CHEMICAL' ? 'selected' : '' }}>Bau Kimia / Asing</option>
                                    </select>
                                    @error('aroma_status')
                                        <span class="text-xs text-[#9B3232] font-medium block mt-1">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label for="texture_status" class="block text-xs font-bold text-[#172923] mb-1.5">
                                        Tekstur <span class="text-[#9B3232]">*</span>
                                    </label>
                                    <select name="texture_status"
                                            id="texture_status"
                                            required
                                            class="min-h-[44px] w-full text-xs text-[#172923] rounded-xl border border-[#DCE5DD] px-3.5 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                        <option value="NORMAL" {{ old('texture_status') === 'NORMAL' ? 'selected' : '' }}>Matang Normal &amp; Empuk</option>
                                        <option value="SLIMY" {{ old('texture_status') === 'SLIMY' ? 'selected' : '' }}>Berlendir / Lengket</option>
                                        <option value="DRY" {{ old('texture_status') === 'DRY' ? 'selected' : '' }}>Terlalu Kering / Keras</option>
                                        <option value="SOGGY" {{ old('texture_status') === 'SOGGY' ? 'selected' : '' }}>Lembek Berair / Hancur</option>
                                    </select>
                                    @error('texture_status')
                                        <span class="text-xs text-[#9B3232] font-medium block mt-1">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label for="temperature_c" class="block text-xs font-bold text-[#172923] mb-1.5">
                                        Suhu Kedatangan (°C)
                                    </label>
                                    <input type="number"
                                           step="0.1"
                                           name="temperature_c"
                                           id="temperature_c"
                                           placeholder="Contoh: 60.5"
                                           value="{{ old('temperature_c', 60.0) }}"
                                           class="min-h-[44px] w-full text-xs text-[#172923] rounded-xl border border-[#DCE5DD] px-3.5 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] tabular-nums">
                                    <span class="text-[11px] text-[#53645B] mt-1 block">Standar makanan hangat ≥ 60°C.</span>
                                    @error('temperature_c')
                                        <span class="text-xs text-[#9B3232] font-medium block mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Safety-First Taste Test -->
                            <div class="pt-3 border-t border-[#E7ECE8] space-y-3">
                                <label for="taste_tested" class="min-h-[44px] inline-flex items-center gap-2.5 cursor-pointer">
                                    <input type="checkbox"
                                           name="taste_tested"
                                           id="taste_tested"
                                           value="1"
                                           {{ old('taste_tested', '1') ? 'checked' : '' }}
                                           onchange="toggleTasteTest()"
                                           class="w-4 h-4 rounded text-[#1C6654] focus:ring-[#1C6654]">
                                    <span class="text-xs font-bold text-[#172923]">Uji Rasa Dilakukan (Cicipi Sampel)</span>
                                </label>
                                <span class="text-[11px] text-[#53645B] block -mt-1 leading-relaxed">
                                    Jangan mencicipi jika makanan berbau basi, tengik, atau berlendir demi keselamatan pangan.
                                </span>

                                <div id="taste-section-enabled" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="taste_status" class="block text-xs font-bold text-[#172923] mb-1.5">
                                            Kualitas Rasa
                                        </label>
                                        <select name="taste_status"
                                                id="taste_status"
                                                class="min-h-[44px] w-full text-xs text-[#172923] rounded-xl border border-[#DCE5DD] px-3.5 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                            <option value="DELICIOUS_NORMAL" {{ old('taste_status') === 'DELICIOUS_NORMAL' ? 'selected' : '' }}>Enak &amp; Bumbu Pas (Normal)</option>
                                            <option value="OFF_TASTE" {{ old('taste_status') === 'OFF_TASTE' ? 'selected' : '' }}>Rasa Aneh / Asing</option>
                                            <option value="BITTER_SOUR" {{ old('taste_status') === 'BITTER_SOUR' ? 'selected' : '' }}>Pahit / Asam / Basi</option>
                                        </select>
                                    </div>
                                </div>

                                <div id="taste-section-disabled" class="hidden">
                                    <label for="taste_skipped_reason" class="block text-xs font-bold text-[#885D1C] mb-1.5">
                                        Alasan Uji Rasa Dilewati <span class="text-[#9B3232]">*</span>
                                    </label>
                                    <input type="text"
                                           name="taste_skipped_reason"
                                           id="taste_skipped_reason"
                                           placeholder="Contoh: Makanan tercium asam/basi, berisiko jika dicicipi"
                                           value="{{ old('taste_skipped_reason') }}"
                                           class="min-h-[44px] w-full text-xs text-[#172923] rounded-xl border border-[#F7D7A3] bg-[#FEF7EC] px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#885D1C]">
                                    @error('taste_skipped_reason')
                                        <span class="text-xs text-[#9B3232] font-medium block mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Final Organoleptic Decision -->
                            <div class="pt-3 border-t border-[#E7ECE8] space-y-2">
                                <label for="organoleptic_result" class="block text-xs font-bold text-[#172923]">
                                    Keputusan Kelayakan Pangan <span class="text-[#9B3232]">*</span>
                                </label>
                                <select name="organoleptic_result"
                                        id="organoleptic_result"
                                        required
                                        onchange="updateSafetyWarning()"
                                        class="min-h-[44px] w-full text-xs font-bold text-[#172923] rounded-xl border border-[#DCE5DD] px-3.5 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                    <option value="SAFE" {{ old('organoleptic_result') === 'SAFE' ? 'selected' : '' }}>
                                        LAYAK &amp; AMAN (Boleh Disajikan ke Siswa)
                                    </option>
                                    <option value="WARNING" {{ old('organoleptic_result') === 'WARNING' ? 'selected' : '' }}>
                                        DENGAN CATATAN (Aman, namun ada catatan kemasan/suhu)
                                    </option>
                                    <option value="UNSAFE" {{ old('organoleptic_result') === 'UNSAFE' ? 'selected' : '' }}>
                                        TIDAK LAYAK / BERBAHAYA (TAHAN &amp; BATALKAN DISTRIBUSI)
                                    </option>
                                </select>
                                <div id="safety-alert" class="hidden p-3.5 rounded-xl bg-[#FDF2F2] border border-[#F5C2C2] text-[#9B3232] text-xs">
                                    <strong>Peringatan Tegas:</strong> Memilih "TIDAK LAYAK / BERBAHAYA" membekukan batch makanan sekolah ini dalam status HELD/REJECTED sehingga tidak dapat didistribusikan ke siswa.
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- Right 5 Columns: Driver Handover Photo, PIC Evidence & Submit -->
                    <div class="lg:col-span-5 space-y-6">
                        <section aria-labelledby="driver-photo-heading" class="luxury-surface p-5 space-y-3">
                            <h2 id="driver-photo-heading" class="section-title">Foto Serah Terima Kurir</h2>

                            @if ($driverPhoto)
                                <div class="relative rounded-xl overflow-hidden border border-[#DCE5DD] bg-[#FAFBF9] aspect-video flex items-center justify-center">
                                    <img src="{{ route('evidence-photos.show', $driverPhoto) }}"
                                         alt="Bukti Serah Terima Kurir"
                                         class="w-full h-full object-cover">
                                </div>
                                <div class="text-[11px] text-[#53645B] space-y-0.5">
                                    <div class="flex justify-between">
                                        <span>Diunggah pada:</span>
                                        <span class="font-bold text-[#172923] tabular-nums">
                                            {{ $driverPhoto->created_at->setTimezone('Asia/Jakarta')->format('H:i:s \W\I\B') }}
                                        </span>
                                    </div>
                                    @if ($driverPhoto->notes)
                                        <span class="block text-[#172923] italic">"{{ $driverPhoto->notes }}"</span>
                                    @endif
                                </div>
                            @else
                                <div class="inset-surface p-4 text-center text-xs text-[#53645B]">
                                    Kurir belum mengunggah foto serah terima. Anda tetap dapat melanjutkan konfirmasi fisik.
                                </div>
                            @endif
                        </section>

                        <section aria-labelledby="pic-evidence-heading" class="luxury-surface p-5 space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-[#E7ECE8]">
                                <h2 id="pic-evidence-heading" class="section-title">Bukti Foto PIC Sekolah</h2>
                                <span class="text-[11px] text-[#53645B]">Wajib bila ada selisih</span>
                            </div>

                            <div>
                                <label for="evidence_photo" class="block text-xs font-bold text-[#172923] mb-1.5">
                                    Unggah Foto Wadah / Selisih / Kondisi Makanan
                                </label>
                                <input type="file"
                                       name="evidence_photo"
                                       id="evidence_photo"
                                       accept="image/*"
                                       capture="environment"
                                       class="min-h-[44px] block w-full text-xs text-[#172923] file:mr-3 file:py-2 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#1C6654] file:text-white hover:file:bg-[#164D42] file:cursor-pointer border border-[#DCE5DD] rounded-xl p-1.5 bg-[#FAFBF9] focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                <span class="text-[11px] text-[#53645B] mt-1 block">
                                    Diperlukan sebagai bukti audit jika terdapat porsi rusak atau selisih kuantitas.
                                </span>
                                @error('evidence_photo')
                                    <span class="text-xs text-[#9B3232] font-medium block mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="notes" class="block text-xs font-bold text-[#172923] mb-1.5">
                                    Catatan Berita Acara Penerimaan
                                </label>
                                <textarea name="notes"
                                          id="notes"
                                          rows="3"
                                          placeholder="Tuliskan keterangan bila ada porsi rusak, selisih jumlah, atau kondisi khusus..."
                                          class="w-full text-xs text-[#172923] rounded-xl border border-[#DCE5DD] bg-white p-3 focus:outline-none focus:ring-2 focus:ring-[#1C6654]">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <span class="text-xs text-[#9B3232] font-medium block mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="pt-2">
                                <button type="submit"
                                        id="submit-receipt-btn"
                                        class="min-h-[48px] w-full py-3.5 px-4 rounded-xl font-bold text-sm bg-[#1C6654] text-white hover:bg-[#164D42] smooth-transition subtle-shadow flex items-center justify-center gap-2 cursor-pointer">
                                    <span>Konfirmasi Penerimaan Elektronik (P04)</span>
                                </button>
                                <span class="text-[11px] text-[#53645B] text-center block mt-2">
                                    Tindakan ini mengesahkan berita acara penerimaan dan mengaktifkan rute armada ke sekolah berikutnya.
                                </span>
                            </div>
                        </section>
                    </div>
                </div>
            </form>

            <script>
                let currentMode = '{{ old('verification_mode', $stop->deliveryItems->count() > 1 ? 'AGGREGATE' : 'SINGLE_BATCH') }}';

                function switchVerificationMode(mode) {
                    currentMode = mode;
                    const container = document.getElementById('mode-a-container');
                    const notice = document.getElementById('mode-b-notice');
                    const cardA = document.getElementById('mode-a-card');
                    const cardB = document.getElementById('mode-b-card');
                    const receivedInputs = document.querySelectorAll('.batch-item-received');
                    const damagedInputs = document.querySelectorAll('.batch-item-damaged');
                    const recTotal = document.getElementById('received_portions');
                    const dmgTotal = document.getElementById('damaged_portions');
                    const recHint = document.getElementById('rec-hint');

                    if (mode === 'PER_BATCH') {
                        if (container) container.classList.remove('hidden', 'opacity-50');
                        if (notice) notice.classList.add('hidden');
                        if (cardA) {
                            cardA.classList.add('border-[#1C6654]', 'bg-[#EDF7F2]');
                            cardA.classList.remove('border-[#DCE5DD]', 'bg-white');
                        }
                        if (cardB) {
                            cardB.classList.remove('border-[#1C6654]', 'bg-[#EDF7F2]');
                            cardB.classList.add('border-[#DCE5DD]', 'bg-white');
                        }

                        receivedInputs.forEach(i => i.disabled = false);
                        damagedInputs.forEach(i => i.disabled = false);

                        if (recTotal) recTotal.readOnly = true;
                        if (dmgTotal) dmgTotal.readOnly = true;
                        if (recHint) recHint.innerText = 'Dihitung otomatis dari penjumlahan rincian per-batch di atas.';

                        syncBatchTotals();
                    } else {
                        if (container) container.classList.add('hidden', 'opacity-50');
                        if (notice) notice.classList.remove('hidden');
                        if (cardB) {
                            cardB.classList.add('border-[#1C6654]', 'bg-[#EDF7F2]');
                            cardB.classList.remove('border-[#DCE5DD]', 'bg-white');
                        }
                        if (cardA) {
                            cardA.classList.remove('border-[#1C6654]', 'bg-[#EDF7F2]');
                            cardA.classList.add('border-[#DCE5DD]', 'bg-white');
                        }

                        receivedInputs.forEach(i => i.disabled = true);
                        damagedInputs.forEach(i => i.disabled = true);

                        if (recTotal) recTotal.readOnly = false;
                        if (dmgTotal) dmgTotal.readOnly = false;
                        if (recHint) recHint.innerText = 'Total ompreng yang diserahkan kurir.';

                        calculateDiscrepancy();
                    }
                }

                function syncBatchTotals() {
                    const batchCards = document.querySelectorAll('.batch-card');
                    let totalRec = 0;
                    let totalDmg = 0;
                    let hasBatchDiscrepancy = false;

                    batchCards.forEach(card => {
                        const itemId = card.dataset.itemId;
                        const loaded = parseInt(card.dataset.loaded) || 0;
                        const recEl = document.getElementById('item_rec_' + itemId);
                        const dmgEl = document.getElementById('item_dmg_' + itemId);
                        const statusEl = document.getElementById('batch_status_' + itemId);

                        const recVal = recEl ? (parseInt(recEl.value) || 0) : 0;
                        const dmgVal = dmgEl ? (parseInt(dmgEl.value) || 0) : 0;

                        totalRec += recVal;
                        totalDmg += dmgVal;

                        if (statusEl) {
                            if (recVal === loaded && dmgVal === 0) {
                                statusEl.innerText = 'Sesuai Manifest';
                                statusEl.className = 'font-bold tabular-nums batch-status-badge text-[#1C6654]';
                            } else {
                                hasBatchDiscrepancy = true;
                                let diffText = '';
                                if (recVal < loaded) diffText = `Kurang ${loaded - recVal}`;
                                else if (recVal > loaded) diffText = `Lebih +${recVal - loaded}`;
                                if (dmgVal > 0) diffText += (diffText ? ', ' : '') + `${dmgVal} Rusak`;

                                statusEl.innerText = diffText;
                                statusEl.className = 'font-bold tabular-nums batch-status-badge text-[#885D1C]';
                            }
                        }
                    });

                    const recEl = document.getElementById('received_portions');
                    if (recEl) recEl.value = totalRec;

                    const dmgEl = document.getElementById('damaged_portions');
                    if (dmgEl) dmgEl.value = totalDmg;

                    const warningBanner = document.getElementById('batch-discrepancy-warning');
                    if (warningBanner) {
                        if (hasBatchDiscrepancy) {
                            warningBanner.classList.remove('hidden');
                        } else {
                            warningBanner.classList.add('hidden');
                        }
                    }

                    calculateDiscrepancy();
                }

                function calculateDiscrepancy() {
                    const expected = {{ (int) $expectedPortions }};
                    const receivedInput = document.getElementById('received_portions');
                    const damagedInput = document.getElementById('damaged_portions');
                    const valEl = document.getElementById('discrepancy-value');
                    const descEl = document.getElementById('discrepancy-desc');
                    const labelEl = document.getElementById('discrepancy-label');
                    const attributionNote = document.getElementById('attribution-mode-note');

                    const received = parseInt(receivedInput ? receivedInput.value : 0) || 0;
                    const damaged = parseInt(damagedInput ? damagedInput.value : 0) || 0;

                    const shortage = Math.max(0, expected - received);
                    const overage = Math.max(0, received - expected);
                    const isMultiBatch = {{ $stop->deliveryItems->count() > 1 ? 'true' : 'false' }};

                    if (attributionNote) {
                        if (isMultiBatch && currentMode === 'AGGREGATE' && (shortage > 0 || overage > 0 || damaged > 0)) {
                            attributionNote.classList.remove('hidden');
                        } else {
                            attributionNote.classList.add('hidden');
                        }
                    }

                    if (shortage === 0 && overage === 0 && damaged === 0) {
                        valEl.innerText = '0';
                        valEl.className = 'text-lg font-bold tabular-nums text-[#1C6654]';
                        labelEl.innerText = 'Selisih Porsi';
                        descEl.innerText = `Diterima lengkap & sesuai manifest. Seluruh ${expected} porsi diterima utuh tanpa kerusakan.`;
                    } else if (shortage > 0) {
                        valEl.innerText = '-' + shortage;
                        valEl.className = 'text-lg font-bold tabular-nums text-[#9B3232]';
                        labelEl.innerText = 'Kekurangan Porsi (Shortage)';
                        if (damaged > 0) {
                            descEl.innerText = `Kekurangan ${shortage} porsi dari muatan (${expected} porsi) dan terdapat ${damaged} porsi rusak. Catatan keterangan wajib diisi.`;
                        } else {
                            descEl.innerText = `Kekurangan ${shortage} porsi dari manifest muatan (${expected} porsi). Catatan keterangan wajib diisi.`;
                        }
                    } else if (overage > 0) {
                        valEl.innerText = '+' + overage;
                        valEl.className = 'text-lg font-bold tabular-nums text-[#285F80]';
                        labelEl.innerText = 'Kelebihan Porsi (Overage)';
                        if (damaged > 0) {
                            descEl.innerText = `Kelebihan fisik +${overage} porsi di atas manifest muatan (${expected} porsi), dan terdapat ${damaged} porsi rusak. Catatan keterangan wajib diisi.`;
                        } else {
                            descEl.innerText = `Kelebihan fisik +${overage} porsi di atas manifest muatan (${expected} porsi). Catatan keterangan wajib diisi.`;
                        }
                    } else if (damaged > 0) {
                        valEl.innerText = damaged;
                        valEl.className = 'text-lg font-bold tabular-nums text-[#9B3232]';
                        labelEl.innerText = 'Porsi Rusak (Damaged)';
                        descEl.innerText = `Jumlah fisik total sesuai (${expected} porsi), namun terdapat ${damaged} porsi rusak/tumpah. Catatan keterangan wajib diisi.`;
                    }
                }

                function toggleTasteTest() {
                    const checkbox = document.getElementById('taste_tested');
                    const enabledSection = document.getElementById('taste-section-enabled');
                    const disabledSection = document.getElementById('taste-section-disabled');
                    const skipReason = document.getElementById('taste_skipped_reason');

                    if (checkbox && checkbox.checked) {
                        if (enabledSection) enabledSection.classList.remove('hidden');
                        if (disabledSection) disabledSection.classList.add('hidden');
                        if (skipReason) skipReason.required = false;
                    } else {
                        if (enabledSection) enabledSection.classList.add('hidden');
                        if (disabledSection) disabledSection.classList.remove('hidden');
                        if (skipReason) skipReason.required = true;
                    }
                }

                function updateSafetyWarning() {
                    const select = document.getElementById('organoleptic_result');
                    const alertBox = document.getElementById('safety-alert');
                    const submitBtn = document.getElementById('submit-receipt-btn');

                    if (select && select.value === 'UNSAFE') {
                        if (alertBox) alertBox.classList.remove('hidden');
                        if (submitBtn) {
                            submitBtn.classList.remove('bg-[#1C6654]', 'hover:bg-[#164D42]');
                            submitBtn.classList.add('bg-[#9B3232]', 'hover:bg-[#7D2828]');
                            submitBtn.innerText = 'TAHAN & BATALKAN DISTRIBUSI (UNSAFE)';
                        }
                    } else {
                        if (alertBox) alertBox.classList.add('hidden');
                        if (submitBtn) {
                            submitBtn.classList.remove('bg-[#9B3232]', 'hover:bg-[#7D2828]');
                            submitBtn.classList.add('bg-[#1C6654]', 'hover:bg-[#164D42]');
                            submitBtn.innerText = 'Konfirmasi Penerimaan Elektronik (P04)';
                        }
                    }
                }

                document.addEventListener('DOMContentLoaded', function () {
                    @if ($stop->deliveryItems->count() > 1)
                        switchVerificationMode('{{ old('verification_mode', 'AGGREGATE') }}');
                    @else
                        calculateDiscrepancy();
                    @endif
                    toggleTasteTest();
                    updateSafetyWarning();
                });
            </script>
        @endif
    </div>
</x-app-shell>
