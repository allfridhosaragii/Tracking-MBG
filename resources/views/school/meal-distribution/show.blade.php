<x-app-shell title="Layanan Pembagian Makanan : {{ $school->name }}">
    <div class="max-w-4xl mx-auto px-4 py-6 sm:px-6 space-y-6">

        <!-- Navigasi Balik -->
        <div class="flex items-center justify-between pb-4 border-b border-[#DCE5DD]">
            <a href="{{ route('school.meal-distribution.index') }}" 
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#53645B] hover:text-[#172923] touch-target">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Kembali ke Daftar Pembagian (P05)</span>
            </a>

            <div class="flex items-center gap-2">
                <span class="text-xs text-[#708177]">Status Layanan:</span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $record->status->badgeClass() }}">
                    {{ $record->status->label() }}
                </span>
            </div>
        </div>

        <!-- Flash Message Alerts -->
        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 flex items-start gap-3 shadow-xs">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="leading-relaxed font-medium">{{ session('success') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 flex items-start gap-3 shadow-xs">
                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

        <!-- Card Entitas Layanan Sekolah -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-bold text-[#1C6654] uppercase tracking-wider bg-[#EDF2ED] px-2 py-0.5 rounded">
                            Layanan Makanan Sekolah
                        </span>
                        <span class="text-xs text-[#53645B] font-mono">ID: #{{ $record->id }}</span>
                    </div>
                    <h1 class="text-lg font-bold text-[#172923] mt-1">{{ $school->name }}</h1>
                    <p class="text-xs text-[#53645B]">{{ $school->address }}</p>
                </div>

                <div class="text-left sm:text-right text-xs text-[#53645B] space-y-1 bg-[#F5F7F3] sm:bg-transparent p-3 sm:p-0 rounded-xl">
                    <div><span class="text-[#708177]">Trip Pengantaran:</span> <span class="font-mono font-bold text-[#172923]">{{ $trip?->trip_code ?? 'TIDAK TERDAFTAR' }}</span></div>
                    <div><span class="text-[#708177]">Kurir Pengantar:</span> <span class="font-semibold text-[#172923]">{{ $trip?->driver?->name ?? 'Kurir' }}</span></div>
                    <div><span class="text-[#708177]">Diterima Oleh PIC:</span> <span class="font-semibold text-[#172923]">{{ $record->receiver?->name ?? 'PIC Sekolah' }}</span></div>
                </div>
            </div>

            <!-- Metrik Porsi Fisik -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs bg-[#F5F7F3] p-4 rounded-xl border border-[#EDF2ED]">
                <div>
                    <span class="text-[#708177] block text-[11px]">Manifest Manifes</span>
                    <span class="font-bold text-[#172923] font-mono text-base">{{ $record->expected_portions }} porsi</span>
                </div>
                <div>
                    <span class="text-[#708177] block text-[11px]">Diterima Fisik</span>
                    <span class="font-bold text-[#172923] font-mono text-base">{{ $record->received_portions ?? 0 }} porsi</span>
                </div>
                <div>
                    <span class="text-[#708177] block text-[11px]">Porsi Rusak</span>
                    <span class="font-bold {{ (int)$record->damaged_portions > 0 ? 'text-rose-700' : 'text-[#172923]' }} font-mono text-base">
                        {{ $record->damaged_portions ?? 0 }} porsi
                    </span>
                </div>
                <div>
                    <span class="text-[#708177] block text-[11px]">Batas Layak Konsumsi</span>
                    <span class="font-bold text-emerald-800 font-mono text-base">{{ $usablePortions }} porsi</span>
                </div>
            </div>
        </div>

        @if ($record->isHeld() || $record->isRejected())
            <!-- Warning State: Makanan Ditahan -->
            <div class="p-5 rounded-2xl bg-rose-50 border-2 border-rose-300 text-rose-900 space-y-2 shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-6 h-6 text-rose-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <h2 class="text-sm font-bold">Layanan Makanan Ditahan (Food Safety Hold)</h2>
                </div>
                <p class="text-xs leading-relaxed">
                    Makanan tidak memenuhi syarat keamanan pangan organoleptik pada pemeriksaan sekolah. Makanan dilarang dibagikan kepada siswa. Insiden keselamatan pangan telah otomatis dilaporkan kepada Pengawas Gizi dan Kepala SPPG.
                </p>
                @if ($record->notes)
                    <div class="text-xs bg-white/70 p-2.5 rounded-lg border border-rose-200 mt-2">
                        <span class="font-semibold text-rose-950">Catatan Pemeriksaan:</span> {{ $record->notes }}
                    </div>
                @endif
            </div>
        @endif

        <!-- Tahap 1: Riwayat Pemeriksaan Kedatangan (ON_ARRIVAL) -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 shadow-xs space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-[#EDF2ED]">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-[#EDF2ED] text-[#1C6654] text-xs font-bold flex items-center justify-center">1</span>
                    <h2 class="text-xs font-bold text-[#172923] uppercase tracking-wider">Hasil Pemeriksaan Saat Kedatangan (ON_ARRIVAL)</h2>
                </div>
                @if ($arrivalQc)
                    <span class="text-xs font-semibold {{ $arrivalQc->conclusion === \App\Enums\QualityCheckConclusion::SAFE ? 'text-emerald-700' : 'text-rose-700' }}">
                        Kesimpulan: {{ $arrivalQc->conclusion->label() }}
                    </span>
                @else
                    <span class="text-xs text-[#708177]">Pemeriksaan kedatangan tercatat</span>
                @endif
            </div>

            @if ($arrivalQc)
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs text-[#53645B]">
                    <div class="p-2 rounded-lg bg-[#F5F7F3]">
                        <span class="text-[10px] text-[#708177] block">Rupa / Tampilan</span>
                        <span class="font-bold {{ $arrivalQc->appearance_ok ? 'text-emerald-800' : 'text-rose-800' }}">
                            {{ $arrivalQc->appearance_ok ? 'Sesuai / Baik' : 'Tidak Normal' }}
                        </span>
                    </div>
                    <div class="p-2 rounded-lg bg-[#F5F7F3]">
                        <span class="text-[10px] text-[#708177] block">Aroma</span>
                        <span class="font-bold {{ $arrivalQc->aroma_ok ? 'text-emerald-800' : 'text-rose-800' }}">
                            {{ $arrivalQc->aroma_ok ? 'Segar / Normal' : 'Menyengat / Tidak Normal' }}
                        </span>
                    </div>
                    <div class="p-2 rounded-lg bg-[#F5F7F3]">
                        <span class="text-[10px] text-[#708177] block">Tekstur</span>
                        <span class="font-bold {{ $arrivalQc->texture_ok ? 'text-emerald-800' : 'text-rose-800' }}">
                            {{ $arrivalQc->texture_ok ? 'Layak' : 'Basi / Lembek' }}
                        </span>
                    </div>
                    <div class="p-2 rounded-lg bg-[#F5F7F3]">
                        <span class="text-[10px] text-[#708177] block">Uji Rasa</span>
                        <span class="font-bold {{ $arrivalQc->taste_tested && $arrivalQc->taste_ok ? 'text-emerald-800' : 'text-[#172923]' }}">
                            {{ $arrivalQc->taste_tested ? ($arrivalQc->taste_ok ? 'Sesuai' : 'Tidak Wajar') : 'Tidak Dicicipi' }}
                        </span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Tahap 2: Pemeriksaan Sebelum Konsumsi (BEFORE_CONSUMPTION) -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-[#EDF2ED]">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-[#1C6654] text-white text-xs font-bold flex items-center justify-center">2</span>
                    <div>
                        <h2 class="text-xs font-bold text-[#172923] uppercase tracking-wider">Pemeriksaan Sebelum Makan (BEFORE_CONSUMPTION)</h2>
                        <span class="text-[10px] text-[#708177]">Wajib dilakukan sesaat sebelum makanan dibagikan kepada siswa</span>
                    </div>
                </div>

                @if ($preConsumptionQc)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ $preConsumptionQc->conclusion === \App\Enums\QualityCheckConclusion::SAFE ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' }}">
                        {{ $preConsumptionQc->conclusion->label() }}
                    </span>
                @endif
            </div>

            @if (in_array($record->status, [\App\Enums\SchoolReceiptStatus::RECEIVED, \App\Enums\SchoolReceiptStatus::RECEIVED_WITH_DISCREPANCY], true))
                <!-- FORM PEMERIKSAAN KEDUA -->
                <form action="{{ route('school.meal-distribution.pre-consumption-check', $record) }}" 
                      method="POST" 
                      enctype="multipart/form-data" 
                      class="space-y-4">
                    @csrf

                    <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 leading-relaxed">
                        <strong>Perhatian PIC Sekolah:</strong> Pastikan makanan masih berada dalam batas suhu aman, tidak ada tanda-tanda kerusakan, bau basi, atau perubahan tekstur akibat waktu tunggu setelah pengantaran. Makanan yang dicurigai tidak aman <strong>dilarang dicicipi</strong>.
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Rupa -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-[#172923]">1. Rupa & Tampilan Fisik <span class="text-rose-600">*</span></label>
                            <div class="flex items-center gap-4 text-xs">
                                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" name="appearance_ok" value="1" class="text-[#1C6654] focus:ring-[#1C6654]" required checked>
                                    <span>Normal / Bersih / Baik</span>
                                </label>
                                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" name="appearance_ok" value="0" class="text-rose-600 focus:ring-rose-500">
                                    <span>Berlendir / Berjamur / Rusak</span>
                                </label>
                            </div>
                        </div>

                        <!-- Aroma -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-[#172923]">2. Aroma Makanan <span class="text-rose-600">*</span></label>
                            <div class="flex items-center gap-4 text-xs">
                                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" name="aroma_ok" value="1" class="text-[#1C6654] focus:ring-[#1C6654]" required checked>
                                    <span>Harum Normal / Tidak Asam</span>
                                </label>
                                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" name="aroma_ok" value="0" class="text-rose-600 focus:ring-rose-500">
                                    <span>Asam / Basi / Menyengat</span>
                                </label>
                            </div>
                        </div>

                        <!-- Tekstur -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-[#172923]">3. Tekstur <span class="text-rose-600">*</span></label>
                            <div class="flex items-center gap-4 text-xs">
                                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" name="texture_ok" value="1" class="text-[#1C6654] focus:ring-[#1C6654]" required checked>
                                    <span>Sesuai Matang Wajar</span>
                                </label>
                                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" name="texture_ok" value="0" class="text-rose-600 focus:ring-rose-500">
                                    <span>Lembek Tidak Wajar / Terpisah</span>
                                </label>
                            </div>
                        </div>

                        <!-- Suhu Opsional -->
                        <div class="space-y-1.5">
                            <label for="temperature_c" class="block text-xs font-bold text-[#172923]">4. Suhu Makanan (°C) <span class="text-[#708177] font-normal">(jika ada termometer)</span></label>
                            <input type="number" step="0.1" name="temperature_c" id="temperature_c" 
                                   placeholder="Contoh: 60.5" 
                                   class="w-full text-xs rounded-lg border border-[#DCE5DD] px-3 py-2 text-[#172923] focus:border-[#1C6654] focus:ring-1 focus:ring-[#1C6654]">
                        </div>
                    </div>

                    <!-- Uji Rasa -->
                    <div class="p-3.5 rounded-xl border border-[#DCE5DD] bg-[#F5F7F3] space-y-3">
                        <label class="block text-xs font-bold text-[#172923]">5. Uji Cicip Rasa Petugas</label>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4 text-xs">
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" name="taste_tested" value="1" id="taste_tested" class="rounded border-[#DCE5DD] text-[#1C6654] focus:ring-[#1C6654]">
                                <span>Petugas mencicipi sampel porsi sebelum dibagikan</span>
                            </label>
                        </div>

                        <div id="taste_details" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-[#EDF2ED]">
                            <div class="space-y-1">
                                <span class="text-[11px] text-[#53645B] block font-semibold">Hasil Rasa:</span>
                                <div class="flex items-center gap-4 text-xs">
                                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="taste_ok" value="1" class="text-[#1C6654] focus:ring-[#1C6654]">
                                        <span>Rasa Enak / Normal</span>
                                    </label>
                                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="taste_ok" value="0" class="text-rose-600 focus:ring-rose-500">
                                        <span>Rasa Asam / Pahit / Aneh</span>
                                    </label>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label for="taste_skipped_reason" class="text-[11px] text-[#53645B] block font-semibold">Alasan jika Uji Cicip Dilewati:</label>
                                <input type="text" name="taste_skipped_reason" id="taste_skipped_reason" 
                                       placeholder="Contoh: Tampilan mencurigakan / Kemasan tersegel dingin" 
                                       class="w-full text-xs rounded-lg border border-[#DCE5DD] px-3 py-1.5 text-[#172923]">
                            </div>
                        </div>
                    </div>

                    <!-- Kesimpulan & Catatan -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label for="conclusion" class="block text-xs font-bold text-[#172923]">Kesimpulan Pemeriksaan <span class="text-rose-600">*</span></label>
                            <select name="conclusion" id="conclusion" class="w-full text-xs rounded-lg border border-[#DCE5DD] px-3 py-2 text-[#172923] focus:border-[#1C6654] focus:ring-1 focus:ring-[#1C6654]">
                                <option value="SAFE" selected>Lolos Uji Konsumsi (SAFE) : Makanan Siap Dibagikan</option>
                                <option value="UNSAFE">TIDAK AMAN (UNSAFE) : Terdapat Tanda Kerusakan</option>
                                <option value="HOLD">Tahan Sementara (HOLD) : Perlu Verifikasi Pengawas Gizi</option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label for="photo" class="block text-xs font-bold text-[#172923]">Foto Bukti Pemeriksaan <span class="text-[#708177] font-normal">(opsional)</span></label>
                            <input type="file" name="photo" id="photo" accept="image/*" class="w-full text-xs file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-[#EDF2ED] file:text-[#1C6654] hover:file:bg-[#EDF2ED]/80">
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label for="notes" class="block text-xs font-bold text-[#172923]">Catatan Pengamatan Organoleptik:</label>
                        <textarea name="notes" id="notes" rows="2" placeholder="Catat kondisi fisik makanan saat pemeriksaan..." class="w-full text-xs rounded-lg border border-[#DCE5DD] p-3 text-[#172923] focus:border-[#1C6654] focus:ring-1 focus:ring-[#1C6654]"></textarea>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-5 py-2.5 rounded-lg text-xs font-bold text-white bg-[#1C6654] hover:bg-[#172923] touch-target smooth-transition shadow-xs">
                            Simpan Hasil Pemeriksaan Sebelum Makan
                        </button>
                    </div>
                </form>
            @elseif ($preConsumptionQc)
                <!-- HASIL PEMERIKSAAN KEDUA TELAH DISIMPAN -->
                <div class="space-y-3">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs text-[#53645B]">
                        <div class="p-2.5 rounded-lg bg-[#F5F7F3]">
                            <span class="text-[10px] text-[#708177] block">Rupa / Tampilan</span>
                            <span class="font-bold {{ $preConsumptionQc->appearance_ok ? 'text-emerald-800' : 'text-rose-800' }}">
                                {{ $preConsumptionQc->appearance_ok ? 'Normal / Bersih' : 'Bermasalah' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-[#F5F7F3]">
                            <span class="text-[10px] text-[#708177] block">Aroma</span>
                            <span class="font-bold {{ $preConsumptionQc->aroma_ok ? 'text-emerald-800' : 'text-rose-800' }}">
                                {{ $preConsumptionQc->aroma_ok ? 'Harum Normal' : 'Asam / Basi' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-[#F5F7F3]">
                            <span class="text-[10px] text-[#708177] block">Tekstur</span>
                            <span class="font-bold {{ $preConsumptionQc->texture_ok ? 'text-emerald-800' : 'text-rose-800' }}">
                                {{ $preConsumptionQc->texture_ok ? 'Matang Wajar' : 'Lembek / Rusak' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-[#F5F7F3]">
                            <span class="text-[10px] text-[#708177] block">Uji Rasa</span>
                            <span class="font-bold text-[#172923]">
                                {{ $preConsumptionQc->taste_tested ? ($preConsumptionQc->taste_ok ? 'Sesuai' : 'Tidak Normal') : 'Tidak Dicicipi' }}
                            </span>
                        </div>
                    </div>

                    <div class="text-xs text-[#53645B] flex flex-wrap items-center gap-4 pt-1">
                        <div><span class="text-[#708177]">Waktu Pemeriksaan:</span> <span class="font-semibold text-[#172923]">{{ $preConsumptionQc->checked_at?->translatedFormat('d M Y, H:i') }} WIB</span></div>
                        <div><span class="text-[#708177]">Pemeriksa:</span> <span class="font-semibold text-[#172923]">{{ $preConsumptionQc->inspector?->name ?? 'PIC' }}</span></div>
                        @if ($preConsumptionQc->temperature_c)
                            <div><span class="text-[#708177]">Suhu:</span> <span class="font-mono font-bold text-[#172923]">{{ $preConsumptionQc->temperature_c }}°C</span></div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Tahap 3: Pelaksanaan Distribusi Makanan -->
        @if ($record->status === \App\Enums\SchoolReceiptStatus::CLEARED_TO_SERVE)
            <div class="bg-white rounded-2xl border-2 border-emerald-300 p-5 shadow-xs space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-[#EDF2ED]">
                    <span class="w-6 h-6 rounded-full bg-emerald-600 text-white text-xs font-bold flex items-center justify-center">3</span>
                    <h2 class="text-xs font-bold text-[#172923] uppercase tracking-wider">Mulai Pembagian Makanan</h2>
                </div>

                <div class="p-3.5 rounded-xl bg-emerald-50 text-xs text-emerald-900 leading-relaxed">
                    Makanan telah dinyatakan <strong>Lolos Uji Konsumsi (CLEARED_TO_SERVE)</strong>. Sebanyak <strong>{{ $usablePortions }} porsi</strong> siap dibagikan kepada siswa sesuai jadwal jam makan sekolah.
                </div>

                <form action="{{ route('school.meal-distribution.start', $record) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full sm:w-auto px-6 py-2.5 rounded-lg text-xs font-bold text-white bg-emerald-700 hover:bg-emerald-800 touch-target smooth-transition shadow-xs flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Catat Mulai Pembagian Makanan</span>
                    </button>
                </form>
            </div>
        @elseif (in_array($record->status, [\App\Enums\SchoolReceiptStatus::DISTRIBUTED, \App\Enums\SchoolReceiptStatus::MEAL_COMPLETED], true))
            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-4 shadow-xs flex items-center justify-between text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-[#1C6654] text-white text-xs font-bold flex items-center justify-center">3</span>
                    <span class="font-bold text-[#172923]">Mulai Pembagian Makanan:</span>
                </div>
                <div class="font-mono text-[#53645B]">
                    {{ $record->distribution_started_at ? $record->distribution_started_at->translatedFormat('d M Y, H:i') . ' WIB' : 'Tercatat' }}
                </div>
            </div>
        @endif

        <!-- Tahap 4: Realisasi Sajian & Penutupan Kegiatan Makan -->
        @if ($record->status === \App\Enums\SchoolReceiptStatus::DISTRIBUTED)
            <div class="bg-white rounded-2xl border-2 border-blue-300 p-5 shadow-xs space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-[#EDF2ED]">
                    <span class="w-6 h-6 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center">4</span>
                    <div>
                        <h2 class="text-xs font-bold text-[#172923] uppercase tracking-wider">Realisasi Sajian & Tutup Kegiatan Makan</h2>
                        <span class="text-[10px] text-[#708177]">Masukkan porsi terbagi nyata dan porsi sisa yang tidak termakan</span>
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
                                Porsi Dibagikan / Dikonsumsi Siswa <span class="text-rose-600">*</span>
                            </label>
                            <input type="number" name="served_portions" id="served_portions" 
                                   value="{{ old('served_portions', $usablePortions) }}" 
                                   min="0" max="{{ $usablePortions }}" required 
                                   class="w-full text-xs font-mono font-bold rounded-lg border border-[#DCE5DD] px-3 py-2 text-[#172923] focus:border-[#1C6654] focus:ring-1 focus:ring-[#1C6654]">
                            <span class="text-[10px] text-[#708177]">Batas maksimum: {{ $usablePortions }} porsi layak</span>
                        </div>

                        <div class="space-y-1.5">
                            <label for="leftover_portions" class="block text-xs font-bold text-[#172923]">
                                Porsi Sisa / Tidak Dibagikan
                            </label>
                            <input type="number" name="leftover_portions" id="leftover_portions" 
                                   value="{{ old('leftover_portions', 0) }}" 
                                   min="0" max="{{ $usablePortions }}" 
                                   class="w-full text-xs font-mono font-bold rounded-lg border border-[#DCE5DD] px-3 py-2 text-[#172923] focus:border-[#1C6654] focus:ring-1 focus:ring-[#1C6654]">
                            <span class="text-[10px] text-[#708177]">Contoh: Siswa absen atau porsi cadangan berlebih</span>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#EDF2ED] text-xs text-[#53645B] leading-relaxed">
                        <strong>Aturan Rekonsiliasi:</strong> Total porsi dibagikan ditambah porsi sisa tidak boleh melampaui kuantitas porsi layak konsumsi ({{ $usablePortions }} porsi).
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label for="complete_notes" class="block text-xs font-bold text-[#172923]">Catatan Penutupan Layanan:</label>
                            <input type="text" name="notes" id="complete_notes" placeholder="Catatan kegiatan makan..." class="w-full text-xs rounded-lg border border-[#DCE5DD] px-3 py-2 text-[#172923]">
                        </div>

                        <div class="space-y-1.5">
                            <label for="complete_photo" class="block text-xs font-bold text-[#172923]">Foto Dokumentasi <span class="text-[#708177] font-normal">(opsional)</span></label>
                            <input type="file" name="photo" id="complete_photo" accept="image/*" class="w-full text-xs file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-[#EDF2ED] file:text-[#1C6654]">
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-5 py-2.5 rounded-lg text-xs font-bold text-white bg-[#1C6654] hover:bg-[#172923] touch-target smooth-transition shadow-xs">
                            Tutup Kegiatan Makan (Selesai)
                        </button>
                    </div>
                </form>
            </div>
        @elseif ($record->status === \App\Enums\SchoolReceiptStatus::MEAL_COMPLETED)
            <!-- STATUS SUDAH SELESAI -->
            <div class="bg-white rounded-2xl border border-emerald-300 p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-[#EDF2ED]">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-emerald-600 text-white text-xs font-bold flex items-center justify-center">✓</span>
                        <h2 class="text-xs font-bold text-[#172923] uppercase tracking-wider">Kegiatan Makan Telah Resmi Ditutup (MEAL_COMPLETED)</h2>
                    </div>
                    <span class="text-xs font-mono text-[#53645B]">
                        {{ $record->meal_completed_at?->translatedFormat('d M Y, H:i') }} WIB
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs bg-[#F5F7F3] p-4 rounded-xl border border-[#EDF2ED]">
                    <div>
                        <span class="text-[#708177] block text-[11px]">Realisasi Terbagi</span>
                        <span class="font-bold text-emerald-800 font-mono text-base">{{ $record->served_portions }} porsi</span>
                    </div>
                    <div>
                        <span class="text-[#708177] block text-[11px]">Porsi Sisa</span>
                        <span class="font-bold text-[#172923] font-mono text-base">{{ $record->leftover_portions }} porsi</span>
                    </div>
                    <div>
                        <span class="text-[#708177] block text-[11px]">Waktu Penutupan</span>
                        <span class="font-bold text-[#172923] font-mono text-xs">{{ $record->meal_completed_at?->translatedFormat('H:i') }} WIB</span>
                    </div>
                </div>

                <!-- Link Lanjut ke Penyiapan Ompreng P06 -->
                <div class="p-4 rounded-xl bg-[#EDF2ED] border border-[#1C6654]/20 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="text-xs text-[#172923]">
                        <strong>Langkah Selanjutnya:</strong> Wadah ompreng dapat dihitung dan ditandai siap dijemput oleh armada logistik kurir.
                    </div>
                    <a href="{{ route('school.trays.index') }}" 
                       class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-xs font-bold text-white bg-[#1C6654] hover:bg-[#172923] touch-target smooth-transition shrink-0 shadow-xs">
                        <span>Lanjut ke Penyiapan Ompreng (P06)</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>
            </div>
        @endif

    </div>
</x-app-shell>
