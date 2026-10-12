<x-app-shell title="Review Desk — Pengawas Gizi">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <x-role-badge :role="$user->role" />
                <span class="text-xs text-[#53645B]">{{ $sppg->name }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">Review Desk — Standar Mutu & Gizi</h1>
            <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">Verifikasi komposisi nutrisi menu, evaluasi organoleptik, dan persetujuan batch</p>
        </div>

        <span class="px-2.5 py-1 rounded-md bg-[#F0F5FA] text-[#285F80] font-semibold text-xs border border-[#BCD4E6] flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-[#285F80]"></span>
            <span>SIAP REVIEW BATCH</span>
        </span>
    </div>

    <!-- Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-card title="Sasaran Manfaat" subtitle="Penerima gizi harian terdaftar">
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-[#172923] tabular-nums">{{ number_format($totalBeneficiaries) }}</span>
                <span class="text-xs text-[#53645B]">siswa penerima</span>
            </div>
            <p class="text-xs text-[#53645B] mt-2">Tersebar di 3 sekolah binaan SPPG</p>
        </x-card>

        <x-card title="Gerbang Kualitas" subtitle="Kontrak QC rujukan">
            <div class="space-y-1.5 text-xs text-[#172923]">
                <div class="flex items-center justify-between">
                    <span>1. Before Dispatch (SPPG)</span>
                    <span class="font-semibold text-[#21684B]">Wajib Gizi</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>2. On Arrival (Sekolah)</span>
                    <span class="font-semibold text-[#885D1C]">PIC + Driver</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>3. Before Consumption</span>
                    <span class="font-semibold text-[#21684B]">PIC Uji Rasa</span>
                </div>
            </div>
        </x-card>

        <x-card title="Integritas Mutu" subtitle="Prinsip Food Safety BGN">
            <p class="text-xs text-[#53645B] leading-relaxed">
                Status <span class="font-bold text-[#9B3232]">UNSAFE</span> menahan seluruh batch tanpa override sepihak. Dokumentasi dan foto organoleptik dicatat privat dan terenkripsi.
            </p>
        </x-card>
    </div>

    <!-- School Target Overview -->
    <x-card title="Daftar Sekolah Sasaran Gizi" subtitle="Kebutuhan nutrisi per jenjang pendidikan">
        <div class="divide-y divide-[#EDF2ED]">
            @foreach ($schools as $school)
                <div class="py-3 flex items-center justify-between gap-4">
                    <div>
                        <span class="font-bold text-[#172923] text-sm block">{{ $school->name }}</span>
                        <span class="text-xs text-[#53645B]">Jenjang: {{ $school->education_level }} • Kode: {{ $school->school_code }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-bold text-[#1C6654] tabular-nums">{{ number_format($school->registered_beneficiaries) }}</span>
                        <span class="text-xs text-[#53645B] block">target porsi</span>
                    </div>
                </div>
            @endforeach
        </div>
    </x-card>
</x-app-shell>
