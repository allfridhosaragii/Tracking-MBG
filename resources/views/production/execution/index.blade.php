<x-app-shell title="Workspace Eksekusi Dapur : Asisten Lapangan">
    <!-- Header Eksekusi Dapur -->
    <header class="mb-6 pb-4 border-b border-[#DCE5DD]">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                    <x-role-badge :role="auth()->user()->role" />
                    <span class="text-xs font-mono text-[#53645B] bg-white border border-[#DCE5DD] px-2 py-0.5 rounded">
                        Dapur: {{ $plan->sppg->code }}
                    </span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded border {{ $plan->status->value === 'READY_FOR_DISTRIBUTION' ? 'bg-[#EDF7F2] text-[#1B573E] border-[#BCE2D0]' : ($plan->status->value === 'IN_PRODUCTION' ? 'bg-[#FAF4EB] text-[#885D1C] border-[#E8D7BE]' : 'bg-[#F0F5FA] text-[#285F80] border-[#BCD4E6]') }}">
                        Status Rencana: {{ $plan->status->label() }}
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">
                    Eksekusi Produksi &amp; Pelacakan Batch Dapur
                </h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">
                    Unit Pelayanan: <strong>{{ $plan->sppg->name }}</strong> : Tanggal Layanan: <strong>{{ $plan->service_date->translatedFormat('l, d F Y') }}</strong>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('production.dashboard', ['date' => $plan->service_date->toDateString()]) }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-[#DCE5DD] text-xs font-semibold text-[#172923] hover:bg-[#FAFBF9] transition-colors touch-target">
                    <svg class="w-4 h-4 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Kembali ke Runbook</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Invariant 4 Angka: Target Alokasi vs Batch Direncanakan vs Realisasi Aktual vs Lolos QC -->
    <section aria-labelledby="metrics-heading" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <h2 id="metrics-heading" class="sr-only">Metrik Invarian Produksi</h2>

        <!-- 1. Target Rencana Sah -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-4 subtle-shadow">
            <div class="flex items-center justify-between text-[#53645B] mb-1">
                <span class="text-[10px] font-bold uppercase tracking-wider">1. Target Sah Rencana</span>
                <span class="w-2 h-2 rounded-full bg-[#1C6654]" aria-hidden="true"></span>
            </div>
            <div class="flex items-baseline gap-1 mt-1">
                <span class="text-2xl font-bold text-[#1C6654] tabular-nums">{{ number_format($totalPlanned) }}</span>
                <span class="text-xs text-[#53645B]">porsi</span>
            </div>
            <p class="text-[11px] text-[#53645B] mt-1">Disahkan Kepala SPPG</p>
        </div>

        <!-- 2. Target Batch Dibuat -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-4 subtle-shadow">
            <div class="flex items-center justify-between text-[#53645B] mb-1">
                <span class="text-[10px] font-bold uppercase tracking-wider">2. Akumulasi Batch</span>
                <span class="w-2 h-2 rounded-full bg-[#285F80]" aria-hidden="true"></span>
            </div>
            <div class="flex items-baseline gap-1 mt-1">
                <span class="text-2xl font-bold text-[#285F80] tabular-nums">{{ number_format($batches->sum('planned_portions')) }}</span>
                <span class="text-xs text-[#53645B]">porsi</span>
            </div>
            <p class="text-[11px] text-[#53645B] mt-1">{{ $batches->count() }} batch pekerjaan</p>
        </div>

        <!-- 3. Realisasi Masak Aktual -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-4 subtle-shadow">
            <div class="flex items-center justify-between text-[#53645B] mb-1">
                <span class="text-[10px] font-bold uppercase tracking-wider">3. Realisasi Aktual</span>
                <span class="w-2 h-2 rounded-full bg-[#885D1C]" aria-hidden="true"></span>
            </div>
            <div class="flex items-baseline gap-1 mt-1">
                <span class="text-2xl font-bold text-[#885D1C] tabular-nums">{{ number_format($totalActual) }}</span>
                <span class="text-xs text-[#53645B]">porsi</span>
            </div>
            @php
                $diff = $totalActual - $totalPlanned;
            @endphp
            <p class="text-[11px] mt-1 font-medium {{ $diff === 0 ? 'text-[#1B573E]' : ($diff > 0 ? 'text-[#285F80]' : 'text-[#9B3232]') }}">
                {{ $diff === 0 ? 'Tepat sesuai target' : ($diff > 0 ? "+{$diff} porsi lebih" : "{$diff} porsi kurang") }}
            </p>
        </div>

        <!-- 4. Porsi Lolos QC / Released -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-4 subtle-shadow">
            <div class="flex items-center justify-between text-[#53645B] mb-1">
                <span class="text-[10px] font-bold uppercase tracking-wider">4. Lolos QC (Siap Distribusi)</span>
                <span class="w-2 h-2 rounded-full bg-[#1B573E]" aria-hidden="true"></span>
            </div>
            <div class="flex items-baseline gap-1 mt-1">
                <span class="text-2xl font-bold text-[#1B573E] tabular-nums">{{ number_format($totalReleased) }}</span>
                <span class="text-xs text-[#53645B]">porsi</span>
            </div>
            <p class="text-[11px] text-[#53645B] mt-1">Otorisasi Pengawas Gizi</p>
        </div>
    </section>

    <!-- Grid Utama: Form Buat Batch & Ringkasan Menu (Kiri) | Daftar Batch & Eksekusi Tahapan (Kanan) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        
        <!-- Kolom Kiri: Informasi Menu & Form Batch Baru -->
        <div class="space-y-6">
            <!-- Kartu Menu Terjadwal -->
            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 subtle-shadow">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-[#EDF2ED]">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#1C6654]">Menu Terjadwal</span>
                        <h2 class="text-base font-bold text-[#172923] mt-0.5">
                            {{ $plan->menu->name }}
                        </h2>
                    </div>
                    <span class="text-xs font-mono font-bold bg-[#FAFBF9] border border-[#DCE5DD] px-2 py-1 rounded">
                        v{{ $plan->menu->version }}
                    </span>
                </div>

                <div class="space-y-2 mb-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#53645B]">Komposisi Hidangan</h3>
                    <div class="space-y-1.5 text-xs">
                        @foreach ($plan->menu->items as $item)
                            <div class="flex items-center justify-between p-2 rounded-lg bg-[#FAFBF9] border border-[#EDF2ED]">
                                <span class="font-medium text-[#172923]">{{ $item->item_name }}</span>
                                <span class="font-mono text-[#53645B]">{{ number_format($item->serving_weight_g) }}g</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="pt-3 border-t border-[#EDF2ED] text-xs text-[#53645B] flex justify-between items-center">
                    <span>Alokasi Penerima:</span>
                    <strong class="text-[#172923]">{{ $plan->allocations->count() }} Sekolah</strong>
                </div>
            </div>

            <!-- Form Buat Batch Baru -->
            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 subtle-shadow">
                <div class="pb-3 mb-4 border-b border-[#EDF2ED]">
                    <h2 class="text-sm font-bold text-[#172923]">Buka Batch Pekerjaan Dapur</h2>
                    <p class="text-xs text-[#53645B] mt-0.5">
                        Bagi pekerjaan memasak ke dalam batch terukur untuk kontrol mutu dan ketertelusuran.
                    </p>
                </div>

                <form method="POST" action="{{ route('production.plans.batches.store', $plan) }}" class="space-y-4">
                    @csrf

                    @php
                        $remainingTarget = max(0, $totalPlanned - $batches->sum('planned_portions'));
                        $defaultBatchTarget = $remainingTarget > 0 ? $remainingTarget : $totalPlanned;
                    @endphp

                    <div>
                        <label for="planned_portions" class="block text-xs font-semibold text-[#172923] mb-1">
                            Target Porsi Batch <span class="text-[#9B3232]">*</span>
                        </label>
                        <input type="number" 
                               id="planned_portions" 
                               name="planned_portions" 
                               value="{{ old('planned_portions', $defaultBatchTarget) }}" 
                               min="1" 
                               required 
                               class="w-full px-3 py-2 text-xs rounded-xl bg-white border border-[#DCE5DD] text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                        <p class="text-[11px] text-[#53645B] mt-1">
                            Sisa target rencana yang belum dibuat batch: <strong>{{ number_format($remainingTarget) }} porsi</strong>
                        </p>
                        @error('planned_portions')
                            <p class="text-[11px] text-[#9B3232] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="portion_category" class="block text-xs font-semibold text-[#172923] mb-1">
                            Kategori Porsi
                        </label>
                        <select id="portion_category" 
                                name="portion_category" 
                                class="w-full px-3 py-2 text-xs rounded-xl bg-white border border-[#DCE5DD] text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                            <option value="STANDARD" {{ old('portion_category') === 'STANDARD' ? 'selected' : '' }}>Standar (Semua Jenjang)</option>
                            <option value="SD" {{ old('portion_category') === 'SD' ? 'selected' : '' }}>Porsi SD (Sekolah Dasar)</option>
                            <option value="SMP" {{ old('portion_category') === 'SMP' ? 'selected' : '' }}>Porsi SMP (Menengah)</option>
                            <option value="SMA" {{ old('portion_category') === 'SMA' ? 'selected' : '' }}>Porsi SMA (Atas)</option>
                        </select>
                    </div>

                    <div>
                        <label for="notes" class="block text-xs font-semibold text-[#172923] mb-1">
                            Catatan Dapur (Opsional)
                        </label>
                        <textarea id="notes" 
                                  name="notes" 
                                  rows="2" 
                                  placeholder="Contoh: Penggorengan kloter pertama untuk wilayah utara" 
                                  class="w-full px-3 py-2 text-xs rounded-xl bg-white border border-[#DCE5DD] text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654]">{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" 
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-[#1C6654] hover:bg-[#154D3F] text-white text-xs font-bold shadow-xs transition-colors touch-target">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <span>Buat Batch Pekerjaan</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Kolom Kanan (2 Kolom): Daftar Batch & Eksekusi Tahapan Produksi -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 subtle-shadow">
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-[#EDF2ED]">
                    <div>
                        <h2 class="text-sm sm:text-base font-bold text-[#172923]">Daftar Batch Produksi Aktif</h2>
                        <p class="text-xs text-[#53645B] mt-0.5">Pantau dan jalankan tahapan memasak, pemorsian, serta serah terima QC</p>
                    </div>
                    <span class="text-xs font-mono font-bold bg-[#EDF7F2] text-[#1B573E] border border-[#BCE2D0] px-2.5 py-1 rounded-lg">
                        {{ $batches->count() }} Batch Terdaftar
                    </span>
                </div>

                @if ($batches->isEmpty())
                    <!-- Empty State -->
                    <div class="p-8 text-center rounded-xl bg-[#FAFBF9] border border-dashed border-[#DCE5DD] space-y-3">
                        <div class="w-12 h-12 rounded-full bg-[#EDF2ED] text-[#1C6654] flex items-center justify-center mx-auto text-xl">
                            🍳
                        </div>
                        <h3 class="text-sm font-bold text-[#172923]">Belum Ada Batch Dibuat</h3>
                        <p class="text-xs text-[#53645B] max-w-sm mx-auto leading-relaxed">
                            Gunakan form di sebelah kiri untuk membuka batch produksi pertama bagi rencana layanan tanggal ini.
                        </p>
                    </div>
                @else
                    <!-- Batch Cards List -->
                    <div class="space-y-6">
                        @foreach ($batches as $batch)
                            <div class="rounded-xl border border-[#DCE5DD] bg-[#FAFBF9] p-4 sm:p-5 space-y-4">
                                
                                <!-- Header Batch: Kode, Status, Target -->
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-[#DCE5DD]">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-mono font-bold text-[#172923]">{{ $batch->batch_code }}</span>
                                            <span class="text-[10px] font-mono px-2 py-0.5 bg-white border border-[#DCE5DD] rounded text-[#53645B]">
                                                {{ $batch->portion_category ?? 'STANDAR' }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-[#53645B] mt-0.5">
                                            Dibuat oleh: {{ $batch->creator->name }} pada {{ $batch->created_at->format('H:i') }} WIB
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <!-- Status Badge -->
                                        @php
                                            $statusClasses = match($batch->status->value) {
                                                'PREPARING' => 'bg-[#EDF2ED] text-[#172923] border-[#DCE5DD]',
                                                'COOKING' => 'bg-[#FAF4EB] text-[#885D1C] border-[#E8D7BE]',
                                                'PORTIONING' => 'bg-[#F0F5FA] text-[#285F80] border-[#BCD4E6]',
                                                'PACKED' => 'bg-[#F5EFFC] text-[#55327A] border-[#DCC7F5]',
                                                'QC_CHECK' => 'bg-[#FAF4EB] text-[#885D1C] border-[#E8D7BE]',
                                                'RELEASED' => 'bg-[#EDF7F2] text-[#1B573E] border-[#BCE2D0]',
                                                'ON_HOLD' => 'bg-[#FDF2F2] text-[#9B3232] border-[#F5C2C2]',
                                                default => 'bg-white text-[#53645B] border-[#DCE5DD]'
                                            };
                                        @endphp
                                        <span class="text-xs font-bold px-2.5 py-1 rounded-lg border {{ $statusClasses }}">
                                            {{ $batch->status->label() }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Stepper Tahapan Operasional Dapur -->
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#53645B] block mb-2">
                                        Progres Tahapan Kerja Dapur
                                    </span>
                                    
                                    @php
                                        $currentStage = $batch->currentStageLog()?->stage;
                                        $stages = [
                                            \App\Enums\ProductionStage::PREPARATION,
                                            \App\Enums\ProductionStage::COOKING,
                                            \App\Enums\ProductionStage::PORTIONING,
                                            \App\Enums\ProductionStage::PACKING,
                                            \App\Enums\ProductionStage::READY_FOR_QC,
                                        ];
                                    @endphp

                                    <div class="grid grid-cols-5 gap-1 text-center">
                                        @foreach ($stages as $stageItem)
                                            @php
                                                $isPast = false;
                                                $isCurrent = false;
                                                $stageLog = $batch->stageLogs->firstWhere('stage', $stageItem);

                                                if ($stageLog && $stageLog->completed_at) {
                                                    $isPast = true;
                                                } elseif ($stageLog && ! $stageLog->completed_at) {
                                                    $isCurrent = true;
                                                }
                                            @endphp
                                            <div class="p-2 rounded-lg text-xs {{ $isPast ? 'bg-[#EDF7F2] border border-[#BCE2D0] text-[#1B573E]' : ($isCurrent ? 'bg-[#1C6654] text-white font-bold' : 'bg-white border border-[#DCE5DD] text-[#708177]') }}">
                                                <span class="block text-[10px] font-mono leading-tight">
                                                    {{ $isPast ? '✓ Selesai' : ($isCurrent ? '▶ Aktif' : 'Antre') }}
                                                </span>
                                                <span class="block text-[11px] font-semibold mt-0.5 truncate">
                                                    {{ $stageItem->label() }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Invarian Porsi Batch: Target vs Aktual vs QC -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3 rounded-xl bg-white border border-[#DCE5DD] text-xs">
                                    <div>
                                        <span class="text-[10px] text-[#53645B] font-bold uppercase block">Target Batch</span>
                                        <span class="text-base font-bold text-[#1C6654] tabular-nums">{{ number_format($batch->planned_portions) }} porsi</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-[#53645B] font-bold uppercase block">Realisasi Aktual</span>
                                        @if ($batch->actual_portions !== null)
                                            <span class="text-base font-bold text-[#885D1C] tabular-nums">{{ number_format($batch->actual_portions) }} porsi</span>
                                            @php
                                                $bDiff = $batch->actual_portions - $batch->planned_portions;
                                            @endphp
                                            <span class="text-[10px] block font-medium {{ $bDiff === 0 ? 'text-[#1B573E]' : ($bDiff > 0 ? 'text-[#285F80]' : 'text-[#9B3232]') }}">
                                                ({{ $bDiff === 0 ? 'Tepat' : ($bDiff > 0 ? "+{$bDiff}" : "{$bDiff}") }})
                                            </span>
                                        @else
                                            <span class="text-xs text-[#708177] italic block mt-0.5">Belum dicatat</span>
                                        @endif
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-[#53645B] font-bold uppercase block">Hasil QC Gizi</span>
                                        @if ($batch->isReleased())
                                            <span class="text-xs font-bold text-[#1B573E] block mt-0.5">Lolos ({{ number_format($batch->actual_portions) }} porsi)</span>
                                            <span class="text-[10px] text-[#53645B] block">{{ $batch->released_at?->format('H:i') }} WIB</span>
                                        @elseif ($batch->isOnHold())
                                            <span class="text-xs font-bold text-[#9B3232] block mt-0.5">Ditahan (On Hold)</span>
                                        @elseif ($batch->status->value === 'QC_CHECK')
                                            <span class="text-xs font-bold text-[#885D1C] block mt-0.5">Menunggu Uji Gizi</span>
                                        @else
                                            <span class="text-xs text-[#708177] italic block mt-0.5">Belum diserahkan</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Aksi Tahapan Sesuai Status Batch -->
                                @if (! $batch->isReleased() && ! $batch->isOnHold() && $batch->status->value !== 'QC_CHECK')
                                    <div class="pt-2 border-t border-[#DCE5DD] space-y-3">
                                        
                                        <!-- Tombol Maju Tahap Jika Masih PREPARATION, COOKING, atau PORTIONING -->
                                        @php
                                            $nextStage = $currentStage?->next();
                                        @endphp

                                        @if ($nextStage && $nextStage !== \App\Enums\ProductionStage::READY_FOR_QC)
                                            <form method="POST" action="{{ route('production.batches.stage', $batch) }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                                @csrf
                                                <input type="hidden" name="stage" value="{{ $nextStage->value }}">
                                                <input type="text" 
                                                       name="notes" 
                                                       placeholder="Catatan tahap (opsional)" 
                                                       class="flex-1 px-3 py-2 text-xs rounded-xl bg-white border border-[#DCE5DD] text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                                <button type="submit" 
                                                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-[#1C6654] hover:bg-[#154D3F] text-white text-xs font-bold rounded-xl shadow-xs transition-colors touch-target">
                                                    <span>Lanjut ke Tahap {{ $nextStage->label() }}</span>
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif

                                        <!-- Form Input Realisasi Porsi Aktual (Bisa diisi saat PORTIONING atau PACKED) -->
                                        @if ($currentStage && in_array($currentStage, [\App\Enums\ProductionStage::PORTIONING, \App\Enums\ProductionStage::PACKING]))
                                            <div class="p-3 rounded-xl bg-white border border-[#DCE5DD] space-y-2">
                                                <span class="text-xs font-bold text-[#172923] block">Pencatatan Porsi Aktual yang Dihasilkan</span>
                                                <form method="POST" action="{{ route('production.batches.actual', $batch) }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                                    @csrf
                                                    <div class="sm:w-36">
                                                        <input type="number" 
                                                               name="actual_portions" 
                                                               value="{{ old('actual_portions', $batch->actual_portions ?? $batch->planned_portions) }}" 
                                                               min="0" 
                                                               required 
                                                               placeholder="Porsi aktual" 
                                                               class="w-full px-3 py-2 text-xs rounded-xl bg-white border border-[#DCE5DD] text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                                    </div>
                                                    <input type="text" 
                                                           name="notes" 
                                                           value="{{ old('notes') }}" 
                                                           placeholder="Catatan porsi / penjelasan selisih" 
                                                           class="flex-1 px-3 py-2 text-xs rounded-xl bg-white border border-[#DCE5DD] text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                                    <button type="submit" 
                                                            class="inline-flex items-center justify-center px-4 py-2 bg-[#885D1C] hover:bg-[#6E4B17] text-white text-xs font-bold rounded-xl shadow-xs transition-colors touch-target">
                                                        <span>Simpan Realisasi</span>
                                                    </button>
                                                </form>
                                            </div>
                                        @endif

                                        <!-- Form Serah Terima ke QC (Hanya bila tahapan PACKING & porsi aktual telah dicatat) -->
                                        @if ($batch->status->value === 'PACKED' || ($currentStage === \App\Enums\ProductionStage::PACKING && $batch->actual_portions !== null))
                                            <div class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] space-y-3">
                                                <div>
                                                    <span class="text-xs font-bold text-[#1B573E] block">Serahkan Batch ke Pemeriksaan Mutu (QC)</span>
                                                    <p class="text-[11px] text-[#53645B] mt-0.5">
                                                        Unggah foto makanan siap kemas dan serahkan ke Pengawas Gizi sebelum batch dapat dimuat ke armada distribusi.
                                                    </p>
                                                </div>

                                                <form method="POST" action="{{ route('production.batches.submit-qc', $batch) }}" enctype="multipart/form-data" class="space-y-3">
                                                    @csrf
                                                    <div>
                                                        <label for="photo_{{ $batch->id }}" class="block text-xs font-semibold text-[#172923] mb-1">
                                                            Foto Bukti Makanan Siap Kemas (Opsional)
                                                        </label>
                                                        <input type="file" 
                                                               id="photo_{{ $batch->id }}" 
                                                               name="photo" 
                                                               accept="image/jpeg,image/png,image/webp" 
                                                               class="w-full text-xs text-[#53645B] file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-white file:text-[#1C6654] file:border file:border-[#DCE5DD] hover:file:bg-[#FAFBF9]">
                                                    </div>

                                                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                                        <input type="text" 
                                                               name="notes" 
                                                               placeholder="Catatan untuk Pengawas Gizi (opsional)" 
                                                               class="flex-1 px-3 py-2 text-xs rounded-xl bg-white border border-[#DCE5DD] text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                                        <button type="submit" 
                                                                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-[#1C6654] hover:bg-[#154D3F] text-white text-xs font-bold rounded-xl shadow-xs transition-colors touch-target">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                            </svg>
                                                            <span>Serahkan ke QC</span>
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <!-- Feedback Status Menunggu QC / Lolos / Ditahan -->
                                @if ($batch->status->value === 'QC_CHECK')
                                    <div class="p-3 rounded-xl bg-[#FAF4EB] border border-[#E8D7BE] text-xs text-[#885D1C] flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-[#885D1C] animate-pulse"></span>
                                            <span>Batch sedang dalam antrean uji organoleptik oleh Pengawas Gizi.</span>
                                        </div>
                                        <span class="font-mono text-[11px] font-bold">Menunggu Verifikasi</span>
                                    </div>
                                @elseif ($batch->isReleased())
                                    <div class="p-3 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-xs text-[#1B573E] flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-[#12B76A]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span>Batch telah lolos uji mutu &amp; diotorisasi untuk pengiriman ke sekolah.</span>
                                        </div>
                                        <span class="font-mono text-[11px] font-bold">Dilepas: {{ $batch->releaser?->name }}</span>
                                    </div>
                                @elseif ($batch->isOnHold())
                                    <div class="p-3 rounded-xl bg-[#FDF2F2] border border-[#F5C2C2] text-xs text-[#9B3232] flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-[#9B3232]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            <span>Batch DITAHAN (On Hold) oleh Pengawas Gizi. Distribusi diblokir hingga tindakan perbaikan selesai.</span>
                                        </div>
                                        <span class="font-mono text-[11px] font-bold">Status: TAHAN</span>
                                    </div>
                                @endif

                                <!-- Riwayat Stage Logs Accordion / Collapse -->
                                <details class="text-xs group border-t border-[#EDF2ED] pt-2">
                                    <summary class="cursor-pointer font-semibold text-[#53645B] hover:text-[#172923] flex items-center justify-between py-1">
                                        <span>Riwayat Log Tahapan Kerja ({{ $batch->stageLogs->count() }} catatan)</span>
                                        <span class="text-[10px] text-[#708177] group-open:rotate-180 transition-transform">▼</span>
                                    </summary>
                                    <div class="mt-2 space-y-1.5 pl-2 border-l-2 border-[#DCE5DD]">
                                        @foreach ($batch->stageLogs as $log)
                                            <div class="text-[11px] text-[#53645B] flex items-baseline justify-between">
                                                <div>
                                                    <strong class="text-[#172923]">{{ $log->stage->label() }}</strong>:
                                                    {{ $log->notes ?? 'Diproses' }}
                                                </div>
                                                <span class="font-mono text-[10px] text-[#708177] shrink-0 ml-2">
                                                    {{ $log->started_at->format('H:i') }} WIB
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </details>

                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Tabel Alokasi Sekolah Terdaftar (Referensi Distribusi) -->
            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 subtle-shadow">
                <div class="pb-3 mb-3 border-b border-[#EDF2ED] flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-[#172923]">Target Alokasi Sekolah Penerima</h2>
                        <p class="text-xs text-[#53645B] mt-0.5">Rujukan jumlah ompreng saat pemorsian dan pengemasan</p>
                    </div>
                    <span class="text-xs font-mono font-bold text-[#1C6654] bg-[#EDF7F2] border border-[#BCE2D0] px-2.5 py-1 rounded">
                        Total: {{ number_format($totalPlanned) }} Porsi
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#FAFBF9] border-b border-[#DCE5DD] text-[#53645B] text-[10px] uppercase font-bold">
                            <tr>
                                <th scope="col" class="py-2.5 px-3">Sekolah Penerima</th>
                                <th scope="col" class="py-2.5 px-3">Jenjang</th>
                                <th scope="col" class="py-2.5 px-3 text-right">Target Porsi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#EDF2ED]">
                            @foreach ($plan->allocations as $allocation)
                                <tr class="hover:bg-[#FAFBF9]">
                                    <td class="py-2.5 px-3 font-semibold text-[#172923]">{{ $allocation->school->name }}</td>
                                    <td class="py-2.5 px-3">
                                        <span class="px-1.5 py-0.5 text-[10px] font-bold bg-[#EDF2ED] rounded">
                                            {{ $allocation->school->education_level }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono font-bold text-[#1C6654]">
                                        {{ number_format($allocation->allocated_portions) }} porsi
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</x-app-shell>
