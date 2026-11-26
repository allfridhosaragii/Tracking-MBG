<x-app-shell title="Workspace Eksekusi Dapur">
    <div class="space-y-5">
        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <a href="{{ route('production.dashboard', ['date' => $plan->service_date->toDateString()]) }}" class="text-xs font-semibold text-[#1C6654] hover:underline inline-flex items-center gap-1 mb-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Kembali ke Runbook</span>
                </a>
                <h1 class="page-title">Eksekusi Produksi &amp; Pelacakan Batch Dapur</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-1">
                    {{ $plan->sppg->name }} • {{ $plan->service_date->translatedFormat('l, d F Y') }} • {{ $plan->status->label() }}
                </p>
            </div>
        </header>

        <!-- 4 Production Invariant Metrics -->
        <section aria-label="Metrik Invarian Produksi" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="luxury-surface p-4">
                <span class="eyebrow-label block">Target Rencana</span>
                <div class="mt-1.5 flex items-baseline gap-1">
                    <span class="text-2xl font-bold text-[#1C6654] tabular-nums">{{ number_format($totalPlanned) }}</span>
                    <span class="text-xs text-[#53645B]">porsi</span>
                </div>
            </div>

            <div class="luxury-surface p-4">
                <span class="eyebrow-label block">Akumulasi Batch</span>
                <div class="mt-1.5 flex items-baseline gap-1">
                    <span class="text-2xl font-bold text-[#285F80] tabular-nums">{{ number_format($batches->sum('planned_portions')) }}</span>
                    <span class="text-xs text-[#53645B]">porsi ({{ $batches->count() }})</span>
                </div>
            </div>

            <div class="luxury-surface p-4">
                <span class="eyebrow-label block">Realisasi Aktual</span>
                <div class="mt-1.5 flex items-baseline gap-1">
                    <span class="text-2xl font-bold text-[#885D1C] tabular-nums">{{ number_format($totalActual) }}</span>
                    <span class="text-xs text-[#53645B]">porsi</span>
                </div>
            </div>

            <div class="luxury-surface p-4">
                <span class="eyebrow-label block">Lolos QC</span>
                <div class="mt-1.5 flex items-baseline gap-1">
                    <span class="text-2xl font-bold text-[#21684B] tabular-nums">{{ number_format($totalReleased) }}</span>
                    <span class="text-xs text-[#53645B]">porsi</span>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
            <!-- Left: Open New Batch & Menu Reference (4 cols) -->
            <div class="lg:col-span-4 space-y-5">
                <x-card title="Buka Batch Pekerjaan Dapur" subtitle="Bagi proses masak ke dalam batch terukur untuk kontrol mutu.">
                    @php
                        $remainingTarget = max(0, $totalPlanned - $batches->sum('planned_portions'));
                        $defaultBatchTarget = $remainingTarget > 0 ? $remainingTarget : $totalPlanned;
                    @endphp

                    <form method="POST" action="{{ route('production.plans.batches.store', $plan) }}" class="space-y-4">
                        @csrf

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
                                   class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm rounded-xl bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] text-[#172923] font-mono font-bold tabular-nums">
                            <p class="text-[11px] text-[#53645B] mt-1">Sisa belum masuk batch: <strong class="tabular-nums">{{ number_format($remainingTarget) }} porsi</strong></p>
                            @error('planned_portions')
                                <p class="text-[11px] text-[#9B3232] mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="portion_category" class="block text-xs font-semibold text-[#172923] mb-1">Kategori Porsi</label>
                            <select id="portion_category"
                                    name="portion_category"
                                    class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm rounded-xl bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] text-[#172923]">
                                <option value="STANDARD" {{ old('portion_category') === 'STANDARD' ? 'selected' : '' }}>Standar (Semua Jenjang)</option>
                                <option value="SD" {{ old('portion_category') === 'SD' ? 'selected' : '' }}>Porsi SD</option>
                                <option value="SMP" {{ old('portion_category') === 'SMP' ? 'selected' : '' }}>Porsi SMP</option>
                                <option value="SMA" {{ old('portion_category') === 'SMA' ? 'selected' : '' }}>Porsi SMA</option>
                            </select>
                        </div>

                        <div>
                            <label for="notes" class="block text-xs font-semibold text-[#172923] mb-1">Catatan Dapur (Opsional)</label>
                            <textarea id="notes"
                                      name="notes"
                                      rows="2"
                                      placeholder="Contoh: Kloter pertama wilayah utara"
                                      class="w-full px-3.5 py-2 text-xs rounded-xl bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] text-[#172923]">{{ old('notes') }}</textarea>
                        </div>

                        <button type="submit"
                                class="w-full min-h-[46px] px-4 py-2.5 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs sm:text-sm font-bold cursor-pointer smooth-transition">
                            + Buat Batch Pekerjaan
                        </button>
                    </form>
                </x-card>

                <!-- Menu Reference Disclosure -->
                <details class="sop-disclosure" open>
                    <summary>
                        <span>Resep: {{ $plan->menu->name }} (v{{ $plan->menu->version }})</span>
                        <svg class="w-4 h-4 text-[#53645B] disclosure-chevron shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <div class="p-4 space-y-1.5 text-xs">
                        @foreach ($plan->menu->items as $item)
                            <div class="flex items-center justify-between py-1 border-b border-[#E7ECE8] last:border-b-0">
                                <span class="text-[#172923] font-medium">{{ $item->item_name }}</span>
                                <span class="font-mono text-[#53645B] tabular-nums">{{ number_format($item->serving_weight_g) }}g</span>
                            </div>
                        @endforeach
                    </div>
                </details>
            </div>

            <!-- Right: Active Batches & Allocations (8 cols) -->
            <div class="lg:col-span-8 space-y-5">
                <x-card title="Daftar Batch Produksi Aktif" subtitle="Jalankan tahapan persiapan, memasak, pemorsian, pengemasan, dan pengajuan QC." badge="{{ $batches->count() }} Batch">
                    @if ($batches->isEmpty())
                        <div class="p-8 text-center inset-surface space-y-2">
                            <div class="w-10 h-10 rounded-xl bg-[#EDF7F2] text-[#1C6654] flex items-center justify-center mx-auto">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-[#172923]">Belum Ada Batch Dibuat</h3>
                            <p class="text-xs text-[#53645B] max-w-sm mx-auto">Buka batch pekerjaan pertama menggunakan formulir untuk mulai mencatat progres dapur.</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach ($batches as $batch)
                                @php
                                    $statusClasses = match($batch->status->value) {
                                        'PREPARING' => 'bg-[#FAFBF9] text-[#172923] border-[#DCE5DD]',
                                        'COOKING' => 'bg-[#FEF7EC] text-[#885D1C] border-[#F7D7A3]',
                                        'PORTIONING' => 'bg-[#F0F6FA] text-[#285F80] border-[#C2DBE8]',
                                        'PACKED' => 'bg-[#F4F0F7] text-[#55327A] border-[#D8C7EA]',
                                        'QC_CHECK' => 'bg-[#FEF7EC] text-[#885D1C] border-[#F7D7A3]',
                                        'RELEASED' => 'bg-[#EDF7F2] text-[#21684B] border-[#BCE2D0]',
                                        'ON_HOLD' => 'bg-[#FDF2F2] text-[#9B3232] border-[#F5C2C2]',
                                        default => 'bg-white text-[#53645B] border-[#DCE5DD]'
                                    };
                                    $currentStage = $batch->currentStageLog()?->stage;
                                    $stages = [
                                        \App\Enums\ProductionStage::PREPARATION,
                                        \App\Enums\ProductionStage::COOKING,
                                        \App\Enums\ProductionStage::PORTIONING,
                                        \App\Enums\ProductionStage::PACKING,
                                        \App\Enums\ProductionStage::READY_FOR_QC,
                                    ];
                                @endphp

                                <div class="inset-surface p-4 sm:p-5 space-y-4">
                                    <!-- Batch Header -->
                                    <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-[#E7ECE8]">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-mono font-bold text-[#172923]">{{ $batch->batch_code }}</span>
                                                <span class="text-[10px] font-mono px-2 py-0.5 bg-white border border-[#DCE5DD] rounded-md text-[#53645B]">
                                                    {{ $batch->portion_category ?? 'STANDAR' }}
                                                </span>
                                            </div>
                                            <span class="text-[11px] text-[#53645B] block mt-0.5">{{ $batch->creator->name }} • {{ $batch->created_at->format('H:i') }} WIB</span>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-semibold px-2.5 py-1 rounded-lg border {{ $statusClasses }}">
                                                {{ $batch->status->label() }}
                                            </span>
                                            <a href="{{ route('batches.show', $batch) }}"
                                               class="min-h-[38px] px-3 py-1.5 rounded-lg bg-white border border-[#DCE5DD] text-xs font-semibold text-[#172923] hover:bg-[#F5F7F3] inline-flex items-center">
                                                Jejak
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Responsive Stage Stepper (Wraps cleanly on Mobile 320px-390px) -->
                                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-1.5">
                                        @foreach ($stages as $stageItem)
                                            @php
                                                $stageLog = $batch->stageLogs->firstWhere('stage', $stageItem);
                                                $isPast = $stageLog && $stageLog->completed_at;
                                                $isCurrent = $stageLog && ! $stageLog->completed_at;
                                            @endphp
                                            <div class="px-2.5 py-2 rounded-lg text-xs {{ $isPast ? 'bg-[#EDF7F2] border border-[#BCE2D0] text-[#21684B]' : ($isCurrent ? 'bg-[#1C6654] text-white font-bold' : 'bg-white border border-[#E7ECE8] text-[#53645B]') }}">
                                                <span class="block text-[10px] opacity-80">{{ $isPast ? 'Selesai' : ($isCurrent ? 'Aktif' : 'Antre') }}</span>
                                                <span class="block text-[11px] font-semibold truncate mt-0.5">{{ $stageItem->label() }}</span>
                                            </div>
                                        @endforeach
                                    </div>

                                    <!-- Portion Summary -->
                                    <div class="grid grid-cols-3 gap-2 p-3 rounded-xl bg-white border border-[#E7ECE8] text-xs">
                                        <div>
                                            <span class="text-[10px] text-[#53645B] block">Target</span>
                                            <span class="font-bold text-[#1C6654] tabular-nums">{{ number_format($batch->planned_portions) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-[10px] text-[#53645B] block">Aktual</span>
                                            <span class="font-bold text-[#172923] tabular-nums">{{ $batch->actual_portions !== null ? number_format($batch->actual_portions) : '-' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-[10px] text-[#53645B] block">Status QC</span>
                                            <span class="font-semibold {{ $batch->isReleased() ? 'text-[#21684B]' : ($batch->isOnHold() ? 'text-[#9B3232]' : 'text-[#53645B]') }}">
                                                {{ $batch->isReleased() ? 'Lolos' : ($batch->isOnHold() ? 'Ditahan' : ($batch->status->value === 'QC_CHECK' ? 'Di Meja QC' : 'Dapur')) }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Stage Actions -->
                                    @if (! $batch->isReleased() && ! $batch->isOnHold() && $batch->status->value !== 'QC_CHECK')
                                        <div class="space-y-3 pt-2 border-t border-[#E7ECE8]">
                                            @php $nextStage = $currentStage?->next(); @endphp

                                            @if ($nextStage && $nextStage !== \App\Enums\ProductionStage::READY_FOR_QC)
                                                <form method="POST" action="{{ route('production.batches.stage', $batch) }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                                    @csrf
                                                    <input type="hidden" name="stage" value="{{ $nextStage->value }}">
                                                    <input type="text"
                                                           name="notes"
                                                           placeholder="Catatan tahap (opsional)"
                                                           class="flex-1 min-h-[44px] px-3 py-2 text-xs rounded-xl bg-white border border-[#DCE5DD] text-[#172923]">
                                                    <button type="submit"
                                                            class="min-h-[44px] px-4 py-2 bg-[#1C6654] hover:bg-[#164D42] text-white text-xs font-bold rounded-xl cursor-pointer smooth-transition">
                                                        Lanjut ke {{ $nextStage->label() }} &rarr;
                                                    </button>
                                                </form>
                                            @endif

                                            @if ($currentStage && in_array($currentStage, [\App\Enums\ProductionStage::PORTIONING, \App\Enums\ProductionStage::PACKING]))
                                                <div class="p-3.5 rounded-xl bg-white border border-[#DCE5DD] space-y-2">
                                                    <span class="text-xs font-bold text-[#172923] block">Catat Porsi Aktual Dihasilkan</span>
                                                    <form method="POST" action="{{ route('production.batches.actual', $batch) }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                                        @csrf
                                                        <input type="number"
                                                               name="actual_portions"
                                                               value="{{ old('actual_portions', $batch->actual_portions ?? $batch->planned_portions) }}"
                                                               min="0"
                                                               required
                                                               class="sm:w-32 min-h-[44px] px-3 py-2 text-xs rounded-xl bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] text-[#172923] font-mono font-bold tabular-nums">
                                                        <input type="text"
                                                               name="notes"
                                                               value="{{ old('notes') }}"
                                                               placeholder="Keterangan selisih (opsional)"
                                                               class="flex-1 min-h-[44px] px-3 py-2 text-xs rounded-xl bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] text-[#172923]">
                                                        <button type="submit"
                                                                class="min-h-[44px] px-4 py-2 bg-[#885D1C] hover:bg-[#6E4B17] text-white text-xs font-bold rounded-xl cursor-pointer smooth-transition">
                                                            Simpan Realisasi
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif

                                            @if ($batch->status->value === 'PACKED' || ($currentStage === \App\Enums\ProductionStage::PACKING && $batch->actual_portions !== null))
                                                <div class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] space-y-3">
                                                    <span class="text-xs font-bold text-[#1B573E] block">Ajukan Batch ke Pemeriksaan Mutu (QC)</span>

                                                    <form method="POST" action="{{ route('production.batches.submit-qc', $batch) }}" enctype="multipart/form-data" class="space-y-3">
                                                        @csrf
                                                        <div>
                                                            <label for="photo_{{ $batch->id }}" class="block text-xs font-semibold text-[#172923] mb-1">
                                                                Foto Bukti Makanan Siap Kemas
                                                            </label>
                                                            <input type="file"
                                                                   id="photo_{{ $batch->id }}"
                                                                   name="photo"
                                                                   accept="image/jpeg,image/png,image/webp"
                                                                   class="w-full text-xs text-[#53645B] file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border file:border-[#DCE5DD] file:text-xs file:font-semibold file:bg-white file:text-[#1C6654]">
                                                        </div>

                                                        <details class="rounded-xl bg-white border border-[#DCE5DD] p-3 text-xs">
                                                            <summary class="cursor-pointer font-semibold text-[#885D1C]">Prosedur Darurat Tanpa Kamera</summary>
                                                            <div class="mt-2.5 space-y-2 pt-2 border-t border-[#E7ECE8]">
                                                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                                                    <input type="checkbox" name="has_evidence_exception" value="1" id="exception_check_{{ $batch->id }}" class="rounded text-[#885D1C]">
                                                                    <span>Ajukan pengecualian foto bukti</span>
                                                                </label>
                                                                <input type="text"
                                                                       name="evidence_exception_reason"
                                                                       placeholder="Alasan resmi pengecualian (min. 15 karakter)"
                                                                       class="w-full min-h-[40px] px-3 py-1.5 text-xs rounded-lg bg-[#FAFBF9] border border-[#DCE5DD] text-[#172923]">
                                                            </div>
                                                        </details>

                                                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                                            <input type="text"
                                                                   name="notes"
                                                                   placeholder="Catatan untuk Pengawas Gizi (opsional)"
                                                                   class="flex-1 min-h-[44px] px-3 py-2 text-xs rounded-xl bg-white border border-[#DCE5DD] text-[#172923]">
                                                            <button type="submit"
                                                                    class="min-h-[44px] px-5 py-2.5 bg-[#1C6654] hover:bg-[#164D42] text-white text-xs font-bold rounded-xl cursor-pointer smooth-transition">
                                                                Serahkan ke QC
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    @if ($batch->status->value === 'QC_CHECK')
                                        <div class="p-3 rounded-xl bg-[#FEF7EC] border border-[#F7D7A3] text-xs text-[#885D1C] flex items-center justify-between gap-2">
                                            <span>Menunggu pemeriksaan organoleptik oleh Pengawas Gizi.</span>
                                            <span class="font-semibold shrink-0">Antrean QC</span>
                                        </div>
                                    @elseif ($batch->isReleased())
                                        <div class="p-3 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-xs text-[#1B573E] flex items-center justify-between gap-2">
                                            <span>Batch lolos uji mutu dan siap dimuat ke armada distribusi.</span>
                                            <span class="font-semibold shrink-0">{{ $batch->releaser?->name }}</span>
                                        </div>
                                    @elseif ($batch->isOnHold())
                                        <div class="p-3 rounded-xl bg-[#FDF2F2] border border-[#F5C2C2] text-xs text-[#9B3232]">
                                            Batch ditahan (On Hold) oleh Pengawas Gizi. Pemuatan diblokir.
                                        </div>
                                    @endif

                                    <!-- Stage Logs Disclosure -->
                                    <details class="text-xs border-t border-[#E7ECE8] pt-2">
                                        <summary class="cursor-pointer font-semibold text-[#53645B] hover:text-[#172923] py-1">
                                            Log Tahapan Kerja ({{ $batch->stageLogs->count() }})
                                        </summary>
                                        <div class="mt-2 space-y-1.5 pl-3 border-l-2 border-[#DCE5DD]">
                                            @foreach ($batch->stageLogs as $log)
                                                <div class="flex items-baseline justify-between gap-2 text-[11px] text-[#53645B]">
                                                    <span><strong class="text-[#172923]">{{ $log->stage->label() }}</strong>: {{ $log->notes ?? 'Diproses' }}</span>
                                                    <span class="font-mono shrink-0 tabular-nums">{{ $log->started_at->format('H:i') }} WIB</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>

                <!-- School Allocation Reference -->
                <x-card title="Target Alokasi Sekolah Penerima" badge="{{ number_format($totalPlanned) }} Porsi">
                    <div class="divide-y divide-[#E7ECE8] -my-1 text-xs">
                        @foreach ($plan->allocations as $allocation)
                            <div class="py-2.5 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <span class="font-semibold text-[#172923] block truncate">{{ $allocation->school->name }}</span>
                                    <span class="text-[11px] text-[#53645B]">{{ $allocation->school->education_level }}</span>
                                </div>
                                <span class="font-mono font-bold text-[#1C6654] shrink-0 tabular-nums">{{ number_format($allocation->allocated_portions) }} porsi</span>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</x-app-shell>
