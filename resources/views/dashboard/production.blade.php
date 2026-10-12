<x-app-shell title="Production Runbook — Asisten Lapangan">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <x-role-badge :role="$user->role" />
                <span class="text-xs text-[#53645B]">{{ $sppg->name }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">Production Runbook — Dapur SPPG</h1>
            <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">Panduan tahapan produksi fisik, kontrol batch, dan pencatatan porsi nyata</p>
        </div>

        <span class="px-2.5 py-1 rounded-md bg-[#FAF4EB] text-[#885D1C] font-semibold text-xs border border-[#E8D7BE] flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-[#885D1C]"></span>
            <span>OPERASIONAL DAPUR</span>
        </span>
    </div>

    <!-- Production Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-card title="Target Produksi Hari Ini">
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-[#172923] tabular-nums">{{ number_format($targetPortions) }}</span>
                <span class="text-xs text-[#53645B]">porsi</span>
            </div>
            <p class="text-xs text-[#21684B] font-medium mt-1">Alokasi untuk 3 sekolah</p>
        </x-card>

        <x-card title="Tahapan Standar">
            <ol class="text-xs text-[#53645B] space-y-1 list-decimal list-inside">
                <li>Persiapan Bahan Baku</li>
                <li>Proses Memasak Utama</li>
                <li>Porsi & Kemasan Ompreng</li>
                <li>QC Hold & Release Gizi</li>
            </ol>
        </x-card>

        <x-card title="Integritas Data Lapangan">
            <p class="text-xs text-[#53645B] leading-relaxed">
                Jumlah porsi aktual (<span class="font-mono text-[#172923]">actual_portions</span>) dicatat saat kemasan selesai dan menjadi batas maksimum muatan kurir.
            </p>
        </x-card>
    </div>

    <!-- Runbook Table -->
    <x-card title="Rincian Alokasi Per Sekolah" subtitle="Data sasaran persiapan ompreng per titik antar">
        <div class="divide-y divide-[#EDF2ED]">
            @foreach ($schools as $school)
                <div class="py-3 flex items-center justify-between gap-4">
                    <div>
                        <span class="font-bold text-[#172923] text-sm block">{{ $school->name }}</span>
                        <span class="text-xs text-[#53645B]">{{ $school->address }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-bold text-[#1C6654] tabular-nums">{{ number_format($school->registered_beneficiaries) }} porsi</span>
                        <span class="text-[11px] text-[#53645B] block">Siap Kemas</span>
                    </div>
                </div>
            @endforeach
        </div>
    </x-card>
</x-app-shell>
