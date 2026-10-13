<x-app-shell title="Production Runbook : Asisten Lapangan">
    <!-- Header Asisten Lapangan -->
    <header class="mb-6 pb-4 border-b border-[#DCE5DD]">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <x-role-badge :role="$user->role" />
                    <span class="text-xs font-mono text-[#53645B] bg-white border border-[#DCE5DD] px-2 py-0.5 rounded">
                        Dapur: {{ $sppg->code }}
                    </span>
                    @if ($currentPlan)
                        <span class="text-xs text-[#1B573E] font-semibold bg-[#EDF7F2] border border-[#BCE2D0] px-2 py-0.5 rounded flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#12B76A]" aria-hidden="true"></span>
                            <span>Target Resmi Difinalisasi</span>
                        </span>
                    @else
                        <span class="text-xs text-[#885D1C] font-semibold bg-[#FAF4EB] border border-[#E8D7BE] px-2 py-0.5 rounded flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#885D1C]" aria-hidden="true"></span>
                            <span>Menunggu Finalisasi Kepala SPPG</span>
                        </span>
                    @endif
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">
                    Rencana Produksi &amp; Kesiapan Dapur SPPG
                </h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">
                    Unit Pelayanan: <strong>{{ $sppg->name }}</strong> : Target produksi dan alokasi sekolah yang telah disahkan secara resmi.
                </p>
            </div>

            <!-- Date and Target Badge -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                <form method="GET" action="{{ route('production.dashboard') }}" class="flex items-center gap-2">
                    <input type="date" 
                           name="date" 
                           value="{{ $selectedDate }}" 
                           onchange="this.form.submit()" 
                           class="px-2.5 py-1.5 text-xs bg-white border border-[#DCE5DD] rounded-xl text-[#172923] font-medium focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                </form>
                <span class="px-3 py-2 rounded-xl bg-white border border-[#DCE5DD] text-xs font-medium text-[#172923] subtle-shadow">
                    Target Alokasi Sah: <strong class="tabular-nums font-bold text-[#1C6654]">{{ number_format($targetPortions) }}</strong> porsi
                </span>
            </div>
        </div>
    </header>

    @if ($currentPlan)
        <!-- FINALIZED PLAN BANNER & DETAILS -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
            <!-- Left 2 Cols: Menu & Operational Readiness -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-[#DCE5DD] p-4 sm:p-6 subtle-shadow space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-[#EDF2ED]">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#1C6654]">Menu Terjadwal</span>
                        <h2 class="text-base sm:text-lg font-bold text-[#172923] mt-0.5">
                            {{ $currentPlan->menu->name }} (Versi {{ $currentPlan->menu->version }})
                        </h2>
                        <span class="text-xs font-mono text-[#53645B]">Kode: {{ $currentPlan->menu->menu_code }}</span>
                    </div>
                    <span class="text-xs font-mono font-bold text-[#1B573E] bg-[#EDF7F2] px-2.5 py-1 rounded-lg border border-[#BCE2D0]">
                        Difinalisasi {{ $currentPlan->finalized_at?->format('H:i') }} WIB
                    </span>
                </div>

                <!-- Menu Item Components Breakdown -->
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#53645B] mb-2">Komponen Hidangan per Porsi</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                        @foreach ($currentPlan->menu->items as $item)
                            <div class="p-2.5 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] flex items-center justify-between">
                                <span class="font-medium text-[#172923]">{{ $item->item_name }}</span>
                                <span class="font-mono text-[#53645B] font-bold">{{ number_format($item->serving_weight_g) }}g</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Readiness Notice & PBI-06 Action Button -->
                <div class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-xs text-[#1B573E] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 shrink-0 mt-0.5 text-[#1C6654]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="leading-relaxed">
                            <p class="font-bold">Rencana Resmi Telah Diterima</p>
                            <p class="text-[11px] mt-0.5">
                                Target produksi sejumlah <strong>{{ number_format($targetPortions) }} porsi</strong> telah disahkan oleh Kepala SPPG ({{ $currentPlan->finalizer?->name }}).
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('production.plans.execute', $currentPlan) }}" 
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-[#1C6654] hover:bg-[#154D3F] text-white text-xs font-bold shadow-xs transition-colors shrink-0 touch-target">
                        <span>Buka Workspace Eksekusi Dapur</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Right 1 Col: Summary & Hygiene Protocol -->
            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-4 sm:p-6 subtle-shadow flex flex-col justify-between">
                <div>
                    <div class="pb-3 mb-3 border-b border-[#EDF2ED]">
                        <h2 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-[#172923]">Ringkasan Produksi Dapur</h2>
                        <p class="text-xs text-[#53645B] mt-0.5">Target sah untuk {{ $parsedDate->translatedFormat('d F Y') }}</p>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="p-3 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD]">
                            <span class="text-[10px] uppercase font-bold text-[#53645B] block">Total Target Porsi</span>
                            <span class="text-2xl font-bold text-[#1C6654] tabular-nums block mt-0.5">{{ number_format($targetPortions) }} <span class="text-xs font-normal text-[#53645B]">porsi</span></span>
                            <span class="text-[11px] text-[#53645B] block mt-1">Terdistribusi ke {{ $currentPlan->allocations->count() }} titik sekolah</span>
                        </div>

                        <div class="p-3 rounded-xl bg-[#FAF4EB] border border-[#E8D7BE] text-[#885D1C]">
                            <span class="text-[10px] uppercase font-bold block mb-1">Standar Higiene Dapur</span>
                            <p class="text-[11px] leading-relaxed">
                                Pastikan celemek, masker, penutup kepala, dan sarung tangan steril dikenakan oleh seluruh kru dapur sebelum proses porsi dimulai.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="pt-3 mt-3 border-t border-[#EDF2ED] text-[11px] text-[#53645B]">
                    Status: Siap untuk tahapan persiapan bahan dan pemorsian.
                </div>
            </div>
        </div>

        <!-- School Delivery Allocations Table (Read-Only) -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-4 sm:p-6 subtle-shadow">
            <div class="pb-3 mb-4 border-b border-[#EDF2ED] flex items-center justify-between">
                <div>
                    <h2 class="text-sm sm:text-base font-bold text-[#172923]">Alokasi Porsi per Sekolah Penerima</h2>
                    <p class="text-xs text-[#53645B] mt-0.5">Target porsi resmi untuk pengisian wadah ompreng distribusi</p>
                </div>
                <span class="text-xs font-mono font-bold text-[#1C6654] bg-[#EDF7F2] px-2.5 py-1 rounded border border-[#BCE2D0]">
                    {{ $currentPlan->allocations->count() }} Sekolah
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F7F3] border-b border-[#DCE5DD] text-[#53645B] uppercase text-[10px] tracking-wider">
                        <tr>
                            <th scope="col" class="py-3 px-4 font-bold">Sekolah Penerima</th>
                            <th scope="col" class="py-3 px-3 font-bold">Jenjang</th>
                            <th scope="col" class="py-3 px-3 font-bold text-right">Data Pokok</th>
                            <th scope="col" class="py-3 px-4 font-bold text-right">Target Alokasi Sah</th>
                            <th scope="col" class="py-3 px-4 font-bold text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DCE5DD]">
                        @foreach ($currentPlan->allocations as $alloc)
                            <tr class="hover:bg-[#FAFBF9] transition-colors">
                                <td class="py-3 px-4">
                                    <span class="font-bold text-[#172923] block">{{ $alloc->school->name }}</span>
                                    <span class="text-[10px] text-[#53645B] font-mono block">NPSN: {{ $alloc->school->school_code }}</span>
                                </td>
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 text-[10px] font-bold bg-[#EDF2ED] text-[#172923] rounded border border-[#DCE5DD]">
                                        {{ $alloc->school->education_level }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right font-mono tabular-nums text-[#53645B]">
                                    {{ number_format($alloc->school->registered_beneficiaries) }} anak
                                </td>
                                <td class="py-3 px-4 text-right font-mono tabular-nums font-extrabold text-sm text-[#1C6654]">
                                    {{ number_format($alloc->allocated_portions) }} porsi
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#EDF7F2] text-[#1B573E] border border-[#BCE2D0]">
                                        Terkunci
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <!-- EMPTY STATE: Belum ada rencana difinalisasi -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-8 text-center max-w-lg mx-auto space-y-3 my-8 subtle-shadow">
            <div class="w-12 h-12 rounded-full bg-[#FAF4EB] border border-[#E8D7BE] text-[#885D1C] flex items-center justify-center mx-auto text-xl">
                ⏳
            </div>
            <h2 class="text-base font-bold text-[#172923]">Belum Ada Rencana Produksi Difinalisasi</h2>
            <p class="text-xs text-[#53645B] leading-relaxed">
                Untuk tanggal <strong>{{ $parsedDate->translatedFormat('l, d F Y') }}</strong>, Kepala SPPG belum memfinalisasi target rencana produksi dapur. Target produksi dan alokasi sekolah akan tampil di halaman ini secara otomatis segera setelah disahkan.
            </p>
        </div>
    @endif
</x-app-shell>
