<x-app-shell title="Inspeksi Mutu Batch {{ $batch->batch_code }}">
    <div class="space-y-5">
        @php
            $badgeStyle = match($batch->status->value) {
                'RELEASED' => 'bg-[#EDF7F2] text-[#21684B] border-[#BCE2D0]',
                'ON_HOLD' => 'bg-[#FDF2F2] text-[#9B3232] border-[#F5C2C2]',
                'QC_CHECK' => 'bg-[#FEF7EC] text-[#885D1C] border-[#F7D7A3]',
                default => 'bg-[#FAFBF9] text-[#53645B] border-[#DCE5DD]'
            };
        @endphp

        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <a href="{{ route('nutrition.qc.index') }}" class="text-xs font-semibold text-[#285F80] hover:underline inline-flex items-center gap-1 mb-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Antrean Uji Mutu</span>
                </a>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="page-title">Inspeksi Batch {{ $batch->batch_code }}</h1>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-lg border {{ $badgeStyle }}">{{ $batch->status->label() }}</span>
                </div>
                <p class="text-xs text-[#53645B] mt-1">{{ $menu->name }} (v{{ $menu->version }}) • {{ $plan->service_date->translatedFormat('l, d F Y') }}</p>
            </div>

            <a href="{{ route('batches.show', $batch) }}"
               class="min-h-[44px] px-3.5 py-2 rounded-xl bg-white border border-[#DCE5DD] text-xs font-semibold text-[#172923] hover:bg-[#FAFBF9] inline-flex items-center shrink-0 smooth-transition">
                Lihat Jejak Batch
            </a>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
            <!-- Left: Batch Context & Evidence (4 cols) -->
            <div class="lg:col-span-4 space-y-5">
                <x-card title="Ringkasan Produksi Batch" compact>
                    <div class="space-y-2.5 text-xs">
                        <div class="flex justify-between py-1 border-b border-[#E7ECE8]">
                            <span class="text-[#53645B]">Kategori</span>
                            <span class="font-semibold text-[#172923]">{{ $batch->portion_category ?? 'STANDAR' }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#E7ECE8]">
                            <span class="text-[#53645B]">Target Rencana</span>
                            <span class="font-mono font-bold text-[#1C6654] tabular-nums">{{ number_format($batch->planned_portions) }} porsi</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#E7ECE8]">
                            <span class="text-[#53645B]">Realisasi Masak</span>
                            <span class="font-mono font-bold text-[#172923] tabular-nums">{{ $batch->actual_portions !== null ? number_format($batch->actual_portions) . ' porsi' : 'Belum tercatat' }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-[#53645B]">Operator Dapur</span>
                            <span class="font-medium text-[#172923]">{{ $batch->creator->name }}</span>
                        </div>

                        @if ($batch->notes)
                            <div class="inset-surface p-3 mt-2">
                                <span class="eyebrow-label block mb-1">Catatan Dapur</span>
                                <p class="text-[#172923]">{{ $batch->notes }}</p>
                            </div>
                        @endif
                    </div>
                </x-card>

                <!-- Food Ready Photo -->
                <x-card title="Bukti Foto Makanan Siap" compact>
                    @if ($photos->isNotEmpty())
                        <div class="space-y-3">
                            @foreach ($photos as $photo)
                                <div class="rounded-xl border border-[#DCE5DD] overflow-hidden bg-[#FAFBF9]">
                                    <a href="{{ route('evidence-photos.show', $photo) }}" target="_blank" rel="noopener" class="block">
                                        <img src="{{ route('evidence-photos.show', $photo) }}"
                                             alt="Bukti Makanan Batch {{ $batch->batch_code }}"
                                             class="w-full h-44 object-cover">
                                    </a>
                                    <div class="p-2.5 text-xs text-[#53645B]">
                                        <p class="font-semibold text-[#172923]">{{ $photo->notes ?? 'Dokumentasi Siap Kemas' }}</p>
                                        <p class="text-[11px] font-mono mt-0.5">{{ $photo->uploader->name }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif ($batch->has_evidence_exception)
                        <div class="p-3.5 rounded-xl bg-[#FEF7EC] border border-[#F7D7A3] text-xs text-[#885D1C] space-y-1">
                            <p class="font-bold">Pengecualian Bukti Resmi</p>
                            <p>{{ $batch->evidence_exception_reason }}</p>
                        </div>
                    @else
                        <div class="p-4 rounded-xl bg-[#FDF2F2] border border-[#F5C2C2] text-xs text-[#9B3232] text-center">
                            Bukti foto belum dilampirkan.
                        </div>
                    @endif
                </x-card>

                <!-- Komposisi Resep -->
                <details class="sop-disclosure">
                    <summary>
                        <span>Rincian Gramasi Resep ({{ $menu->items->count() }} Bahan)</span>
                        <svg class="w-4 h-4 text-[#53645B] disclosure-chevron shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <div class="p-4 space-y-1.5 text-xs">
                        @foreach ($menu->items as $item)
                            <div class="flex items-center justify-between py-1 border-b border-[#E7ECE8] last:border-b-0">
                                <span class="text-[#172923]">{{ $item->item_name }}</span>
                                <span class="font-mono text-[#53645B] tabular-nums">{{ number_format($item->serving_weight_g) }}g</span>
                            </div>
                        @endforeach
                    </div>
                </details>

                @if ($qualityChecks->isNotEmpty())
                    <x-card title="Riwayat Keputusan QC" compact>
                        <div class="space-y-2 text-xs">
                            @foreach ($qualityChecks as $qc)
                                <div class="p-3 rounded-xl {{ $qc->isSafe() ? 'bg-[#EDF7F2] border border-[#BCE2D0]' : 'bg-[#FDF2F2] border border-[#F5C2C2]' }} space-y-1">
                                    <div class="flex items-center justify-between font-bold">
                                        <span class="{{ $qc->isSafe() ? 'text-[#21684B]' : 'text-[#9B3232]' }}">{{ $qc->conclusion->label() }}</span>
                                        <span class="font-mono text-[10px] text-[#53645B] tabular-nums">{{ $qc->checked_at->format('d/m H:i') }} WIB</span>
                                    </div>
                                    <p class="text-[11px] text-[#53645B]">{{ $qc->inspector->name }}</p>
                                    @if ($qc->decision_reason)
                                        <p class="text-[11px] text-[#172923]">{{ $qc->decision_reason }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </x-card>
                @endif
            </div>

            <!-- Right: Evaluation Form (8 cols) -->
            <div class="lg:col-span-8">
                <x-card title="Lembar Evaluasi Organoleptik &amp; Otorisasi Mutu" subtitle="Periksa seluruh parameter sensorik dan suhu sebelum menetapkan status pelepasan batch.">
                    @if ($batch->isReleased())
                        <div class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-xs text-[#1B573E] space-y-1">
                            <p class="font-bold text-sm">Batch Telah Diotorisasi &amp; Dirilis</p>
                            <p>Disahkan oleh <strong>{{ $batch->releaser?->name }}</strong> pada {{ $batch->released_at?->format('d M Y, H:i') }} WIB sejumlah <strong>{{ number_format($batch->accepted_portions ?? $batch->actual_portions) }} porsi</strong>.</p>
                        </div>
                    @else
                        <form method="POST" action="{{ route('nutrition.qc.inspect', $batch) }}" class="space-y-6">
                            @csrf

                            <!-- 1. Sensorik -->
                            <div class="space-y-3">
                                <h3 class="eyebrow-label text-[#172923]">1. Parameter Sensorik</h3>

                                @php
                                    $sensoryFields = [
                                        ['name' => 'appearance_ok', 'title' => 'Penampilan & Warna', 'desc' => 'Warna alami masakan, tidak pucat atau gosong, bebas benda asing.', 'ok' => 'Sesuai', 'bad' => 'Menyimpang'],
                                        ['name' => 'aroma_ok', 'title' => 'Aroma Hidangan', 'desc' => 'Segar khas bumbu, tidak asam, tidak tengik.', 'ok' => 'Segar', 'bad' => 'Menyimpang'],
                                        ['name' => 'texture_ok', 'title' => 'Tekstur & Kematangan', 'desc' => 'Matang merata, tidak berlendir.', 'ok' => 'Matang', 'bad' => 'Menyimpang'],
                                    ];
                                @endphp

                                @foreach ($sensoryFields as $sf)
                                    <div class="inset-surface p-3.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                        <div>
                                            <span class="text-xs font-bold text-[#172923] block">{{ $sf['title'] }}</span>
                                            <p class="text-[11px] text-[#53645B] mt-0.5">{{ $sf['desc'] }}</p>
                                        </div>
                                        <div class="grid grid-cols-2 sm:flex items-center gap-2 shrink-0">
                                            <label class="min-h-[42px] px-3 py-2 rounded-xl bg-white border border-[#DCE5DD] inline-flex items-center gap-2 text-xs font-semibold text-[#172923] cursor-pointer has-checked:border-[#1C6654] has-checked:bg-[#EDF7F2]">
                                                <input type="radio" name="{{ $sf['name'] }}" value="1" {{ old($sf['name'], '1') == '1' ? 'checked' : '' }} class="text-[#1C6654]">
                                                <span>{{ $sf['ok'] }}</span>
                                            </label>
                                            <label class="min-h-[42px] px-3 py-2 rounded-xl bg-white border border-[#DCE5DD] inline-flex items-center gap-2 text-xs font-semibold text-[#9B3232] cursor-pointer has-checked:border-[#9B3232] has-checked:bg-[#FDF2F2]">
                                                <input type="radio" name="{{ $sf['name'] }}" value="0" {{ old($sf['name']) == '0' ? 'checked' : '' }} class="text-[#9B3232]">
                                                <span>{{ $sf['bad'] }}</span>
                                            </label>
                                        </div>
                                    </div>
                                    @error($sf['name'])
                                        <p class="text-[11px] text-[#9B3232]">{{ $message }}</p>
                                    @enderror
                                @endforeach

                                <!-- Taste Check -->
                                <div class="inset-surface p-3.5 space-y-3">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                        <div>
                                            <span class="text-xs font-bold text-[#172923] block">Uji Cicip Rasa</span>
                                            <p class="text-[11px] text-[#53645B] mt-0.5">Lewati pencicipan bila sampel sudah dicurigai basi atau terkontaminasi.</p>
                                        </div>
                                        <div class="grid grid-cols-2 sm:flex items-center gap-2 shrink-0">
                                            <label class="min-h-[42px] px-3 py-2 rounded-xl bg-white border border-[#DCE5DD] inline-flex items-center gap-2 text-xs font-semibold text-[#172923] cursor-pointer has-checked:border-[#1C6654] has-checked:bg-[#EDF7F2]">
                                                <input type="radio" name="taste_tested" value="1" {{ old('taste_tested', '1') == '1' ? 'checked' : '' }} class="text-[#1C6654]">
                                                <span>Dicicipi</span>
                                            </label>
                                            <label class="min-h-[42px] px-3 py-2 rounded-xl bg-white border border-[#DCE5DD] inline-flex items-center gap-2 text-xs font-semibold text-[#885D1C] cursor-pointer has-checked:border-[#885D1C] has-checked:bg-[#FEF7EC]">
                                                <input type="radio" name="taste_tested" value="0" {{ old('taste_tested') == '0' ? 'checked' : '' }} class="text-[#885D1C]">
                                                <span>Dilewati</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3 border-t border-[#E7ECE8]">
                                        <div>
                                            <span class="text-[11px] font-semibold text-[#172923] block mb-1.5">Hasil Rasa (Jika Dicicipi)</span>
                                            <div class="flex items-center gap-3">
                                                <label class="inline-flex items-center gap-1.5 text-xs text-[#172923] cursor-pointer min-h-[36px]">
                                                    <input type="radio" name="taste_ok" value="1" {{ old('taste_ok', '1') == '1' ? 'checked' : '' }} class="text-[#1C6654]">
                                                    <span>Sesuai Resep</span>
                                                </label>
                                                <label class="inline-flex items-center gap-1.5 text-xs text-[#9B3232] cursor-pointer min-h-[36px]">
                                                    <input type="radio" name="taste_ok" value="0" {{ old('taste_ok') == '0' ? 'checked' : '' }} class="text-[#9B3232]">
                                                    <span>Tidak Layak</span>
                                                </label>
                                            </div>
                                        </div>

                                        <div>
                                            <label for="taste_skipped_reason" class="text-[11px] font-semibold text-[#172923] block mb-1">Alasan Dilewati (Jika Tidak Dicicipi)</label>
                                            <input type="text"
                                                   id="taste_skipped_reason"
                                                   name="taste_skipped_reason"
                                                   value="{{ old('taste_skipped_reason') }}"
                                                   placeholder="Contoh: Aroma asam menyengat"
                                                   class="w-full min-h-[40px] px-3 py-1.5 text-xs rounded-xl bg-white border border-[#DCE5DD] text-[#172923]">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Fisik & Rekonsiliasi -->
                            <div class="space-y-3">
                                <h3 class="eyebrow-label text-[#172923]">2. Suhu Inti &amp; Rekonsiliasi Porsi</h3>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label for="temperature_c" class="block text-xs font-semibold text-[#172923] mb-1">Suhu Inti (°C)</label>
                                        <input type="number"
                                               id="temperature_c"
                                               name="temperature_c"
                                               value="{{ old('temperature_c', '65.0') }}"
                                               step="0.1"
                                               min="0"
                                               max="150"
                                               class="w-full min-h-[44px] px-3 py-2 text-xs rounded-xl bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] text-[#172923] font-mono tabular-nums">
                                        <p class="text-[10px] text-[#53645B] mt-1">Acuan aman &ge; 60°C</p>
                                    </div>

                                    <div>
                                        <label for="accepted_portions" class="block text-xs font-semibold text-[#172923] mb-1">Porsi Disetujui Lolos</label>
                                        <input type="number"
                                               id="accepted_portions"
                                               name="accepted_portions"
                                               value="{{ old('accepted_portions', $batch->actual_portions ?? $batch->planned_portions) }}"
                                               min="0"
                                               max="{{ $batch->actual_portions ?? $batch->planned_portions }}"
                                               class="w-full min-h-[44px] px-3 py-2 text-xs rounded-xl bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] text-[#172923] font-mono tabular-nums">
                                    </div>

                                    <div>
                                        <label for="sop_reference" class="block text-xs font-semibold text-[#172923] mb-1">Referensi SOP</label>
                                        <input type="text"
                                               id="sop_reference"
                                               name="sop_reference"
                                               value="{{ old('sop_reference', 'SOP-BGN-MUTU-01') }}"
                                               class="w-full min-h-[44px] px-3 py-2 text-xs rounded-xl bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] text-[#172923] font-mono">
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Keputusan -->
                            <div class="space-y-3">
                                <h3 class="eyebrow-label text-[#172923]">3. Keputusan Keamanan Pangan <span class="text-[#9B3232]">*</span></h3>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <label class="p-4 rounded-xl border border-[#BCE2D0] bg-[#EDF7F2] cursor-pointer flex items-start gap-3 has-checked:ring-2 has-checked:ring-[#1C6654]">
                                        <input type="radio" name="conclusion" value="SAFE" {{ old('conclusion', 'SAFE') === 'SAFE' ? 'checked' : '' }} class="mt-0.5 text-[#1C6654]">
                                        <div>
                                            <span class="text-xs font-bold text-[#1B573E] block">LOLOS QC (SAFE)</span>
                                            <p class="text-[11px] text-[#1B573E] mt-0.5 leading-relaxed">Memenuhi seluruh parameter kelayakan dan siap dimuat ke kurir.</p>
                                        </div>
                                    </label>

                                    <label class="p-4 rounded-xl border border-[#F5C2C2] bg-[#FDF2F2] cursor-pointer flex items-start gap-3 has-checked:ring-2 has-checked:ring-[#9B3232]">
                                        <input type="radio" name="conclusion" value="HOLD" {{ old('conclusion') === 'HOLD' ? 'checked' : '' }} class="mt-0.5 text-[#9B3232]">
                                        <div>
                                            <span class="text-xs font-bold text-[#9B3232] block">DITAHAN (ON HOLD)</span>
                                            <p class="text-[11px] text-[#9B3232] mt-0.5 leading-relaxed">Terdapat penyimpangan mutu; batch diblokir dari pengiriman.</p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- 4. Rationale -->
                            <div class="space-y-3">
                                <div>
                                    <label for="decision_reason" class="block text-xs font-semibold text-[#172923] mb-1">
                                        Pertimbangan Keputusan Pengawas Gizi <span class="text-[#9B3232]">*</span>
                                    </label>
                                    <textarea id="decision_reason"
                                              name="decision_reason"
                                              rows="2"
                                              placeholder="Jelaskan dasar pertimbangan profesional atas sampel batch ini (min. 10 karakter)..."
                                              class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] text-[#172923]">{{ old('decision_reason') }}</textarea>
                                    @error('decision_reason')
                                        <p class="text-[11px] text-[#9B3232] mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="notes" class="block text-xs font-semibold text-[#172923] mb-1">Catatan Tambahan (Opsional)</label>
                                    <input type="text"
                                           id="notes"
                                           name="notes"
                                           value="{{ old('notes') }}"
                                           placeholder="Instruksi tambahan bagi kru dapur atau kurir..."
                                           class="w-full min-h-[44px] px-3.5 py-2 text-xs rounded-xl bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] text-[#172923]">
                                </div>
                            </div>

                            <div class="pt-3 border-t border-[#E7ECE8]">
                                <button type="submit"
                                        class="w-full min-h-[48px] px-6 py-3 bg-[#285F80] hover:bg-[#1E4861] text-white text-xs sm:text-sm font-bold rounded-xl cursor-pointer smooth-transition">
                                    Simpan Keputusan QC &amp; Otorisasi Batch
                                </button>
                            </div>
                        </form>
                    @endif
                </x-card>
            </div>
        </div>
    </div>
</x-app-shell>
