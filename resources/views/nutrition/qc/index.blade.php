<x-app-shell title="Desk Verifikasi Mutu & QC : Pengawas Gizi">
    <!-- Header QC Desk -->
    <header class="mb-6 pb-4 border-b border-[#DCE5DD]">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <x-role-badge :role="auth()->user()->role" />
                    <span class="text-xs font-mono text-[#53645B] bg-white border border-[#DCE5DD] px-2 py-0.5 rounded">
                        SPPG: {{ auth()->user()->sppg->code }}
                    </span>
                    <span class="text-xs text-[#285F80] font-semibold bg-[#F0F5FA] border border-[#BCD4E6] px-2 py-0.5 rounded flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#285F80]" aria-hidden="true"></span>
                        <span>Otorisasi Keamanan Pangan</span>
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">
                    Pemeriksaan Mutu Batch &amp; Kontrol Keamanan Pangan
                </h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">
                    Keputusan uji organoleptik dan kelayakan konsumsi sebelum makanan dimuat ke armada distribusi sekolah.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('nutrition.dashboard') }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-[#DCE5DD] text-xs font-semibold text-[#172923] hover:bg-[#FAFBF9] transition-colors touch-target">
                    <svg class="w-4 h-4 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Kembali ke Desk Gizi</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Metrik Antrean QC -->
    <section aria-labelledby="qc-metrics-heading" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <h2 id="qc-metrics-heading" class="sr-only">Metrik Antrean Pemeriksaan Mutu</h2>

        @php
            $waitingCount = $pendingBatches->where('status.value', 'QC_CHECK')->count();
            $onHoldCount = $pendingBatches->where('status.value', 'ON_HOLD')->count();
            $releasedToday = $monitoringBatches->where('status.value', 'RELEASED')->count();
        @endphp

        <!-- 1. Menunggu Uji Organoleptik -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-4 subtle-shadow">
            <div class="flex items-center justify-between text-[#53645B] mb-1">
                <span class="text-[10px] font-bold uppercase tracking-wider">Antrean Uji QC</span>
                <span class="w-2.5 h-2.5 rounded-full {{ $waitingCount > 0 ? 'bg-[#885D1C] animate-pulse' : 'bg-[#DCE5DD]' }}" aria-hidden="true"></span>
            </div>
            <div class="flex items-baseline gap-1 mt-1">
                <span class="text-2xl font-bold text-[#885D1C] tabular-nums">{{ $waitingCount }}</span>
                <span class="text-xs text-[#53645B]">batch</span>
            </div>
            <p class="text-[11px] text-[#53645B] mt-1">Menunggu keputusan uji kelayakan</p>
        </div>

        <!-- 2. Batch Ditahan (On Hold) -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-4 subtle-shadow">
            <div class="flex items-center justify-between text-[#53645B] mb-1">
                <span class="text-[10px] font-bold uppercase tracking-wider">Batch Ditahan (On Hold)</span>
                <span class="w-2.5 h-2.5 rounded-full {{ $onHoldCount > 0 ? 'bg-[#9B3232]' : 'bg-[#DCE5DD]' }}" aria-hidden="true"></span>
            </div>
            <div class="flex items-baseline gap-1 mt-1">
                <span class="text-2xl font-bold text-[#9B3232] tabular-nums">{{ $onHoldCount }}</span>
                <span class="text-xs text-[#53645B]">batch</span>
            </div>
            <p class="text-[11px] text-[#53645B] mt-1">Distribusi diblokir secara mutlak</p>
        </div>

        <!-- 3. Lolos QC & Siap Distribusi -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-4 subtle-shadow">
            <div class="flex items-center justify-between text-[#53645B] mb-1">
                <span class="text-[10px] font-bold uppercase tracking-wider">Lolos QC (Siap Distribusi)</span>
                <span class="w-2.5 h-2.5 rounded-full bg-[#12B76A]" aria-hidden="true"></span>
            </div>
            <div class="flex items-baseline gap-1 mt-1">
                <span class="text-2xl font-bold text-[#1B573E] tabular-nums">{{ $releasedToday }}</span>
                <span class="text-xs text-[#53645B]">batch</span>
            </div>
            <p class="text-[11px] text-[#53645B] mt-1">Dilepas ke kurir untuk pengiriman</p>
        </div>
    </section>

    <!-- Bagian 1: Antrean Batch Menunggu Pemeriksaan QC -->
    <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 mb-8 subtle-shadow">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-[#EDF2ED]">
            <div>
                <h2 class="text-sm sm:text-base font-bold text-[#172923]">Antrean Tindakan QC: Uji Organoleptik &amp; Keputusan</h2>
                <p class="text-xs text-[#53645B] mt-0.5">Lakukan evaluasi fisik sebelum menandatangani persetujuan pelepasan makanan</p>
            </div>
            <span class="text-xs font-mono font-bold bg-[#FAF4EB] text-[#885D1C] border border-[#E8D7BE] px-2.5 py-1 rounded-lg">
                {{ $pendingBatches->count() }} Perlu Perhatian
            </span>
        </div>

        @if ($pendingBatches->isEmpty())
            <div class="p-8 text-center rounded-xl bg-[#FAFBF9] border border-dashed border-[#DCE5DD] space-y-3">
                <div class="w-12 h-12 rounded-full bg-[#EDF7F2] text-[#1B573E] flex items-center justify-center mx-auto text-xl">
                    ✓
                </div>
                <h3 class="text-sm font-bold text-[#172923]">Antrean Pemeriksaan Bersih</h3>
                <p class="text-xs text-[#53645B] max-w-sm mx-auto leading-relaxed">
                    Tidak ada batch yang sedang menunggu pemeriksaan QC saat ini. Batch yang diserahkan oleh kru dapur akan muncul secara otomatis di sini.
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($pendingBatches as $batch)
                    <div class="rounded-xl border {{ $batch->isOnHold() ? 'border-[#F5C2C2] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} p-4 space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b {{ $batch->isOnHold() ? 'border-[#F5C2C2]' : 'border-[#DCE5DD]' }}">
                            <div>
                                <span class="text-sm font-mono font-bold text-[#172923]">{{ $batch->batch_code }}</span>
                                <span class="text-[10px] text-[#53645B] block">
                                    Menu: <strong>{{ $batch->productionPlan->menu->name }}</strong> (v{{ $batch->productionPlan->menu->version }})
                                </span>
                            </div>
                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-lg border {{ $batch->isOnHold() ? 'bg-white text-[#9B3232] border-[#F5C2C2]' : 'bg-[#FAF4EB] text-[#885D1C] border-[#E8D7BE]' }}">
                                {{ $batch->status->label() }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="p-2 rounded-lg bg-white border border-[#DCE5DD]">
                                <span class="text-[10px] text-[#53645B] font-bold block">Realisasi Porsi</span>
                                <span class="text-sm font-bold text-[#172923] tabular-nums">
                                    {{ number_format($batch->actual_portions ?? $batch->planned_portions) }} porsi
                                </span>
                            </div>
                            <div class="p-2 rounded-lg bg-white border border-[#DCE5DD]">
                                <span class="text-[10px] text-[#53645B] font-bold block">Target Awal</span>
                                <span class="text-sm font-bold text-[#1C6654] tabular-nums">
                                    {{ number_format($batch->planned_portions) }} porsi
                                </span>
                            </div>
                        </div>

                        <div class="text-xs text-[#53645B] flex justify-between items-center">
                            <span>Operator: {{ $batch->creator->name }}</span>
                            <span class="font-mono text-[11px]">{{ $batch->updated_at->format('H:i') }} WIB</span>
                        </div>

                        <div class="pt-2 border-t {{ $batch->isOnHold() ? 'border-[#F5C2C2]' : 'border-[#DCE5DD]' }}">
                            <a href="{{ route('nutrition.qc.show', $batch) }}" 
                               class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl {{ $batch->isOnHold() ? 'bg-[#9B3232] hover:bg-[#7D2828]' : 'bg-[#285F80] hover:bg-[#1E4861]' }} text-white text-xs font-bold shadow-xs transition-colors touch-target">
                                <span>{{ $batch->isOnHold() ? 'Buka Evaluasi Ulang (On Hold)' : 'Lakukan Uji Mutu Organoleptik' }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Bagian 2: Riwayat & Monitoring Hasil Pemeriksaan Mutu -->
    <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 subtle-shadow">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 mb-4 border-b border-[#EDF2ED]">
            <div>
                <h2 class="text-sm sm:text-base font-bold text-[#172923]">Riwayat &amp; Monitoring Seluruh Batch</h2>
                <p class="text-xs text-[#53645B] mt-0.5">Daftar ketertelusuran hasil uji mutu dan status pelepasan batch</p>
            </div>

            <!-- Date Filter Form -->
            <form method="GET" action="{{ route('nutrition.qc.index') }}" class="flex items-center gap-2">
                <input type="date" 
                       name="date" 
                       value="{{ $selectedDate }}" 
                       onchange="this.form.submit()" 
                       class="px-3 py-1.5 text-xs bg-white border border-[#DCE5DD] rounded-xl text-[#172923] font-medium focus:outline-none focus:ring-2 focus:ring-[#285F80]">
                @if ($selectedDate)
                    <a href="{{ route('nutrition.qc.index') }}" class="text-xs text-[#285F80] hover:underline">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#FAFBF9] border-b border-[#DCE5DD] text-[#53645B] text-[10px] uppercase font-bold tracking-wider">
                    <tr>
                        <th scope="col" class="py-3 px-3">Kode Batch</th>
                        <th scope="col" class="py-3 px-3">Menu</th>
                        <th scope="col" class="py-3 px-3 text-right">Target</th>
                        <th scope="col" class="py-3 px-3 text-right">Realisasi</th>
                        <th scope="col" class="py-3 px-3 text-center">Status Mutu</th>
                        <th scope="col" class="py-3 px-3">Pemeriksa QC</th>
                        <th scope="col" class="py-3 px-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EDF2ED]">
                    @forelse ($monitoringBatches as $mBatch)
                        <tr class="hover:bg-[#FAFBF9]">
                            <td class="py-3 px-3 font-mono font-bold text-[#172923]">
                                {{ $mBatch->batch_code }}
                                <span class="block text-[10px] text-[#53645B] font-normal">
                                    {{ $mBatch->productionPlan->service_date->translatedFormat('d M Y') }}
                                </span>
                            </td>
                            <td class="py-3 px-3">
                                <span class="font-semibold text-[#172923] block">{{ $mBatch->productionPlan->menu->name }}</span>
                                <span class="text-[10px] text-[#53645B]">v{{ $mBatch->productionPlan->menu->version }}</span>
                            </td>
                            <td class="py-3 px-3 text-right font-mono tabular-nums text-[#53645B]">
                                {{ number_format($mBatch->planned_portions) }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold tabular-nums text-[#172923]">
                                {{ $mBatch->actual_portions !== null ? number_format($mBatch->actual_portions) : '-' }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                @php
                                    $mBadge = match($mBatch->status->value) {
                                        'RELEASED' => 'bg-[#EDF7F2] text-[#1B573E] border-[#BCE2D0]',
                                        'ON_HOLD' => 'bg-[#FDF2F2] text-[#9B3232] border-[#F5C2C2]',
                                        'QC_CHECK' => 'bg-[#FAF4EB] text-[#885D1C] border-[#E8D7BE]',
                                        default => 'bg-[#EDF2ED] text-[#53645B] border-[#DCE5DD]'
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $mBadge }}">
                                    {{ $mBatch->status->label() }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-[#53645B]">
                                @if ($mBatch->releaser)
                                    <span class="font-semibold text-[#172923] block">{{ $mBatch->releaser->name }}</span>
                                    <span class="text-[10px] font-mono">{{ $mBatch->released_at?->format('H:i') }} WIB</span>
                                @else
                                    <span class="italic text-[#708177]">Belum disahkan</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-center">
                                <a href="{{ route('nutrition.qc.show', $mBatch) }}" 
                                   class="inline-flex items-center px-2.5 py-1 text-[11px] font-semibold text-[#285F80] hover:text-[#1E4861] bg-[#F0F5FA] border border-[#BCD4E6] rounded-lg touch-target">
                                    Buka Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-[#708177] italic">
                                Belum ada catatan batch untuk filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-shell>
