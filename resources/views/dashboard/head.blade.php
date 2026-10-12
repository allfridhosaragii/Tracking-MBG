<x-app-shell title="Operations Briefing — Kepala SPPG">
    <!-- Header Briefing: Control Center Status Strip -->
    <header class="mb-6 pb-4 border-b border-[#DCE5DD]">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                    <x-role-badge :role="$user->role" />
                    <span class="text-xs font-mono text-[#53645B] bg-white border border-[#DCE5DD] px-2 py-0.5 rounded">
                        SPPG: {{ $sppg->code }}
                    </span>
                    <span class="text-xs text-[#21684B] font-medium bg-[#EDF7F2] border border-[#BCE2D0] px-2 py-0.5 rounded flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#21684B]" aria-hidden="true"></span>
                        <span>Fasilitas Aktif</span>
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">Operations Briefing — {{ $sppg->name }}</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">Pusat kendali operasional, kesiapan sekolah binaan, dan agregasi data pokok</p>
            </div>

            <!-- Primary Action & Capacity Context -->
            <div class="flex flex-wrap items-center gap-2.5">
                <div class="bg-white border border-[#DCE5DD] rounded-xl px-3.5 py-2 text-right subtle-shadow hidden sm:block">
                    <span class="text-[10px] uppercase font-bold text-[#53645B] block">Kapasitas Maksimal</span>
                    <span class="text-sm font-bold text-[#172923] tabular-nums">{{ number_format($sppg->max_daily_portions) }} <span class="text-xs font-normal text-[#53645B]">porsi/hari</span></span>
                </div>

                <a href="{{ route('head.schools.index') }}" 
                   class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs sm:text-sm font-semibold text-white bg-[#1C6654] hover:bg-[#164D42] rounded-xl shadow-xs smooth-transition touch-target focus:outline-none focus:ring-2 focus:ring-[#B78C56]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span>Kelola Sekolah &amp; PIC</span>
                </a>
            </div>
        </div>
    </header>

    <!-- First-Fold Focal Point: Operational Readiness & Exception Rail -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <!-- Panel 1: Aggregated Master Data vs SPPG Capacity -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-[#DCE5DD] p-4 sm:p-5 subtle-shadow">
            <div class="flex items-center justify-between pb-3 mb-3 border-b border-[#EDF2ED]">
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-[#172923]">Agregasi Data Pokok Siswa Terdaftar</h2>
                    <p class="text-[11px] text-[#53645B] mt-0.5">Master data siswa binaan SPPG (Bukan transaksi harian PIC)</p>
                </div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded bg-[#EDF2ED] text-[#1C6654] border border-[#DCE5DD]">
                    {{ $schools->count() }} Sekolah Aktif
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                <div>
                    <span class="text-[11px] font-semibold text-[#53645B] uppercase block">Total Siswa Terdaftar</span>
                    <div class="flex items-baseline gap-2 mt-0.5">
                        <span class="text-3xl sm:text-4xl font-bold text-[#172923] tabular-nums">{{ number_format($totalPortions) }}</span>
                        <span class="text-xs font-medium text-[#53645B]">anak</span>
                    </div>
                    <span class="text-[11px] text-[#21684B] font-medium block mt-1">
                        Memenuhi {{ number_format(($totalPortions / max($sppg->max_daily_portions, 1)) * 100, 1) }}% batas kapasitas SPPG
                    </span>
                </div>

                <!-- Capacity Utilization Meter -->
                <div class="bg-[#FAFBF9] rounded-xl border border-[#DCE5DD] p-3 text-xs space-y-2">
                    <div class="flex justify-between text-[#53645B] text-[11px]">
                        <span>Beban Master Data</span>
                        <span class="font-bold text-[#172923] tabular-nums">{{ number_format($totalPortions) }} / {{ number_format($sppg->max_daily_portions) }}</span>
                    </div>
                    <div class="w-full bg-[#DCE5DD] h-2 rounded-full overflow-hidden" role="progressbar" aria-valuenow="{{ $totalPortions }}" aria-valuemin="0" aria-valuemax="{{ $sppg->max_daily_portions }}">
                        <div class="bg-[#1C6654] h-full rounded-full smooth-transition" style="width: {{ min(100, ($totalPortions / max($sppg->max_daily_portions, 1)) * 100) }}%"></div>
                    </div>
                    <p class="text-[10px] text-[#53645B] leading-tight">
                        Kebutuhan riil porsi dimutakhirkan setiap pagi oleh masing-masing PIC Sekolah sebelum batas cutoff dapur.
                    </p>
                </div>
            </div>
        </div>

        <!-- Panel 2: Operational Exception Rail & Staffing -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-4 sm:p-5 subtle-shadow flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-[#EDF2ED]">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-[#172923]">Kesiapan Staf SPPG</h2>
                    <span class="w-2 h-2 rounded-full bg-[#21684B]" aria-hidden="true"></span>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[#53645B]">Staf Bertugas Aktif:</span>
                        <span class="font-bold text-[#172923] tabular-nums text-sm">{{ $staffCount }} orang</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[#53645B]">Status Alur Kerja:</span>
                        <span class="font-semibold text-[#1C6654]">Siap Distribusi</span>
                    </div>
                </div>
            </div>

            <div class="pt-3 mt-3 border-t border-[#EDF2ED]">
                <p class="text-[11px] text-[#53645B] leading-relaxed">
                    Sistem mendeteksi seluruh sekolah binaan memiliki PIC yang terdaftar dan akun operasional dalam kondisi aktif.
                </p>
            </div>
        </div>
    </div>

    <!-- Operational Ledger: Daftar Sekolah Binaan -->
    <x-card title="Ledger Sekolah Binaan &amp; Data Pokok Penerima" subtitle="Distribusi sekolah dalam jangkauan pelayanan SPPG {{ $sppg->code }}">
        <x-slot:headerAction>
            <a href="{{ route('head.schools.index') }}" 
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-[#1C6654] bg-[#EDF2ED] hover:bg-[#E2EBE2] border border-[#DCE5DD] rounded-xl smooth-transition touch-target">
                <span>Buka Manajemen Sekolah</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </x-slot:headerAction>

        <!-- Desktop Ledger Table -->
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead>
                    <tr class="border-b border-[#DCE5DD] bg-[#FAFBF9] text-[#53645B] text-xs uppercase tracking-wider">
                        <th class="py-3 px-3">Kode / NPSN</th>
                        <th class="py-3 px-3">Nama Sekolah &amp; Alamat</th>
                        <th class="py-3 px-3">Jenjang</th>
                        <th class="py-3 px-3 text-right">Data Siswa Pokok</th>
                        <th class="py-3 px-3">Portal Transparansi</th>
                        <th class="py-3 px-3 text-center">Akses Policy</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EDF2ED]">
                    @foreach ($schools as $school)
                        <tr class="hover:bg-[#F5F7F3] smooth-transition">
                            <td class="py-3 px-3 font-mono text-xs font-bold text-[#1C6654]">
                                {{ $school->school_code }}
                            </td>
                            <td class="py-3 px-3">
                                <a href="{{ route('schools.show', $school) }}" class="font-bold text-[#172923] hover:underline block leading-tight">
                                    {{ $school->name }}
                                </a>
                                <span class="block text-[11px] text-[#53645B] truncate max-w-xs mt-0.5">{{ $school->address }}</span>
                            </td>
                            <td class="py-3 px-3">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-[#EDF2ED] text-[#172923] border border-[#DCE5DD]">
                                    {{ $school->education_level }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-right font-bold text-[#172923] tabular-nums">
                                {{ number_format($school->registered_beneficiaries) }} <span class="text-xs font-normal text-[#53645B]">anak</span>
                            </td>
                            <td class="py-3 px-3">
                                <a href="{{ route('public.schools.show', $school->public_slug) }}" 
                                   target="_blank" 
                                   class="text-[#1C6654] hover:underline text-xs font-semibold inline-flex items-center gap-1 focus:outline-none focus:ring-1 focus:ring-[#1C6654] rounded">
                                    <span>Portal Publik</span>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                </a>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-[#EDF7F2] text-[#21684B] border border-[#BCE2D0]">
                                    TERISOLASI
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile Stacked Card View (390px Optimized) -->
        <div class="sm:hidden divide-y divide-[#EDF2ED]">
            @foreach ($schools as $school)
                <div class="py-3.5 first:pt-0 last:pb-0 space-y-2">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="font-mono text-[11px] font-bold text-[#1C6654] block">{{ $school->school_code }}</span>
                            <a href="{{ route('schools.show', $school) }}" class="text-sm font-bold text-[#172923] hover:underline block leading-tight">
                                {{ $school->name }}
                            </a>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#EDF2ED] text-[#172923] border border-[#DCE5DD]">
                            {{ $school->education_level }}
                        </span>
                    </div>

                    <p class="text-xs text-[#53645B] line-clamp-1">{{ $school->address }}</p>

                    <div class="flex items-center justify-between text-xs bg-[#FAFBF9] p-2.5 rounded-xl border border-[#DCE5DD]">
                        <div>
                            <span class="text-[10px] text-[#53645B] block uppercase font-medium">Data Siswa Pokok</span>
                            <span class="font-bold text-[#172923] tabular-nums">{{ number_format($school->registered_beneficiaries) }} anak</span>
                        </div>
                        <div class="text-right">
                            <a href="{{ route('public.schools.show', $school->public_slug) }}" 
                               target="_blank" 
                               class="text-xs font-semibold text-[#1C6654] hover:underline inline-flex items-center gap-1">
                                <span>Lihat Publik</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-card>
</x-app-shell>
