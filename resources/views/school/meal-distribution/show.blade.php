<x-app-shell title="Layanan Pembagian Makanan | {{ $school->name }}">
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Back Navigation & Status -->
        <header class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-[#DCE5DD]">
            <a href="{{ route('school.meal-distribution.index') }}"
               class="min-h-[44px] inline-flex items-center gap-1.5 text-xs font-bold text-[#53645B] hover:text-[#172923] smooth-transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Kembali ke Daftar Pembagian (P05)</span>
            </a>

            <div class="flex items-center gap-2">
                <span class="text-xs text-[#53645B]">Status Layanan:</span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border {{ $record->status->badgeClass() }}">
                    {{ $record->status->label() }}
                </span>
            </div>
        </header>

        <!-- Flash & Validation Alerts -->
        @if (session('success'))
            <div class="p-4 rounded-2xl bg-[#EDF7F2] border border-[#BCE2D0] text-xs font-semibold text-[#21684B] flex items-start gap-3">
                <svg class="w-5 h-5 text-[#21684B] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="leading-relaxed">{{ session('success') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-2xl bg-[#FDF2F2] border border-[#F5C2C2] text-xs text-[#9B3232] flex items-start gap-3">
                <svg class="w-5 h-5 text-[#9B3232] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="space-y-1">
                    <strong class="font-bold block">Terdapat kendala validasi:</strong>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- School Service Entity Card -->
        <section aria-labelledby="service-entity-heading" class="luxury-surface p-5 sm:p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="eyebrow-label text-[#1C6654] bg-[#EDF7F2] border border-[#BCE2D0] px-2.5 py-0.5 rounded-md">
                            Layanan Makanan Sekolah
                        </span>
                        <span class="text-xs text-[#53645B] font-mono">ID: #{{ $record->id }}</span>
                    </div>
                    <h1 id="service-entity-heading" class="page-title">{{ $school->name }}</h1>
                    <p class="text-xs text-[#53645B] mt-0.5">{{ $school->address }}</p>
                </div>

                <div class="inset-surface sm:bg-transparent sm:border-0 p-3 sm:p-0 text-left sm:text-right text-xs text-[#53645B] space-y-1">
                    <div><span>Trip Pengantaran:</span> <span class="font-mono font-bold text-[#172923]">{{ $trip?->trip_code ?? 'TIDAK TERDAFTAR' }}</span></div>
                    <div><span>Kurir Pengantar:</span> <span class="font-semibold text-[#172923]">{{ $trip?->driver?->name ?? 'Kurir' }}</span></div>
                    <div><span>Diterima Oleh PIC:</span> <span class="font-semibold text-[#172923]">{{ $record->receiver?->name ?? 'PIC Sekolah' }}</span></div>
                </div>
            </div>

            <!-- Physical Portion Metrics -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs inset-surface p-4">
                <div>
                    <span class="text-[#53645B] block text-[11px]">Manifes Dikirim</span>
                    <span class="font-bold text-[#172923] tabular-nums text-base mt-0.5 block">{{ $record->expected_portions }} porsi</span>
                </div>
                <div>
                    <span class="text-[#53645B] block text-[11px]">Diterima Fisik</span>
                    <span class="font-bold text-[#172923] tabular-nums text-base mt-0.5 block">{{ $record->received_portions ?? 0 }} porsi</span>
                </div>
                <div>
                    <span class="text-[#53645B] block text-[11px]">Porsi Rusak</span>
                    <span class="font-bold {{ (int) $record->damaged_portions > 0 ? 'text-[#9B3232]' : 'text-[#172923]' }} tabular-nums text-base mt-0.5 block">
                        {{ $record->damaged_portions ?? 0 }} porsi
                    </span>
                </div>
                <div>
                    <span class="text-[#53645B] block text-[11px]">Batas Layak Konsumsi</span>
                    <span class="font-bold text-[#21684B] tabular-nums text-base mt-0.5 block">{{ $usablePortions }} porsi</span>
                </div>
            </div>
        </section>

        @if ($record->isHeld() || $record->isRejected())
            <div class="p-5 rounded-2xl bg-[#FDF2F2] border border-[#F5C2C2] text-[#9B3232] space-y-2">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#9B3232] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <h2 class="text-sm font-bold">Layanan Makanan Ditahan (Food Safety Hold)</h2>
                </div>
                <p class="text-xs leading-relaxed">
                    Makanan tidak memenuhi syarat keamanan pangan organoleptik pada pemeriksaan sekolah dan dilarang dibagikan kepada siswa. Insiden keselamatan pangan otomatis diteruskan ke Pengawas Gizi dan Kepala SPPG.
                </p>
                @if ($record->notes)
                    <div class="text-xs bg-white p-3 rounded-xl border border-[#F5C2C2] mt-2 text-[#172923]">
                        <span class="font-bold text-[#9B3232]">Catatan Pemeriksaan:</span> {{ $record->notes }}
                    </div>
                @endif
            </div>
        @endif

        <!-- Stage 1: ON_ARRIVAL Inspection Summary -->
        <section aria-labelledby="stage-1-heading" class="luxury-surface p-5 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-[#E7ECE8]">
                <div class="flex items-center gap-2.5">
                    <span class="w-6 h-6 rounded-full bg-[#EDF7F2] text-[#1C6654] text-xs font-bold flex items-center justify-center">1</span>
                    <h2 id="stage-1-heading" class="text-xs font-bold text-[#172923] uppercase tracking-wider">Hasil Pemeriksaan Saat Kedatangan (ON_ARRIVAL)</h2>
                </div>
                @if ($arrivalQc)
                    <span class="text-xs font-bold {{ $arrivalQc->conclusion === \App\Enums\QualityCheckConclusion::SAFE ? 'text-[#21684B]' : 'text-[#9B3232]' }}">
                        Kesimpulan: {{ $arrivalQc->conclusion->label() }}
                    </span>
                @else
                    <span class="text-xs text-[#53645B]">Pemeriksaan kedatangan tercatat</span>
                @endif
            </div>

            @if ($arrivalQc)
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                    <div class="inset-surface p-3">
                        <span class="text-[11px] text-[#53645B] block">Rupa / Tampilan</span>
                        <span class="font-bold mt-0.5 block {{ $arrivalQc->appearance_ok ? 'text-[#21684B]' : 'text-[#9B3232]' }}">
                            {{ $arrivalQc->appearance_ok ? 'Sesuai / Baik' : 'Tidak Normal' }}
                        </span>
                    </div>
                    <div class="inset-surface p-3">
                        <span class="text-[11px] text-[#53645B] block">Aroma</span>
                        <span class="font-bold mt-0.5 block {{ $arrivalQc->aroma_ok ? 'text-[#21684B]' : 'text-[#9B3232]' }}">
                            {{ $arrivalQc->aroma_ok ? 'Segar / Normal' : 'Menyengat / Tidak Normal' }}
                        </span>
                    </div>
                    <div class="inset-surface p-3">
                        <span class="text-[11px] text-[#53645B] block">Tekstur</span>
                        <span class="font-bold mt-0.5 block {{ $arrivalQc->texture_ok ? 'text-[#21684B]' : 'text-[#9B3232]' }}">
                            {{ $arrivalQc->texture_ok ? 'Layak' : 'Basi / Lembek' }}
                        </span>
                    </div>
                    <div class="inset-surface p-3">
                        <span class="text-[11px] text-[#53645B] block">Uji Rasa</span>
                        <span class="font-bold mt-0.5 block {{ $arrivalQc->taste_tested && $arrivalQc->taste_ok ? 'text-[#21684B]' : 'text-[#172923]' }}">
                            {{ $arrivalQc->taste_tested ? ($arrivalQc->taste_ok ? 'Sesuai' : 'Tidak Wajar') : 'Tidak Dicicipi' }}
                        </span>
                    </div>
                </div>
            @endif
        </section>

        <!-- Stage 2: BEFORE_CONSUMPTION Check -->
        <section aria-labelledby="stage-2-heading" class="luxury-surface p-5 sm:p-6 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-[#E7ECE8]">
                <div class="flex items-center gap-2.5">
                    <span class="w-6 h-6 rounded-full bg-[#1C6654] text-white text-xs font-bold flex items-center justify-center shrink-0">2</span>
                    <div>
                        <h2 id="stage-2-heading" class="text-xs font-bold text-[#172923] uppercase tracking-wider">Pemeriksaan Sebelum Makan (BEFORE_CONSUMPTION)</h2>
                        <span class="text-[11px] text-[#53645B]">Wajib dilakukan sesaat sebelum makanan dibagikan kepada siswa</span>
                    </div>
                </div>

                @if ($preConsumptionQc)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold {{ $preConsumptionQc->conclusion === \App\Enums\QualityCheckConclusion::SAFE ? 'bg-[#EDF7F2] text-[#21684B] border border-[#BCE2D0]' : 'bg-[#FDF2F2] text-[#9B3232] border border-[#F5C2C2]' }}">
                        {{ $preConsumptionQc->conclusion->label() }}
                    </span>
                @endif
            </div>

            @if (in_array($record->status, [\App\Enums\SchoolReceiptStatus::RECEIVED, \App\Enums\SchoolReceiptStatus::RECEIVED_WITH_DISCREPANCY], true))
                <form action="{{ route('school.meal-distribution.pre-consumption-check', $record) }}"
                      method="POST"
                      enctype="multipart/form-data"
                      class="space-y-4">
                    @csrf

                    <div class="p-3.5 rounded-xl bg-[#FEF7EC] border border-[#F7D7A3] text-xs text-[#885D1C] leading-relaxed">
                        <strong>Perhatian PIC Sekolah:</strong> Pastikan makanan masih berada dalam batas suhu aman, tanpa tanda kerusakan, bau basi, atau perubahan tekstur akibat waktu tunggu. Makanan yang dicurigai tidak aman <strong>dilarang dicicipi</strong>.
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Appearance -->
                        <div class="space-y-1.5">
                            <span class="block text-xs font-bold text-[#172923]">1. Rupa &amp; Tampilan Fisik <span class="text-[#9B3232]">*</span></span>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <label class="min-h-[44px] px-3 py-2 rounded-xl border border-[#DCE5DD] bg-[#FAFBF9] hover:bg-white inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="appearance_ok" value="1" class="text-[#1C6654] focus:ring-[#1C6654]" required checked>
                                    <span class="font-medium text-[#172923]">Normal / Baik</span>
                                </label>
                                <label class="min-h-[44px] px-3 py-2 rounded-xl border border-[#DCE5DD] bg-[#FAFBF9] hover:bg-white inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="appearance_ok" value="0" class="text-[#9B3232] focus:ring-[#9B3232]">
                                    <span class="font-medium text-[#9B3232]">Rusak / Lendir</span>
                                </label>
                            </div>
                        </div>

                        <!-- Aroma -->
                        <div class="space-y-1.5">
                            <span class="block text-xs font-bold text-[#172923]">2. Aroma Makanan <span class="text-[#9B3232]">*</span></span>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <label class="min-h-[44px] px-3 py-2 rounded-xl border border-[#DCE5DD] bg-[#FAFBF9] hover:bg-white inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="aroma_ok" value="1" class="text-[#1C6654] focus:ring-[#1C6654]" required checked>
                                    <span class="font-medium text-[#172923]">Harum Normal</span>
                                </label>
                                <label class="min-h-[44px] px-3 py-2 rounded-xl border border-[#DCE5DD] bg-[#FAFBF9] hover:bg-white inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="aroma_ok" value="0" class="text-[#9B3232] focus:ring-[#9B3232]">
                                    <span class="font-medium text-[#9B3232]">Asam / Basi</span>
                                </label>
                            </div>
                        </div>

                        <!-- Texture -->
                        <div class="space-y-1.5">
                            <span class="block text-xs font-bold text-[#172923]">3. Tekstur <span class="text-[#9B3232]">*</span></span>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <label class="min-h-[44px] px-3 py-2 rounded-xl border border-[#DCE5DD] bg-[#FAFBF9] hover:bg-white inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="texture_ok" value="1" class="text-[#1C6654] focus:ring-[#1C6654]" required checked>
                                    <span class="font-medium text-[#172923]">Matang Wajar</span>
                                </label>
                                <label class="min-h-[44px] px-3 py-2 rounded-xl border border-[#DCE5DD] bg-[#FAFBF9] hover:bg-white inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="texture_ok" value="0" class="text-[#9B3232] focus:ring-[#9B3232]">
                                    <span class="font-medium text-[#9B3232]">Lembek / Basi</span>
                                </label>
                            </div>
                        </div>

                        <!-- Temperature -->
                        <div class="space-y-1.5">
                            <label for="temperature_c" class="block text-xs font-bold text-[#172923]">4. Suhu Makanan (°C) <span class="text-[#53645B] font-normal">(opsional)</span></label>
                            <input type="number" step="0.1" name="temperature_c" id="temperature_c"
                                   placeholder="Contoh: 60.5"
                                   class="min-h-[44px] w-full text-xs rounded-xl border border-[#DCE5DD] bg-white px-3.5 py-2 text-[#172923] tabular-nums focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                        </div>
                    </div>

                    <!-- Taste Test -->
                    <div class="inset-surface p-4 space-y-3">
                        <span class="block text-xs font-bold text-[#172923]">5. Uji Cicip Rasa Petugas</span>
                        <label class="min-h-[44px] inline-flex items-center gap-2.5 text-xs cursor-pointer">
                            <input type="checkbox" name="taste_tested" value="1" id="taste_tested" class="w-4 h-4 rounded border-[#DCE5DD] text-[#1C6654] focus:ring-[#1C6654]">
                            <span class="font-medium text-[#172923]">Petugas mencicipi sampel porsi sebelum dibagikan</span>
                        </label>

                        <div id="taste_details" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3 border-t border-[#E7ECE8]">
                            <div class="space-y-1.5">
                                <span class="text-[11px] text-[#53645B] block font-semibold">Hasil Rasa:</span>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <label class="min-h-[44px] px-3 py-2 rounded-xl border border-[#DCE5DD] bg-white inline-flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="taste_ok" value="1" class="text-[#1C6654] focus:ring-[#1C6654]">
                                        <span class="font-medium text-[#172923]">Enak / Normal</span>
                                    </label>
                                    <label class="min-h-[44px] px-3 py-2 rounded-xl border border-[#DCE5DD] bg-white inline-flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="taste_ok" value="0" class="text-[#9B3232] focus:ring-[#9B3232]">
                                        <span class="font-medium text-[#9B3232]">Asam / Aneh</span>
                                    </label>
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label for="taste_skipped_reason" class="text-[11px] text-[#53645B] block font-semibold">Alasan jika Uji Cicip Dilewati:</label>
                                <input type="text" name="taste_skipped_reason" id="taste_skipped_reason"
                                       placeholder="Contoh: Tampilan mencurigakan / kemasan dingin"
                                       class="min-h-[44px] w-full text-xs rounded-xl border border-[#DCE5DD] bg-white px-3.5 py-2 text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                            </div>
                        </div>
                    </div>

                    <!-- Conclusion & Photo -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label for="conclusion" class="block text-xs font-bold text-[#172923]">Kesimpulan Pemeriksaan <span class="text-[#9B3232]">*</span></label>
                            <select name="conclusion" id="conclusion" class="min-h-[44px] w-full text-xs rounded-xl border border-[#DCE5DD] bg-white px-3.5 py-2 text-[#172923] font-semibold focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                <option value="SAFE" selected>Lolos Uji Konsumsi (SAFE) : Siap Dibagikan</option>
                                <option value="UNSAFE">TIDAK AMAN (UNSAFE) : Tanda Kerusakan</option>
                                <option value="HOLD">Tahan Sementara (HOLD) : Verifikasi Gizi</option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label for="photo" class="block text-xs font-bold text-[#172923]">Foto Bukti Pemeriksaan <span class="text-[#53645B] font-normal">(opsional)</span></label>
                            <input type="file" name="photo" id="photo" accept="image/*" class="min-h-[44px] w-full text-xs text-[#53645B] file:mr-3 file:py-2 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#EDF7F2] file:text-[#1C6654] hover:file:bg-[#DCF0E6]">
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label for="notes" class="block text-xs font-bold text-[#172923]">Catatan Pengamatan Organoleptik:</label>
                        <textarea name="notes" id="notes" rows="2" placeholder="Catat kondisi fisik makanan saat pemeriksaan..." class="w-full text-xs rounded-xl border border-[#DCE5DD] bg-white p-3 text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654]"></textarea>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="min-h-[44px] w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-[#1C6654] hover:bg-[#164D42] smooth-transition subtle-shadow cursor-pointer">
                            Simpan Hasil Pemeriksaan Sebelum Makan
                        </button>
                    </div>
                </form>
            @elseif ($preConsumptionQc)
                <div class="space-y-3">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                        <div class="inset-surface p-3">
                            <span class="text-[11px] text-[#53645B] block">Rupa / Tampilan</span>
                            <span class="font-bold mt-0.5 block {{ $preConsumptionQc->appearance_ok ? 'text-[#21684B]' : 'text-[#9B3232]' }}">
                                {{ $preConsumptionQc->appearance_ok ? 'Normal / Bersih' : 'Bermasalah' }}
                            </span>
                        </div>
                        <div class="inset-surface p-3">
                            <span class="text-[11px] text-[#53645B] block">Aroma</span>
                            <span class="font-bold mt-0.5 block {{ $preConsumptionQc->aroma_ok ? 'text-[#21684B]' : 'text-[#9B3232]' }}">
                                {{ $preConsumptionQc->aroma_ok ? 'Harum Normal' : 'Asam / Basi' }}
                            </span>
                        </div>
                        <div class="inset-surface p-3">
                            <span class="text-[11px] text-[#53645B] block">Tekstur</span>
                            <span class="font-bold mt-0.5 block {{ $preConsumptionQc->texture_ok ? 'text-[#21684B]' : 'text-[#9B3232]' }}">
                                {{ $preConsumptionQc->texture_ok ? 'Matang Wajar' : 'Lembek / Rusak' }}
                            </span>
                        </div>
                        <div class="inset-surface p-3">
                            <span class="text-[11px] text-[#53645B] block">Uji Rasa</span>
                            <span class="font-bold mt-0.5 block text-[#172923]">
                                {{ $preConsumptionQc->taste_tested ? ($preConsumptionQc->taste_ok ? 'Sesuai' : 'Tidak Normal') : 'Tidak Dicicipi' }}
                            </span>
                        </div>
                    </div>

                    <div class="text-xs text-[#53645B] flex flex-wrap items-center gap-4 pt-1">
                        <div><span>Waktu Pemeriksaan:</span> <span class="font-semibold text-[#172923] tabular-nums">{{ $preConsumptionQc->checked_at?->translatedFormat('d M Y, H:i') }} WIB</span></div>
                        <div><span>Pemeriksa:</span> <span class="font-semibold text-[#172923]">{{ $preConsumptionQc->inspector?->name ?? 'PIC' }}</span></div>
                        @if ($preConsumptionQc->temperature_c)
                            <div><span>Suhu:</span> <span class="font-mono font-bold text-[#172923] tabular-nums">{{ $preConsumptionQc->temperature_c }}°C</span></div>
                        @endif
                    </div>
                </div>
            @endif
        </section>

        <!-- Stage 3: Start Meal Distribution -->
        @if ($record->status === \App\Enums\SchoolReceiptStatus::CLEARED_TO_SERVE)
            <section aria-labelledby="stage-3-heading" class="luxury-surface p-5 sm:p-6 border-[#1C6654] space-y-4">
                <div class="flex items-center gap-2.5 pb-3 border-b border-[#E7ECE8]">
                    <span class="w-6 h-6 rounded-full bg-[#1C6654] text-white text-xs font-bold flex items-center justify-center">3</span>
                    <h2 id="stage-3-heading" class="text-xs font-bold text-[#172923] uppercase tracking-wider">Mulai Pembagian Makanan</h2>
                </div>

                <div class="p-3.5 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-xs text-[#21684B] leading-relaxed">
                    Makanan telah dinyatakan <strong>Lolos Uji Konsumsi (CLEARED_TO_SERVE)</strong>. Sebanyak <strong>{{ $usablePortions }} porsi</strong> siap dibagikan kepada siswa sesuai jadwal jam makan sekolah.
                </div>

                <form action="{{ route('school.meal-distribution.start', $record) }}" method="POST">
                    @csrf
                    <button type="submit" class="min-h-[44px] w-full sm:w-auto px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-[#1C6654] hover:bg-[#164D42] smooth-transition subtle-shadow flex items-center justify-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Catat Mulai Pembagian Makanan</span>
                    </button>
                </form>
            </section>
        @elseif (in_array($record->status, [\App\Enums\SchoolReceiptStatus::DISTRIBUTED, \App\Enums\SchoolReceiptStatus::MEAL_COMPLETED], true))
            <div class="luxury-surface p-4 flex items-center justify-between text-xs">
                <div class="flex items-center gap-2.5">
                    <span class="w-6 h-6 rounded-full bg-[#1C6654] text-white text-xs font-bold flex items-center justify-center">3</span>
                    <span class="font-bold text-[#172923]">Mulai Pembagian Makanan</span>
                </div>
                <div class="font-mono font-semibold text-[#53645B] tabular-nums">
                    {{ $record->distribution_started_at ? $record->distribution_started_at->translatedFormat('d M Y, H:i') . ' WIB' : 'Tercatat' }}
                </div>
            </div>
        @endif

        <!-- Stage 4: Complete Meal Service & Portion Realization -->
        @if ($record->status === \App\Enums\SchoolReceiptStatus::DISTRIBUTED)
            <section aria-labelledby="stage-4-heading" class="luxury-surface p-5 sm:p-6 border-[#285F80] space-y-4">
                <div class="flex items-center gap-2.5 pb-3 border-b border-[#E7ECE8]">
                    <span class="w-6 h-6 rounded-full bg-[#285F80] text-white text-xs font-bold flex items-center justify-center shrink-0">4</span>
                    <div>
                        <h2 id="stage-4-heading" class="text-xs font-bold text-[#172923] uppercase tracking-wider">Realisasi Sajian &amp; Tutup Kegiatan Makan</h2>
                        <span class="text-[11px] text-[#53645B]">Masukkan porsi terbagi nyata dan porsi sisa yang tidak termakan</span>
                    </div>
                </div>

                <form action="{{ route('school.meal-distribution.complete', $record) }}"
                      method="POST"
                      enctype="multipart/form-data"
                      class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label for="served_portions" class="block text-xs font-bold text-[#172923]">
                                Porsi Dibagikan / Dikonsumsi Siswa <span class="text-[#9B3232]">*</span>
                            </label>
                            <input type="number" name="served_portions" id="served_portions"
                                   value="{{ old('served_portions', $usablePortions) }}"
                                   min="0" max="{{ $usablePortions }}" required
                                   class="min-h-[44px] w-full text-sm font-bold tabular-nums rounded-xl border border-[#DCE5DD] bg-white px-3.5 py-2 text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                            <span class="text-[11px] text-[#53645B] block">Maksimum: {{ $usablePortions }} porsi layak</span>
                        </div>

                        <div class="space-y-1.5">
                            <label for="leftover_portions" class="block text-xs font-bold text-[#172923]">
                                Porsi Sisa / Tidak Dibagikan
                            </label>
                            <input type="number" name="leftover_portions" id="leftover_portions"
                                   value="{{ old('leftover_portions', 0) }}"
                                   min="0" max="{{ $usablePortions }}"
                                   class="min-h-[44px] w-full text-sm font-bold tabular-nums rounded-xl border border-[#DCE5DD] bg-white px-3.5 py-2 text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                            <span class="text-[11px] text-[#53645B] block">Siswa absen atau porsi cadangan berlebih</span>
                        </div>
                    </div>

                    <div class="inset-surface p-3.5 text-xs text-[#53645B] leading-relaxed">
                        <strong>Aturan Rekonsiliasi:</strong> Total porsi dibagikan ditambah porsi sisa tidak melebihi batas porsi layak konsumsi ({{ $usablePortions }} porsi).
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label for="complete_notes" class="block text-xs font-bold text-[#172923]">Catatan Penutupan Layanan:</label>
                            <input type="text" name="notes" id="complete_notes" placeholder="Catatan kegiatan makan..." class="min-h-[44px] w-full text-xs rounded-xl border border-[#DCE5DD] bg-white px-3.5 py-2 text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                        </div>

                        <div class="space-y-1.5">
                            <label for="complete_photo" class="block text-xs font-bold text-[#172923]">Foto Dokumentasi <span class="text-[#53645B] font-normal">(opsional)</span></label>
                            <input type="file" name="photo" id="complete_photo" accept="image/*" class="min-h-[44px] w-full text-xs text-[#53645B] file:mr-3 file:py-2 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#EDF7F2] file:text-[#1C6654]">
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="min-h-[44px] w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-[#1C6654] hover:bg-[#164D42] smooth-transition subtle-shadow cursor-pointer">
                            Tutup Kegiatan Makan (Selesai)
                        </button>
                    </div>
                </form>
            </section>
        @elseif ($record->status === \App\Enums\SchoolReceiptStatus::MEAL_COMPLETED)
            <section aria-labelledby="completed-meal-heading" class="luxury-surface p-5 sm:p-6 border-[#BCE2D0] space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-[#E7ECE8]">
                    <div class="flex items-center gap-2.5">
                        <span class="w-6 h-6 rounded-full bg-[#21684B] text-white text-xs font-bold flex items-center justify-center">✓</span>
                        <h2 id="completed-meal-heading" class="text-xs font-bold text-[#172923] uppercase tracking-wider">Kegiatan Makan Telah Resmi Ditutup (MEAL_COMPLETED)</h2>
                    </div>
                    <span class="text-xs font-mono text-[#53645B] tabular-nums">
                        {{ $record->meal_completed_at?->translatedFormat('d M Y, H:i') }} WIB
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs inset-surface p-4">
                    <div>
                        <span class="text-[#53645B] block text-[11px]">Realisasi Terbagi</span>
                        <span class="font-bold text-[#21684B] tabular-nums text-base mt-0.5 block">{{ $record->served_portions }} porsi</span>
                    </div>
                    <div>
                        <span class="text-[#53645B] block text-[11px]">Porsi Sisa</span>
                        <span class="font-bold text-[#172923] tabular-nums text-base mt-0.5 block">{{ $record->leftover_portions }} porsi</span>
                    </div>
                    <div>
                        <span class="text-[#53645B] block text-[11px]">Waktu Penutupan</span>
                        <span class="font-bold text-[#172923] tabular-nums text-sm mt-0.5 block">{{ $record->meal_completed_at?->translatedFormat('H:i') }} WIB</span>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="text-xs text-[#172923] leading-relaxed">
                        <strong>Langkah Selanjutnya:</strong> Wadah ompreng dapat dihitung dan ditandai siap dijemput oleh armada logistik kurir.
                    </div>
                    <a href="{{ route('school.trays.index') }}"
                       class="min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-[#1C6654] hover:bg-[#164D42] smooth-transition shrink-0 subtle-shadow">
                        <span>Lanjut ke Penyiapan Ompreng (P06)</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>
            </section>
        @endif
    </div>
</x-app-shell>
