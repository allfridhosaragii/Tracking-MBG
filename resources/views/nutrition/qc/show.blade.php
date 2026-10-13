<x-app-shell title="Inspeksi Mutu Batch : {{ $batch->batch_code }}">
    <!-- Header Inspeksi QC -->
    <header class="mb-6 pb-4 border-b border-[#DCE5DD]">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                    <x-role-badge :role="auth()->user()->role" />
                    <span class="text-xs font-mono text-[#53645B] bg-white border border-[#DCE5DD] px-2 py-0.5 rounded">
                        SPPG: {{ $plan->sppg->code }}
                    </span>
                    @php
                        $badgeStyle = match($batch->status->value) {
                            'RELEASED' => 'bg-[#EDF7F2] text-[#1B573E] border-[#BCE2D0]',
                            'ON_HOLD' => 'bg-[#FDF2F2] text-[#9B3232] border-[#F5C2C2]',
                            'QC_CHECK' => 'bg-[#FAF4EB] text-[#885D1C] border-[#E8D7BE]',
                            default => 'bg-[#EDF2ED] text-[#53645B] border-[#DCE5DD]'
                        };
                    @endphp
                    <span class="text-xs font-bold px-2 py-0.5 rounded border {{ $badgeStyle }}">
                        Status Batch: {{ $batch->status->label() }}
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">
                    Lembar Verifikasi Mutu &amp; Organoleptik: {{ $batch->batch_code }}
                </h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">
                    Menu: <strong>{{ $menu->name }} (v{{ $menu->version }})</strong> : Layanan: <strong>{{ $plan->service_date->translatedFormat('l, d F Y') }}</strong>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('batches.show', $batch) }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-[#DCE5DD] text-xs font-semibold text-[#172923] hover:bg-[#FAFBF9] transition-colors touch-target">
                    <svg class="w-4 h-4 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <span>Lihat Jejak Batch (C02)</span>
                </a>
                <a href="{{ route('nutrition.qc.index') }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-[#DCE5DD] text-xs font-semibold text-[#172923] hover:bg-[#FAFBF9] transition-colors touch-target">
                    <svg class="w-4 h-4 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Kembali ke Antrean QC</span>
                </a>
            </div>
        </div>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        
        <!-- Kolom Kiri (1 Kolom): Rincian Batch, Foto Bukti, & Riwayat -->
        <div class="space-y-6">
            
            <!-- Ringkasan Batch & Porsi -->
            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 subtle-shadow space-y-4">
                <div class="pb-3 border-b border-[#EDF2ED]">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#285F80]">Identitas Batch Produksi</span>
                    <h2 class="text-base font-mono font-bold text-[#172923] mt-0.5">{{ $batch->batch_code }}</h2>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-[#FAFBF9]">
                        <span class="text-[#53645B]">Kategori Porsi:</span>
                        <span class="font-bold text-[#172923]">{{ $batch->portion_category ?? 'STANDAR' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-[#FAFBF9]">
                        <span class="text-[#53645B]">Target Direncanakan:</span>
                        <span class="font-mono font-bold text-[#1C6654]">{{ number_format($batch->planned_portions) }} porsi</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-[#FAFBF9]">
                        <span class="text-[#53645B]">Realisasi Aktual Masak:</span>
                        <span class="font-mono font-bold text-[#885D1C]">{{ $batch->actual_portions !== null ? number_format($batch->actual_portions) . ' porsi' : 'Belum tercatat' }}</span>
                    </div>
                    @if ($batch->actual_portions !== null)
                        @php
                            $diff = $batch->actual_portions - $batch->planned_portions;
                        @endphp
                        <div class="flex justify-between py-1 border-b border-[#FAFBF9]">
                            <span class="text-[#53645B]">Selisih Produksi:</span>
                            <span class="font-bold {{ $diff === 0 ? 'text-[#1B573E]' : ($diff > 0 ? 'text-[#285F80]' : 'text-[#9B3232]') }}">
                                {{ $diff === 0 ? 'Tepat sesuai target' : ($diff > 0 ? "+{$diff} porsi" : "{$diff} porsi") }}
                            </span>
                        </div>
                    @endif
                    <div class="flex justify-between py-1 border-b border-[#FAFBF9]">
                        <span class="text-[#53645B]">Operator Dapur:</span>
                        <span class="font-semibold text-[#172923]">{{ $batch->creator->name }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-[#53645B]">Waktu Penyerahan:</span>
                        <span class="font-mono text-[#172923]">{{ $batch->updated_at->format('d M Y, H:i') }} WIB</span>
                    </div>
                </div>

                @if ($batch->notes)
                    <div class="p-3 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] text-xs">
                        <span class="text-[10px] uppercase font-bold text-[#53645B] block mb-1">Catatan Kru Dapur:</span>
                        <p class="text-[#172923] italic">{{ $batch->notes }}</p>
                    </div>
                @endif
            </div>

            <!-- Komponen Hidangan & Takaran Menu -->
            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 subtle-shadow">
                <div class="pb-3 mb-3 border-b border-[#EDF2ED]">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#1C6654]">Standar Resep Terdaftar</span>
                    <h2 class="text-sm font-bold text-[#172923] mt-0.5">{{ $menu->name }}</h2>
                </div>

                <div class="space-y-1.5 text-xs">
                    @foreach ($menu->items as $item)
                        <div class="flex items-center justify-between p-2 rounded-lg bg-[#FAFBF9] border border-[#EDF2ED]">
                            <span class="font-medium text-[#172923]">{{ $item->item_name }}</span>
                            <span class="font-mono text-[#53645B]">{{ number_format($item->serving_weight_g) }}g / porsi</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Bukti Foto Makanan Siap Kemas (FOOD_READY) -->
            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 subtle-shadow space-y-3">
                <div class="pb-3 border-b border-[#EDF2ED]">
                    <h2 class="text-sm font-bold text-[#172923]">Bukti Foto FOOD_READY</h2>
                    <p class="text-xs text-[#53645B] mt-0.5">Dokumentasi fisik sebelum serah terima QC</p>
                </div>

                @if ($photos->isNotEmpty())
                    <div class="space-y-3">
                        @foreach ($photos as $photo)
                            <div class="rounded-xl border border-[#DCE5DD] overflow-hidden bg-[#FAFBF9]">
                                <a href="{{ route('evidence-photos.show', $photo) }}" target="_blank" class="block group relative">
                                    <img src="{{ route('evidence-photos.show', $photo) }}" 
                                         alt="Bukti Makanan Batch {{ $batch->batch_code }}" 
                                         class="w-full h-44 object-cover group-hover:opacity-90 transition-opacity">
                                    <span class="absolute bottom-2 right-2 bg-black/60 text-white text-[10px] px-2 py-0.5 rounded backdrop-blur-xs">
                                        Perbesar Bukti
                                    </span>
                                </a>
                                <div class="p-2.5 text-xs text-[#53645B] space-y-0.5">
                                    <p class="font-semibold text-[#172923]">{{ $photo->notes ?? 'Dokumentasi Makanan Siap Kemas' }}</p>
                                    <p class="text-[11px] font-mono">Diupload oleh: {{ $photo->uploader->name }} ({{ number_format($photo->file_size / 1024, 1) }} KB)</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif ($batch->has_evidence_exception)
                    <div class="p-3.5 rounded-xl bg-[#FAF4EB] border border-[#E8D7BE] text-xs text-[#885D1C] space-y-2">
                        <div class="flex items-center gap-1.5 font-bold">
                            <svg class="w-4 h-4 text-[#885D1C]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>Pengecualian Bukti Operasional Resmi</span>
                        </div>
                        <p class="text-[11px] leading-relaxed">
                            Alasan: <strong>"{{ $batch->evidence_exception_reason }}"</strong>
                        </p>
                        <p class="text-[10px] font-mono text-[#53645B]">
                            Disetujui oleh: {{ $batch->exceptionApprover?->name ?? 'Petugas Lapangan' }}
                        </p>
                    </div>
                @else
                    <div class="p-4 rounded-xl bg-[#FDF2F2] border border-[#F5C2C2] text-xs text-[#9B3232] text-center">
                        <p class="font-bold">Bukti Foto Belum Dilampirkan</p>
                        <p class="text-[11px] mt-0.5">Batch tidak memiliki foto FOOD_READY atau alasan pengecualian operasional.</p>
                    </div>
                @endif
            </div>

            <!-- Riwayat Catatan QC Sebelumnya (Append-Only) -->
            @if ($qualityChecks->isNotEmpty())
                <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 subtle-shadow space-y-3">
                    <div class="pb-2 border-b border-[#EDF2ED]">
                        <h2 class="text-sm font-bold text-[#172923]">Riwayat Keputusan QC</h2>
                    </div>

                    <div class="space-y-2 text-xs">
                        @foreach ($qualityChecks as $qc)
                            <div class="p-3 rounded-xl {{ $qc->isSafe() ? 'bg-[#EDF7F2] border border-[#BCE2D0]' : 'bg-[#FDF2F2] border border-[#F5C2C2]' }} space-y-1">
                                <div class="flex items-center justify-between font-bold">
                                    <span class="{{ $qc->isSafe() ? 'text-[#1B573E]' : 'text-[#9B3232]' }}">
                                        {{ $qc->conclusion->label() }}
                                    </span>
                                    <span class="font-mono text-[10px] text-[#53645B]">{{ $qc->checked_at->format('d/m H:i') }} WIB</span>
                                </div>
                                <p class="text-[11px] text-[#53645B]">Pemeriksa: {{ $qc->inspector->name }}</p>
                                @if ($qc->decision_reason)
                                    <p class="text-[11px] text-[#172923] italic">"{{ $qc->decision_reason }}"</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>

        <!-- Kolom Kanan (2 Kolom): Form Lembar Uji Organoleptik & Keputusan Mutu -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 sm:p-6 subtle-shadow space-y-6">
                
                <div class="pb-4 border-b border-[#EDF2ED]">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[#285F80]">Standar Badan Gizi Nasional</span>
                            <h2 class="text-base sm:text-lg font-bold text-[#172923] mt-0.5">
                                Lembar Evaluasi Organoleptik &amp; Uji Keamanan Pangan
                            </h2>
                        </div>
                        <span class="text-xs font-mono font-bold bg-[#F0F5FA] text-[#285F80] border border-[#BCD4E6] px-2.5 py-1 rounded-lg">
                            Titik Uji: Dapur SPPG
                        </span>
                    </div>
                    <p class="text-xs text-[#53645B] mt-1 leading-relaxed">
                        Evaluasi seluruh parameter organoleptik wajib di bawah ini secara objektif. Makanan yang berstatus <strong>DITAHAN (On Hold)</strong> dilarang keras untuk dikirim ke sekolah penerima.
                    </p>
                </div>

                @if ($batch->isReleased())
                    <!-- Notice jika batch sudah dirilis / terminal -->
                    <div class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-xs text-[#1B573E] flex items-start gap-3">
                        <svg class="w-5 h-5 shrink-0 text-[#12B76A]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="font-bold">Batch Telah Diotorisasi &amp; Dirilis</p>
                            <p class="text-[11px] mt-0.5">
                                Batch ini telah dinyatakan <strong>LOLOS QC (SAFE)</strong> oleh {{ $batch->releaser?->name }} pada {{ $batch->released_at?->format('d M Y, H:i') }} WIB dengan {{ number_format($batch->accepted_portions ?? $batch->actual_portions) }} porsi lolos. Keputusan terminal tidak dapat diubah kembali.
                            </p>
                        </div>
                    </div>
                @else
                    <!-- Form Uji Mutu -->
                    <form method="POST" action="{{ route('nutrition.qc.inspect', $batch) }}" class="space-y-6">
                        @csrf

                        <!-- 1. Checklist Parameter Organoleptik Sensorik -->
                        <div class="space-y-4">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-[#172923] pb-2 border-b border-[#EDF2ED]">
                                1. Pengujian Parameter Organoleptik (Sensorik)
                            </h3>

                            <!-- Parameter A: Penampilan / Warna -->
                            <div class="p-3.5 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <span class="text-xs font-bold text-[#172923] block">A. Penampilan &amp; Warna Makanan</span>
                                    <p class="text-[11px] text-[#53645B] mt-0.5">Warna alami bahan masakan, tidak pucat, tidak gosong, dan tidak ada benda asing.</p>
                                </div>
                                <div class="flex items-center gap-4 shrink-0">
                                    <label class="inline-flex items-center gap-1.5 text-xs font-medium text-[#172923] cursor-pointer">
                                        <input type="radio" name="appearance_ok" value="1" {{ old('appearance_ok', '1') == '1' ? 'checked' : '' }} class="text-[#1C6654] focus:ring-[#1C6654]">
                                        <span>Sesuai (Baik)</span>
                                    </label>
                                    <label class="inline-flex items-center gap-1.5 text-xs font-medium text-[#9B3232] cursor-pointer">
                                        <input type="radio" name="appearance_ok" value="0" {{ old('appearance_ok') == '0' ? 'checked' : '' }} class="text-[#9B3232] focus:ring-[#9B3232]">
                                        <span>Menyimpang</span>
                                    </label>
                                </div>
                            </div>
                            @error('appearance_ok')
                                <p class="text-[11px] text-[#9B3232]">{{ $message }}</p>
                            @enderror

                            <!-- Parameter B: Aroma -->
                            <div class="p-3.5 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <span class="text-xs font-bold text-[#172923] block">B. Aroma Hidangan</span>
                                    <p class="text-[11px] text-[#53645B] mt-0.5">Aroma segar khas bumbu hidangan, tidak berbau asam/basi, tidak tengik, dan tidak menyengat.</p>
                                </div>
                                <div class="flex items-center gap-4 shrink-0">
                                    <label class="inline-flex items-center gap-1.5 text-xs font-medium text-[#172923] cursor-pointer">
                                        <input type="radio" name="aroma_ok" value="1" {{ old('aroma_ok', '1') == '1' ? 'checked' : '' }} class="text-[#1C6654] focus:ring-[#1C6654]">
                                        <span>Sesuai (Segar)</span>
                                    </label>
                                    <label class="inline-flex items-center gap-1.5 text-xs font-medium text-[#9B3232] cursor-pointer">
                                        <input type="radio" name="aroma_ok" value="0" {{ old('aroma_ok') == '0' ? 'checked' : '' }} class="text-[#9B3232] focus:ring-[#9B3232]">
                                        <span>Menyimpang (Asam/Basi)</span>
                                    </label>
                                </div>
                            </div>
                            @error('aroma_ok')
                                <p class="text-[11px] text-[#9B3232]">{{ $message }}</p>
                            @enderror

                            <!-- Parameter C: Tekstur -->
                            <div class="p-3.5 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <span class="text-xs font-bold text-[#172923] block">C. Tekstur &amp; Kematangan</span>
                                    <p class="text-[11px] text-[#53645B] mt-0.5">Kematangan bahan merata, tekstur nasi pulen, sayur tidak layu berlendir, dan lauk matang sempurna.</p>
                                </div>
                                <div class="flex items-center gap-4 shrink-0">
                                    <label class="inline-flex items-center gap-1.5 text-xs font-medium text-[#172923] cursor-pointer">
                                        <input type="radio" name="texture_ok" value="1" {{ old('texture_ok', '1') == '1' ? 'checked' : '' }} class="text-[#1C6654] focus:ring-[#1C6654]">
                                        <span>Sesuai (Matang Sempurna)</span>
                                    </label>
                                    <label class="inline-flex items-center gap-1.5 text-xs font-medium text-[#9B3232] cursor-pointer">
                                        <input type="radio" name="texture_ok" value="0" {{ old('texture_ok') == '0' ? 'checked' : '' }} class="text-[#9B3232] focus:ring-[#9B3232]">
                                        <span>Menyimpang (Mentah/Lendir)</span>
                                    </label>
                                </div>
                            </div>
                            @error('texture_ok')
                                <p class="text-[11px] text-[#9B3232]">{{ $message }}</p>
                            @enderror

                            <!-- Parameter D: Uji Rasa (Dengan Perlindungan Keselamatan Petugas) -->
                            <div class="p-3.5 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] space-y-3">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <div>
                                        <span class="text-xs font-bold text-[#172923] block">D. Uji Cicip Rasa Makanan</span>
                                        <p class="text-[11px] text-[#53645B] mt-0.5">Petugas tidak boleh dipaksa mencicipi jika makanan sudah dicurigai berbau busuk, berlendir, atau terkontaminasi.</p>
                                    </div>
                                    <div class="flex items-center gap-4 shrink-0">
                                        <label class="inline-flex items-center gap-1.5 text-xs font-medium text-[#172923] cursor-pointer">
                                            <input type="radio" name="taste_tested" value="1" {{ old('taste_tested', '1') == '1' ? 'checked' : '' }} class="text-[#1C6654] focus:ring-[#1C6654]">
                                            <span>Dilakukan Cicip</span>
                                        </label>
                                        <label class="inline-flex items-center gap-1.5 text-xs font-medium text-[#885D1C] cursor-pointer">
                                            <input type="radio" name="taste_tested" value="0" {{ old('taste_tested') == '0' ? 'checked' : '' }} class="text-[#885D1C] focus:ring-[#885D1C]">
                                            <span>Dilewati (Risiko Kontaminasi)</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-[#EDF2ED]">
                                    <!-- Jika dicicipi -->
                                    <div>
                                        <span class="text-[11px] font-semibold text-[#172923] block mb-1">Hasil Uji Rasa (Bila Dicicipi):</span>
                                        <div class="flex items-center gap-3">
                                            <label class="inline-flex items-center gap-1.5 text-xs text-[#172923] cursor-pointer">
                                                <input type="radio" name="taste_ok" value="1" {{ old('taste_ok', '1') == '1' ? 'checked' : '' }} class="text-[#1C6654] focus:ring-[#1C6654]">
                                                <span>Sesuai Resep</span>
                                            </label>
                                            <label class="inline-flex items-center gap-1.5 text-xs text-[#9B3232] cursor-pointer">
                                                <input type="radio" name="taste_ok" value="0" {{ old('taste_ok') == '0' ? 'checked' : '' }} class="text-[#9B3232] focus:ring-[#9B3232]">
                                                <span>Menyimpang (Tidak Layak)</span>
                                            </label>
                                        </div>
                                        @error('taste_ok')
                                            <p class="text-[11px] text-[#9B3232] mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Jika dilewati -->
                                    <div>
                                        <label for="taste_skipped_reason" class="text-[11px] font-semibold text-[#172923] block mb-1">Alasan Tidak Mencicipi (Jika Dilewati):</label>
                                        <input type="text" 
                                               id="taste_skipped_reason" 
                                               name="taste_skipped_reason" 
                                               value="{{ old('taste_skipped_reason') }}" 
                                               placeholder="Contoh: Aroma tercium asam menyengat, membahayakan pemeriksa"
                                               class="w-full px-2.5 py-1.5 text-xs rounded-xl bg-white border border-[#DCE5DD] text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#885D1C]">
                                        @error('taste_skipped_reason')
                                            <p class="text-[11px] text-[#9B3232] mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Parameter Fisik: Suhu & Rekonsiliasi Porsi -->
                        <div class="space-y-4">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-[#172923] pb-2 border-b border-[#EDF2ED]">
                                2. Suhu Masak, SOP &amp; Rekonsiliasi Porsi Lolos
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <!-- Suhu Inti (°C) -->
                                <div class="p-3.5 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] space-y-1">
                                    <label for="temperature_c" class="text-xs font-bold text-[#172923] block">Suhu Inti Makanan (°C)</label>
                                    <div class="relative rounded-xl">
                                        <input type="number" 
                                               id="temperature_c" 
                                               name="temperature_c" 
                                               value="{{ old('temperature_c', '65.0') }}" 
                                               step="0.1" 
                                               min="0" 
                                               max="150" 
                                               class="w-full px-3 py-2 text-xs rounded-xl bg-white border border-[#DCE5DD] text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#285F80]">
                                        <span class="absolute right-3 top-2 text-xs font-bold text-[#53645B]">°C</span>
                                    </div>
                                    <p class="text-[10px] text-[#53645B]">Hot-holding min. 60°C (Permenkes 1096/2011)</p>
                                    @error('temperature_c')
                                        <p class="text-[11px] text-[#9B3232]">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Porsi Lolos QC (Reconciliation) -->
                                <div class="p-3.5 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] space-y-1">
                                    <label for="accepted_portions" class="text-xs font-bold text-[#172923] block">Porsi Disetujui Lolos (Accepted)</label>
                                    <input type="number" 
                                           id="accepted_portions" 
                                           name="accepted_portions" 
                                           value="{{ old('accepted_portions', $batch->actual_portions ?? $batch->planned_portions) }}" 
                                           min="0" 
                                           max="{{ $batch->actual_portions ?? $batch->planned_portions }}" 
                                           class="w-full px-3 py-2 text-xs rounded-xl bg-white border border-[#DCE5DD] text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                    <p class="text-[10px] text-[#53645B]">Maks. {{ $batch->actual_portions ?? $batch->planned_portions }} porsi aktual</p>
                                    @error('accepted_portions')
                                        <p class="text-[11px] text-[#9B3232]">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Referensi SOP -->
                                <div class="p-3.5 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] space-y-1">
                                    <label for="sop_reference" class="text-xs font-bold text-[#172923] block">Referensi SOP Pengawasan</label>
                                    <input type="text" 
                                           id="sop_reference" 
                                           name="sop_reference" 
                                           value="{{ old('sop_reference', 'SOP-BGN-MUTU-01') }}" 
                                           class="w-full px-3 py-2 text-xs rounded-xl bg-white border border-[#DCE5DD] text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#285F80]">
                                    <p class="text-[10px] text-[#53645B]">Standar Higiene &amp; Mutu BGN</p>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Kesimpulan & Otorisasi Keamanan Pangan -->
                        <div class="space-y-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-[#172923] pb-2 border-b border-[#EDF2ED]">
                                3. Keputusan Mutu &amp; Tindakan Operasional <span class="text-[#9B3232]">*</span>
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Pilihan SAFE (Lolos QC) -->
                                <label class="p-4 rounded-xl border border-[#BCE2D0] bg-[#EDF7F2] cursor-pointer flex items-start gap-3 hover:bg-[#E3F2EB] transition-colors">
                                    <input type="radio" 
                                           name="conclusion" 
                                           value="SAFE" 
                                           {{ old('conclusion', 'SAFE') === 'SAFE' ? 'checked' : '' }} 
                                           class="mt-1 text-[#1C6654] focus:ring-[#1C6654]">
                                    <div>
                                        <span class="text-xs font-bold text-[#1B573E] block">LOLOS QC (SAFE)</span>
                                        <p class="text-[11px] text-[#1B573E] mt-0.5 leading-relaxed">
                                            Batch disetujui sesuai prosedur operasional yang berlaku dan memenuhi seluruh kriteria kelayakan konsumsi.
                                        </p>
                                    </div>
                                </label>

                                <!-- Pilihan ON_HOLD (Ditahan) -->
                                <label class="p-4 rounded-xl border border-[#F5C2C2] bg-[#FDF2F2] cursor-pointer flex items-start gap-3 hover:bg-[#FBE8E8] transition-colors">
                                    <input type="radio" 
                                           name="conclusion" 
                                           value="HOLD" 
                                           {{ old('conclusion') === 'HOLD' ? 'checked' : '' }} 
                                           class="mt-1 text-[#9B3232] focus:ring-[#9B3232]">
                                    <div>
                                        <span class="text-xs font-bold text-[#9B3232] block">DITAHAN (ON HOLD)</span>
                                        <p class="text-[11px] text-[#9B3232] mt-0.5 leading-relaxed">
                                            Ditemukan penyimpangan organoleptik atau indikasi risiko keamanan pangan. Batch diblokir dari distribusi.
                                        </p>
                                    </div>
                                </label>
                            </div>
                            @error('conclusion')
                                <p class="text-[11px] text-[#9B3232]">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 4. Pertimbangan Keputusan Pengawas Gizi (Wajib Audit) -->
                        <div class="space-y-1">
                            <label for="decision_reason" class="block text-xs font-semibold text-[#172923]">
                                Pertimbangan &amp; Rationale Keputusan Pengawas Gizi <span class="text-[#9B3232]">*</span>
                            </label>
                            <textarea id="decision_reason" 
                                      name="decision_reason" 
                                      rows="3" 
                                      placeholder="Jelaskan dasar pertimbangan profesional Anda atas sampel batch ini (minimal 10 karakter)..." 
                                      class="w-full px-3 py-2 text-xs rounded-xl bg-white border border-[#DCE5DD] text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#285F80]">{{ old('decision_reason') }}</textarea>
                            @error('decision_reason')
                                <p class="text-[11px] text-[#9B3232] mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Catatan Tambahan -->
                        <div>
                            <label for="notes" class="block text-xs font-semibold text-[#172923] mb-1">
                                Catatan Pemeriksaan Tambahan / Tindakan Korektif (Opsional)
                            </label>
                            <input type="text" 
                                   id="notes" 
                                   name="notes" 
                                   value="{{ old('notes') }}"
                                   placeholder="Instruksi tambahan bagi kru dapur atau sekolah penerima..." 
                                   class="w-full px-3 py-2 text-xs rounded-xl bg-white border border-[#DCE5DD] text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#285F80]">
                            @error('notes')
                                <p class="text-[11px] text-[#9B3232] mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Tombol Submit Otorisasi -->
                        <div class="pt-4 border-t border-[#EDF2ED]">
                            <button type="submit" 
                                    class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-[#285F80] hover:bg-[#1E4861] text-white text-xs sm:text-sm font-bold rounded-xl shadow-xs transition-colors touch-target">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <span>Simpan Keputusan QC &amp; Otorisasi Batch</span>
                            </button>
                        </div>

                    </form>
                @endif

            </div>
        </div>

    </div>
</x-app-shell>
