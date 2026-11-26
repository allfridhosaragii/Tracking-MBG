<x-app-shell title="Runbook Produksi Dapur">
    <div class="space-y-5">
        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <span class="eyebrow-label text-[#885D1C]">Asisten Lapangan • {{ $sppg->code }}</span>
                    @if ($currentPlan)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[11px] font-semibold bg-[#EDF7F2] text-[#21684B] border border-[#BCE2D0]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#21684B]" aria-hidden="true"></span>
                            <span>Target Resmi Difinalisasi</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[11px] font-semibold bg-[#FEF7EC] text-[#885D1C] border border-[#F7D7A3]">
                            <span>Menunggu Finalisasi</span>
                        </span>
                    @endif
                </div>
                <h1 class="page-title">Rencana Produksi &amp; Kesiapan Dapur SPPG</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-1">{{ $sppg->name }} • Target porsi dan alokasi sekolah resmi.</p>
            </div>

            <form method="GET" action="{{ route('production.dashboard') }}" class="flex items-center gap-2 shrink-0">
                <label for="prod-date" class="text-xs font-semibold text-[#53645B]">Tanggal:</label>
                <input type="date"
                       id="prod-date"
                       name="date"
                       value="{{ $selectedDate }}"
                       onchange="this.form.submit()"
                       class="min-h-[44px] px-3 py-2 text-xs bg-white border border-[#DCE5DD] rounded-xl text-[#172923] font-medium">
            </form>
        </header>

        @if ($currentPlan)
            <!-- Primary Action Hero Card -->
            <div class="luxury-surface p-5 sm:p-6 space-y-5">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 border-b border-[#E7ECE8]">
                    <div>
                        <span class="eyebrow-label text-[#1C6654] block">Menu Terjadwal ({{ $parsedDate->translatedFormat('d F Y') }})</span>
                        <h2 class="text-lg sm:text-xl font-bold text-[#172923] mt-1">
                            {{ $currentPlan->menu->name }} (Versi {{ $currentPlan->menu->version }})
                        </h2>
                        <span class="text-xs font-mono text-[#53645B] mt-0.5 block">
                            {{ $currentPlan->menu->menu_code }} • Disahkan pukul {{ $currentPlan->finalized_at?->format('H:i') }} WIB oleh {{ $currentPlan->finalizer?->name }}
                        </span>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 shrink-0">
                        <div class="inset-surface px-4 py-2.5 text-left sm:text-right">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[#53645B] block">Target Alokasi Sah</span>
                            <span class="text-xl font-bold text-[#1C6654] tabular-nums">{{ number_format($targetPortions) }} <span class="text-xs font-normal text-[#53645B]">porsi</span></span>
                        </div>

                        <a href="{{ route('production.plans.execute', $currentPlan) }}"
                           class="min-h-[48px] px-5 py-3 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs sm:text-sm font-bold inline-flex items-center justify-center gap-2 smooth-transition">
                            <span>Buka Workspace Eksekusi Dapur</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Menu Components -->
                <div>
                    <h3 class="eyebrow-label mb-2.5">Komponen Hidangan per Porsi</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 text-xs">
                        @foreach ($currentPlan->menu->items as $item)
                            <div class="inset-surface px-3.5 py-2.5 flex items-center justify-between gap-2">
                                <span class="font-medium text-[#172923]">{{ $item->item_name }}</span>
                                <span class="font-mono font-bold text-[#53645B] tabular-nums">{{ number_format($item->serving_weight_g) }}g</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <details class="sop-disclosure">
                    <summary>
                        <span>Standar Higiene Kru Dapur Sebelum Pemorsian</span>
                        <svg class="w-4 h-4 text-[#53645B] disclosure-chevron shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <div class="p-4 text-xs text-[#53645B] leading-relaxed">
                        Pastikan seluruh pelaksana dapur mengenakan celemek bersih, masker, penutup kepala, dan sarung tangan pangan steril sebelum memulai tahapan pemorsian ke wadah ompreng.
                    </div>
                </details>
            </div>

            <!-- School Allocations (Dual Render: Mobile Cards + Desktop Table) -->
            <x-card title="Alokasi Porsi per Sekolah Penerima" subtitle="Acuan jumlah pengisian wadah ompreng distribusi." badge="{{ $currentPlan->allocations->count() }} Sekolah">
                <!-- Desktop Table -->
                <div class="hidden md:block -mx-6 -mb-6 overflow-x-auto">
                    <table class="ledger-table text-xs sm:text-sm">
                        <thead>
                            <tr>
                                <th>Sekolah Penerima</th>
                                <th>Jenjang</th>
                                <th class="text-right">Data Pokok</th>
                                <th class="text-right">Target Alokasi Sah</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($currentPlan->allocations as $alloc)
                                <tr>
                                    <td>
                                        <span class="font-semibold text-[#172923] block">{{ $alloc->school->name }}</span>
                                        <span class="text-[11px] font-mono text-[#53645B]">{{ $alloc->school->school_code }}</span>
                                    </td>
                                    <td>
                                        <span class="px-2 py-0.5 text-[11px] font-semibold bg-[#FAFBF9] text-[#172923] rounded-md border border-[#DCE5DD]">
                                            {{ $alloc->school->education_level }}
                                        </span>
                                    </td>
                                    <td class="text-right font-mono tabular-nums text-[#53645B]">
                                        {{ number_format($alloc->school->registered_beneficiaries) }} siswa
                                    </td>
                                    <td class="text-right font-mono tabular-nums font-bold text-[#1C6654]">
                                        {{ number_format($alloc->allocated_portions) }} porsi
                                    </td>
                                    <td class="text-center">
                                        <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-[#EDF7F2] text-[#21684B] border border-[#BCE2D0]">
                                            Terkunci
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden divide-y divide-[#E7ECE8] -my-1">
                    @foreach ($currentPlan->allocations as $alloc)
                        <div class="py-3.5 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <span class="text-sm font-bold text-[#172923] block leading-snug">{{ $alloc->school->name }}</span>
                                <span class="text-[11px] font-mono text-[#53645B]">{{ $alloc->school->school_code }} • {{ $alloc->school->education_level }}</span>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-base font-bold text-[#1C6654] font-mono tabular-nums block">{{ number_format($alloc->allocated_portions) }}</span>
                                <span class="text-[10px] text-[#53645B]">porsi sah</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @else
            <!-- Clean Empty State (Zero Emoji Slop) -->
            <div class="luxury-surface p-8 sm:p-10 text-center max-w-lg mx-auto space-y-3 my-6">
                <div class="w-12 h-12 rounded-2xl bg-[#FEF7EC] border border-[#F7D7A3] text-[#885D1C] flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 class="text-base font-bold text-[#172923]">Belum Ada Rencana Produksi Difinalisasi</h2>
                <p class="text-xs text-[#53645B] leading-relaxed">
                    Untuk tanggal <strong>{{ $parsedDate->translatedFormat('l, d F Y') }}</strong>, Kepala SPPG belum mengesahkan target produksi dapur. Alokasi porsi akan tampil otomatis setelah difinalisasi.
                </p>
            </div>
        @endif
    </div>
</x-app-shell>
