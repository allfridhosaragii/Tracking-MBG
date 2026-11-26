<x-app-shell title="Meja Inspeksi Mutu (QC)">
    <div class="space-y-5">
        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <span class="eyebrow-label text-[#285F80] block mb-1">Pengawas Gizi • {{ auth()->user()->sppg->code }}</span>
                <h1 class="page-title">Pemeriksaan Mutu Batch &amp; Keamanan Pangan</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-1">Uji sensorik dan suhu inti setiap batch masakan sebelum diizinkan naik ke armada pengantaran.</p>
            </div>

            <form method="GET" action="{{ route('nutrition.qc.index') }}" class="flex items-center gap-2 shrink-0">
                <label for="qc-date" class="text-xs font-semibold text-[#53645B]">Tanggal:</label>
                <input type="date"
                       id="qc-date"
                       name="date"
                       value="{{ $selectedDate }}"
                       onchange="this.form.submit()"
                       class="min-h-[44px] px-3 py-2 text-xs bg-white border border-[#DCE5DD] rounded-xl text-[#172923] font-medium">
                @if ($selectedDate)
                    <a href="{{ route('nutrition.qc.index') }}" class="min-h-[44px] px-3 py-2 text-xs font-semibold text-[#53645B] bg-[#EDF2ED] rounded-xl inline-flex items-center">Reset</a>
                @endif
            </form>
        </header>

        @php
            $waitingCount = $pendingBatches->where('status.value', 'QC_CHECK')->count();
            $onHoldCount = $pendingBatches->where('status.value', 'ON_HOLD')->count();
            $releasedToday = $monitoringBatches->where('status.value', 'RELEASED')->count();
        @endphp

        <!-- Clean Metrics Strip (Zero Infinite Pulse) -->
        <section aria-label="Metrik Antrean Pemeriksaan Mutu" class="grid grid-cols-3 gap-3 sm:gap-4">
            <div class="luxury-surface p-3.5 sm:p-4">
                <span class="eyebrow-label block">Menunggu Uji</span>
                <span class="text-2xl font-bold text-[#885D1C] tabular-nums mt-1 block">{{ $waitingCount }}</span>
            </div>
            <div class="luxury-surface p-3.5 sm:p-4">
                <span class="eyebrow-label block">Ditahan</span>
                <span class="text-2xl font-bold text-[#9B3232] tabular-nums mt-1 block">{{ $onHoldCount }}</span>
            </div>
            <div class="luxury-surface p-3.5 sm:p-4">
                <span class="eyebrow-label block">Lolos QC</span>
                <span class="text-2xl font-bold text-[#21684B] tabular-nums mt-1 block">{{ $releasedToday }}</span>
            </div>
        </section>

        <!-- Active QC Queue -->
        <x-card title="Antrean Tindakan QC" subtitle="Batch dapur yang memerlukan evaluasi organoleptik segera." badge="{{ $pendingBatches->count() }} Batch">
            @if ($pendingBatches->isEmpty())
                <div class="p-8 text-center inset-surface space-y-2">
                    <div class="w-10 h-10 rounded-xl bg-[#EDF7F2] text-[#21684B] flex items-center justify-center mx-auto">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-[#172923]">Antrean Pemeriksaan Bersih</h3>
                    <p class="text-xs text-[#53645B] max-w-sm mx-auto">Belum ada batch baru yang diajukan Asisten Lapangan untuk diuji saat ini.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($pendingBatches as $batch)
                        <div class="rounded-xl border {{ $batch->isOnHold() ? 'border-[#F5C2C2] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} p-4 space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="text-sm font-mono font-bold text-[#172923] block">{{ $batch->batch_code }}</span>
                                    <span class="text-xs text-[#53645B] block mt-0.5">{{ $batch->productionPlan->menu->name }} (v{{ $batch->productionPlan->menu->version }})</span>
                                </div>
                                <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-lg border {{ $batch->isOnHold() ? 'bg-white text-[#9B3232] border-[#F5C2C2]' : 'bg-[#FEF7EC] text-[#885D1C] border-[#F7D7A3]' }}">
                                    {{ $batch->status->label() }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between text-xs text-[#53645B] pt-2 border-t border-[#E7ECE8] tabular-nums">
                                <span>Realisasi: <strong class="text-[#172923]">{{ number_format($batch->actual_portions ?? $batch->planned_portions) }} porsi</strong></span>
                                <span>{{ $batch->updated_at->format('H:i') }} WIB</span>
                            </div>

                            <a href="{{ route('nutrition.qc.show', $batch) }}"
                               class="w-full min-h-[44px] px-4 py-2.5 rounded-xl {{ $batch->isOnHold() ? 'bg-[#9B3232] hover:bg-[#7D2828]' : 'bg-[#285F80] hover:bg-[#1E4861]' }} text-white text-xs font-semibold inline-flex items-center justify-center gap-2 smooth-transition">
                                <span>{{ $batch->isOnHold() ? 'Evaluasi Ulang Batch Ditahan' : 'Lakukan Uji Mutu Organoleptik' }}</span>
                                <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

        <!-- Monitoring History -->
        <x-card title="Riwayat Pemeriksaan Batch" subtitle="Ketertelusuran keputusan QC dan status pelepasan porsi.">
            @if ($monitoringBatches->isEmpty())
                <p class="py-6 text-center text-xs text-[#53645B]">Belum ada catatan batch untuk tanggal ini.</p>
            @else
                <!-- Desktop Table -->
                <div class="hidden md:block -mx-6 -mb-6 overflow-x-auto">
                    <table class="ledger-table text-xs">
                        <thead>
                            <tr>
                                <th>Kode Batch</th>
                                <th>Menu</th>
                                <th class="text-right">Target</th>
                                <th class="text-right">Realisasi</th>
                                <th>Status Mutu</th>
                                <th>Pemeriksa</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($monitoringBatches as $mBatch)
                                @php
                                    $mBadge = match($mBatch->status->value) {
                                        'RELEASED' => 'bg-[#EDF7F2] text-[#21684B] border-[#BCE2D0]',
                                        'ON_HOLD' => 'bg-[#FDF2F2] text-[#9B3232] border-[#F5C2C2]',
                                        'QC_CHECK' => 'bg-[#FEF7EC] text-[#885D1C] border-[#F7D7A3]',
                                        default => 'bg-[#FAFBF9] text-[#53645B] border-[#DCE5DD]'
                                    };
                                @endphp
                                <tr>
                                    <td class="font-mono font-semibold text-[#172923]">
                                        {{ $mBatch->batch_code }}
                                        <span class="block text-[11px] font-sans font-normal text-[#53645B]">{{ $mBatch->productionPlan->service_date->translatedFormat('d M Y') }}</span>
                                    </td>
                                    <td>
                                        <span class="font-semibold text-[#172923] block">{{ $mBatch->productionPlan->menu->name }}</span>
                                        <span class="text-[11px] text-[#53645B]">v{{ $mBatch->productionPlan->menu->version }}</span>
                                    </td>
                                    <td class="text-right font-mono tabular-nums text-[#53645B]">{{ number_format($mBatch->planned_portions) }}</td>
                                    <td class="text-right font-mono font-bold tabular-nums text-[#172923]">{{ $mBatch->actual_portions !== null ? number_format($mBatch->actual_portions) : '-' }}</td>
                                    <td>
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $mBadge }}">{{ $mBatch->status->label() }}</span>
                                    </td>
                                    <td class="text-[#53645B]">
                                        @if ($mBatch->releaser)
                                            <span class="font-medium text-[#172923] block">{{ $mBatch->releaser->name }}</span>
                                            <span class="text-[11px] font-mono">{{ $mBatch->released_at?->format('H:i') }} WIB</span>
                                        @else
                                            <span>Belum disahkan</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('nutrition.qc.show', $mBatch) }}"
                                           class="min-h-[44px] px-3.5 py-2 text-xs font-semibold text-[#285F80] bg-[#F0F6FA] border border-[#C2DBE8] rounded-xl inline-flex items-center">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden divide-y divide-[#E7ECE8] -my-1">
                    @foreach ($monitoringBatches as $mBatch)
                        @php
                            $mBadge = match($mBatch->status->value) {
                                'RELEASED' => 'bg-[#EDF7F2] text-[#21684B] border-[#BCE2D0]',
                                'ON_HOLD' => 'bg-[#FDF2F2] text-[#9B3232] border-[#F5C2C2]',
                                'QC_CHECK' => 'bg-[#FEF7EC] text-[#885D1C] border-[#F7D7A3]',
                                default => 'bg-[#FAFBF9] text-[#53645B] border-[#DCE5DD]'
                            };
                        @endphp
                        <a href="{{ route('nutrition.qc.show', $mBatch) }}" class="py-3.5 block space-y-1.5">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="font-mono text-xs font-bold text-[#172923] block">{{ $mBatch->batch_code }}</span>
                                    <span class="text-xs text-[#53645B] block">{{ $mBatch->productionPlan->menu->name }}</span>
                                </div>
                                <span class="shrink-0 px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $mBadge }}">{{ $mBatch->status->label() }}</span>
                            </div>
                            <div class="flex items-center justify-between text-[11px] text-[#53645B] tabular-nums">
                                <span>Realisasi: {{ $mBatch->actual_portions !== null ? number_format($mBatch->actual_portions) : '-' }} porsi</span>
                                <span>{{ $mBatch->productionPlan->service_date->translatedFormat('d M Y') }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</x-app-shell>
