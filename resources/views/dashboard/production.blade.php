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
                    <span class="text-xs text-[#885D1C] font-semibold bg-[#FAF4EB] border border-[#E8D7BE] px-2 py-0.5 rounded flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#885D1C]" aria-hidden="true"></span>
                        <span>Shift Masak Aktif</span>
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">Runbook Produksi &amp; Dapur SPPG</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">Eksekusi tahapan pengolahan, pemantauan alokasi porsi, dan pemuatan logistik</p>
            </div>

            <div class="flex items-center gap-2">
                <span class="px-3 py-2 rounded-xl bg-white border border-[#DCE5DD] text-xs font-medium text-[#172923] subtle-shadow">
                    Target Alokasi: <strong class="tabular-nums font-bold text-[#1C6654]">{{ number_format($targetPortions) }}</strong> porsi
                </span>
            </div>
        </div>
    </header>

    <!-- Focal Point: Production Runbook Stage Timeline (Hands-busy layout) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
        <!-- Panel 1: Tahapan Produksi Dapur (Runbook Timeline) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-[#DCE5DD] p-4 sm:p-6 subtle-shadow space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-[#EDF2ED]">
                <div>
                    <h2 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-[#172923]">Tahapan Alur Kerja Dapur Hari Ini</h2>
                    <p class="text-xs text-[#53645B] mt-0.5">Fokus langkah aktif: selesaikan satu tahap sebelum melangkah ke tahap berikutnya</p>
                </div>
                <span class="text-xs font-mono text-[#53645B] bg-[#EDF2ED] px-2 py-1 rounded border border-[#DCE5DD]">
                    05.00 - 09.30 WIB
                </span>
            </div>

            <!-- Segmented Journey-Line for Kitchen Production -->
            <div class="space-y-3">
                <!-- Stage 1: Preparation (Completed/Ready) -->
                <div class="p-3.5 rounded-xl border border-[#BCE2D0] bg-[#EDF7F2] flex items-start gap-3">
                    <div class="w-6 h-6 rounded-full bg-[#21684B] text-white flex items-center justify-center text-xs font-bold shrink-0 mt-0.5" aria-hidden="true">
                        ✓
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs sm:text-sm font-bold text-[#172923]">1. Penerimaan Bahan Baku &amp; Persiapan</h3>
                            <span class="text-[10px] font-bold text-[#21684B] bg-white px-2 py-0.5 rounded border border-[#BCE2D0]">SELESAI</span>
                        </div>
                        <p class="text-xs text-[#53645B] mt-0.5">Penimbangan bahan segar, pencucian beras, dan pemotongan sayur/lauk.</p>
                    </div>
                </div>

                <!-- Stage 2: Cooking (Active Focus) -->
                <div class="p-3.5 rounded-xl border-2 border-[#1C6654] bg-white flex items-start gap-3 shadow-xs">
                    <div class="w-6 h-6 rounded-full bg-[#1C6654] text-white flex items-center justify-center text-xs font-bold shrink-0 mt-0.5" aria-hidden="true">
                        2
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs sm:text-sm font-bold text-[#172923]">2. Pengolahan &amp; Memasak Porsi Utama</h3>
                            <span class="text-[10px] font-bold text-white bg-[#1C6654] px-2 py-0.5 rounded">FOKUS AKTIF</span>
                        </div>
                        <p class="text-xs text-[#53645B] mt-0.5">Pemasakan skala besar dengan kontrol suhu inti (>75°C) untuk keamanan pangan.</p>
                        <div class="mt-2.5 pt-2 border-t border-[#EDF2ED] flex items-center justify-between text-xs">
                            <span class="text-[#53645B]">Status Batch:</span>
                            <span class="font-bold text-[#172923] tabular-nums">Batch 01 &amp; 02 dalam penggorengan</span>
                        </div>
                    </div>
                </div>

                <!-- Stage 3: Packaging (Next Step) -->
                <div class="p-3.5 rounded-xl border border-[#DCE5DD] bg-[#FAFBF9] flex items-start gap-3 opacity-80">
                    <div class="w-6 h-6 rounded-full bg-[#EDF2ED] text-[#53645B] border border-[#DCE5DD] flex items-center justify-center text-xs font-bold shrink-0 mt-0.5" aria-hidden="true">
                        3
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs sm:text-sm font-bold text-[#172923]">3. Porsi &amp; Pengemasan Wadah Ompreng</h3>
                            <span class="text-[10px] font-semibold text-[#53645B] bg-[#EDF2ED] px-2 py-0.5 rounded">MENUNGGU</span>
                        </div>
                        <p class="text-xs text-[#53645B] mt-0.5">Penataan porsi per ompreng bersegel sebelum diserahkan ke QC Gizi.</p>
                    </div>
                </div>

                <!-- Stage 4: QC Handover -->
                <div class="p-3.5 rounded-xl border border-[#DCE5DD] bg-[#FAFBF9] flex items-start gap-3 opacity-80">
                    <div class="w-6 h-6 rounded-full bg-[#EDF2ED] text-[#53645B] border border-[#DCE5DD] flex items-center justify-center text-xs font-bold shrink-0 mt-0.5" aria-hidden="true">
                        4
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs sm:text-sm font-bold text-[#172923]">4. Verifikasi Gizi &amp; Pemuatan Kendaraan</h3>
                            <span class="text-[10px] font-semibold text-[#53645B] bg-[#EDF2ED] px-2 py-0.5 rounded">TERKUNCI</span>
                        </div>
                        <p class="text-xs text-[#53645B] mt-0.5">Pemeriksaan organoleptik oleh Pengawas Gizi sebelum diserahkan kepada Kurir.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel 2: Production Summary & Hygiene Notes -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-4 sm:p-6 subtle-shadow flex flex-col justify-between">
            <div>
                <div class="pb-3 mb-3 border-b border-[#EDF2ED]">
                    <h2 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-[#172923]">Ringkasan Alokasi Hari Ini</h2>
                    <p class="text-xs text-[#53645B] mt-0.5">Total porsi pokok sekolah binaan</p>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="p-3 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD]">
                        <span class="text-[10px] uppercase font-bold text-[#53645B] block">Beban Porsi Master</span>
                        <span class="text-2xl font-bold text-[#172923] tabular-nums block mt-0.5">{{ number_format($targetPortions) }} <span class="text-xs font-normal text-[#53645B]">porsi</span></span>
                        <span class="text-[11px] text-[#21684B] font-medium block mt-1">Terbagi dalam 3 titik pengantaran</span>
                    </div>

                    <div class="p-3 rounded-xl bg-[#FAF4EB] border border-[#E8D7BE] text-[#885D1C]">
                        <span class="text-[10px] uppercase font-bold block mb-1">Catatan Higiene Dapur</span>
                        <p class="text-[11px] leading-relaxed">
                            Pastikan masker, sarung tangan steril, dan penutup kepala terpasang selama proses penataan wadah ompreng.
                        </p>
                    </div>
                </div>
            </div>

            <div class="pt-3 mt-3 border-t border-[#EDF2ED] text-[11px] text-[#53645B]">
                Koordinasi teknis dapur terhubung langsung dengan Pengawas Gizi SPPG.
            </div>
        </div>
    </div>

    <!-- School Delivery Allocations -->
    <x-card title="Alokasi Pengiriman Sekolah Binaan" subtitle="Rincian target porsi per sekolah untuk persiapan pemuatan wadah ompreng">
        <div class="divide-y divide-[#EDF2ED]">
            @foreach ($schools as $school)
                <div class="py-3.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 first:pt-0 last:pb-0">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sm text-[#172923]">{{ $school->name }}</span>
                            <span class="px-2 py-0.5 text-[10px] font-bold bg-[#EDF2ED] text-[#172923] rounded border border-[#DCE5DD]">
                                {{ $school->education_level }}
                            </span>
                        </div>
                        <span class="text-xs text-[#53645B] block mt-0.5">{{ $school->address }}</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <span class="text-[10px] uppercase font-bold text-[#53645B] block">Kebutuhan Pokok</span>
                            <span class="text-sm font-bold text-[#172923] tabular-nums">{{ number_format($school->registered_beneficiaries) }} ompreng</span>
                        </div>
                        <a href="{{ route('schools.show', $school) }}" 
                           class="px-3 py-1.5 text-xs font-semibold text-[#1C6654] bg-[#EDF2ED] hover:bg-[#E2EBE2] border border-[#DCE5DD] rounded-xl smooth-transition touch-target flex items-center gap-1">
                            <span>Detail</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </x-card>
</x-app-shell>
