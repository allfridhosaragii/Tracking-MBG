<x-app-shell title="Operations Briefing — Kepala SPPG">
    <!-- Header Briefing -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <x-role-badge :role="$user->role" />
                <span class="text-xs text-[#53645B]">ID SPPG: {{ $sppg->code }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">Operations Briefing — {{ $sppg->name }}</h1>
            <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">Monitoring koordinasi harian, kesiapan sekolah, dan agregasi porsi</p>
        </div>

        <div class="flex items-center gap-2 text-xs">
            <span class="px-2.5 py-1 rounded-md bg-[#EDF7F2] text-[#21684B] font-semibold border border-[#BCE2D0] flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-[#21684B]"></span>
                <span>SPPG AKTIF</span>
            </span>
            <span class="text-[#53645B] bg-white border border-[#DCE5DD] px-2.5 py-1 rounded-md">
                Kapasitas: {{ number_format($sppg->max_daily_portions) }} porsi/hari
            </span>
        </div>
    </div>

    <!-- KPI Summary Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <div class="bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#53645B] block mb-1">Total Porsi Target</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-bold text-[#172923] tabular-nums">{{ number_format($totalPortions) }}</span>
                <span class="text-xs font-medium text-[#53645B]">porsi</span>
            </div>
            <span class="text-[11px] text-[#21684B] font-medium block mt-1">3 sekolah terverifikasi</span>
        </div>

        <div class="bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#53645B] block mb-1">Sekolah Dilayani</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-bold text-[#172923] tabular-nums">{{ $schools->count() }}</span>
                <span class="text-xs font-medium text-[#53645B]">unit</span>
            </div>
            <span class="text-[11px] text-[#53645B] block mt-1">SD, SMP, SMA</span>
        </div>

        <div class="bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#53645B] block mb-1">Staf Operasional</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-bold text-[#172923] tabular-nums">{{ $staffCount }}</span>
                <span class="text-xs font-medium text-[#53645B]">orang</span>
            </div>
            <span class="text-[11px] text-[#53645B] block mt-1">Gizi, Lapangan, Kurir</span>
        </div>

        <div class="bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#53645B] block mb-1">Status Fondasi</span>
            <div class="flex items-baseline gap-2">
                <span class="text-xl sm:text-2xl font-bold text-[#1C6654]">READY</span>
            </div>
            <span class="text-[11px] text-[#53645B] block mt-1">Sprint 0 Baseline</span>
        </div>
    </div>

    <!-- School Portion Distribution Table -->
    <x-card title="Daftar Sekolah Penerima & Kuota Porsi" subtitle="Distribusi sasaran penerima manfaat terdaftar untuk layanan SPPG">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead>
                    <tr class="border-b border-[#DCE5DD] text-[#53645B] text-xs uppercase tracking-wider">
                        <th class="py-3 px-3">Kode Sekolah</th>
                        <th class="py-3 px-3">Nama Sekolah</th>
                        <th class="py-3 px-3">Jenjang</th>
                        <th class="py-3 px-3 text-right">Target Porsi</th>
                        <th class="py-3 px-3">Portal Publik</th>
                        <th class="py-3 px-3 text-center">Akses Policy</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EDF2ED]">
                    @foreach ($schools as $school)
                        <tr class="hover:bg-[#F5F7F3] transition-colors">
                            <td class="py-3 px-3 font-mono text-xs font-semibold text-[#1C6654]">{{ $school->school_code }}</td>
                            <td class="py-3 px-3 font-medium text-[#172923]">
                                {{ $school->name }}
                                <span class="block text-[11px] text-[#53645B] truncate max-w-xs">{{ $school->address }}</span>
                            </td>
                            <td class="py-3 px-3">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-[#EDF2ED] text-[#172923] border border-[#DCE5DD]">
                                    {{ $school->education_level }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-right font-bold text-[#172923] tabular-nums">
                                {{ number_format($school->registered_beneficiaries) }}
                            </td>
                            <td class="py-3 px-3">
                                <a href="{{ route('public.schools.show', $school->public_slug) }}" 
                                   target="_blank" 
                                   class="text-[#1C6654] hover:underline text-xs font-medium inline-flex items-center gap-1">
                                    <span>Lihat Publik</span>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <a href="{{ route('schools.show', $school->id) }}" 
                                   class="px-2.5 py-1 rounded bg-[#EDF5F2] hover:bg-[#DCEFE7] text-[#1C6654] font-medium text-xs border border-[#BCE2D0] transition-colors inline-block">
                                    Detail Internal
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-[#DCE5DD] font-bold text-[#172923] bg-[#F5F7F3]">
                        <td colspan="3" class="py-3 px-3 text-right">TOTAL PORSI AGREGAT:</td>
                        <td class="py-3 px-3 text-right tabular-nums text-base text-[#1C6654]">{{ number_format($totalPortions) }}</td>
                        <td colspan="2" class="py-3 px-3 text-xs text-[#53645B] font-normal">Sesuai Kontrak Simulasi (1.026 porsi)</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-card>
</x-app-shell>
