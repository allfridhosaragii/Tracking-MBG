<x-app-shell title="Briefing Operasional">
    <div class="space-y-6">
        <!-- Minimalist First-Fold Header -->
        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 pb-5 border-b border-[#DCE5DD]">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider bg-[#EDF7F2] text-[#1C6654] border border-[#BCE2D0]">SPPG Active Unit</span>
                    <span class="text-xs font-mono text-[#53645B]">{{ $sppg->code }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold text-[#172923] tracking-tight">{{ $sppg->name }}</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-1 max-w-xl">Pusat kendali operasional harian, pemantauan kapasitas porsi, dan koordinasi distribusi sekolah binaan.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <a href="{{ route('head.production.index') }}"
                   class="min-h-[44px] px-4 py-2.5 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs sm:text-sm font-semibold inline-flex items-center justify-center gap-2 smooth-transition shadow-sm">
                    <span>Rencana Produksi</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                <a href="{{ route('head.distribution.index') }}"
                   class="min-h-[44px] px-4 py-2.5 rounded-xl bg-white hover:bg-[#FAFBF9] text-[#172923] border border-[#DCE5DD] text-xs sm:text-sm font-semibold inline-flex items-center justify-center smooth-transition">
                    Kontrol Distribusi
                </a>
            </div>
        </header>

        <!-- Operational Pulse Strip (2x2 on Mobile, 4 Columns on Desktop) -->
        <section aria-label="Indikator Utama SPPG" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- 1. Master Beneficiaries vs Capacity -->
            <div class="luxury-surface p-4 sm:p-5 col-span-2 sm:col-span-1">
                <span class="eyebrow-label block">Data Siswa Pokok</span>
                <div class="mt-2 flex items-baseline gap-1.5">
                    <span class="text-2xl sm:text-3xl font-bold text-[#172923] tabular-nums">{{ number_format($totalPortions) }}</span>
                    <span class="text-xs text-[#53645B]">/ {{ number_format($sppg->max_daily_portions) }}</span>
                </div>
                <div class="w-full bg-[#EDF2ED] h-1.5 rounded-full overflow-hidden mt-2.5" role="progressbar" aria-valuenow="{{ $totalPortions }}" aria-valuemin="0" aria-valuemax="{{ $sppg->max_daily_portions }}">
                    <div class="bg-[#1C6654] h-full rounded-full" style="width: {{ min(100, ($totalPortions / max($sppg->max_daily_portions, 1)) * 100) }}%"></div>
                </div>
                <span class="text-[11px] text-[#53645B] block mt-1.5">{{ $schools->count() }} sekolah aktif terdaftar</span>
            </div>

            <!-- 2. Today Production Plan -->
            <a href="{{ route('head.production.index') }}" class="luxury-surface p-4 sm:p-5 hover:border-[#1C6654] smooth-transition block">
                <span class="eyebrow-label block">Produksi Hari Ini</span>
                @if (isset($todayPlan) && $todayPlan)
                    <div class="mt-2 text-lg sm:text-xl font-bold text-[#1C6654] leading-snug">
                        {{ $todayPlan->status->label() }}
                    </div>
                    <span class="text-[11px] text-[#53645B] block mt-1 truncate">{{ $todayPlan->menu?->name ?? 'Menu terjadwal' }}</span>
                @else
                    <div class="mt-2 text-base sm:text-lg font-bold text-[#885D1C] leading-snug">
                        Belum Dibuat
                    </div>
                    <span class="text-[11px] text-[#53645B] block mt-1">Ketuk untuk susun rencana</span>
                @endif
            </a>

            <!-- 3. Today Fleet Dispatch -->
            <a href="{{ route('head.distribution.index') }}" class="luxury-surface p-4 sm:p-5 hover:border-[#1C6654] smooth-transition block">
                <span class="eyebrow-label block">Rute Hari Ini</span>
                <div class="mt-2 flex items-baseline gap-1.5">
                    <span class="text-2xl sm:text-3xl font-bold text-[#172923] tabular-nums">{{ $todayTripsCount ?? 0 }}</span>
                    <span class="text-xs text-[#53645B]">trip</span>
                </div>
                <span class="text-[11px] text-[#53645B] block mt-1.5">Penjadwalan armada &amp; kurir</span>
            </a>

            <!-- 4. Staffing & Incidents -->
            <div class="luxury-surface p-4 sm:p-5 col-span-2 sm:col-span-1 flex flex-col justify-between">
                <div>
                    <span class="eyebrow-label block">Staf &amp; Kendala</span>
                    <div class="mt-2 flex items-baseline justify-between gap-2">
                        <div>
                            <span class="text-2xl sm:text-3xl font-bold text-[#172923] tabular-nums">{{ $staffCount }}</span>
                            <span class="text-xs text-[#53645B]">staf</span>
                        </div>
                        @if (($openIncidentsCount ?? 0) > 0)
                            <a href="{{ route('incidents.index') }}" class="px-2 py-1 rounded-lg bg-[#FDF2F2] text-[#9B3232] border border-[#F5C2C2] text-[11px] font-semibold">
                                {{ $openIncidentsCount }} kendala
                            </a>
                        @else
                            <span class="px-2 py-1 rounded-lg bg-[#EDF7F2] text-[#21684B] border border-[#BCE2D0] text-[11px] font-semibold">
                                Normal
                            </span>
                        @endif
                    </div>
                </div>
                <a href="{{ route('head.employees.index') }}" class="text-[11px] font-semibold text-[#1C6654] hover:underline mt-2 inline-block">
                    Kelola direktori pegawai &rarr;
                </a>
            </div>
        </section>

        <!-- Quick Operational Shortcuts -->
        <nav aria-label="Pintasan Modul Kepala SPPG" class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
            <a href="{{ route('head.portions.index') }}" class="min-h-[48px] px-3.5 py-2.5 rounded-xl bg-white hover:bg-[#EDF7F2] border border-[#DCE5DD] hover:border-[#BCE2D0] text-xs font-semibold text-[#172923] flex items-center justify-between gap-2 smooth-transition">
                <span>Monitoring Porsi</span>
                <span class="text-[#1C6654]" aria-hidden="true">&rarr;</span>
            </a>
            <a href="{{ route('head.schools.index') }}" class="min-h-[48px] px-3.5 py-2.5 rounded-xl bg-white hover:bg-[#EDF7F2] border border-[#DCE5DD] hover:border-[#BCE2D0] text-xs font-semibold text-[#172923] flex items-center justify-between gap-2 smooth-transition">
                <span>Sekolah &amp; PIC</span>
                <span class="text-[#1C6654]" aria-hidden="true">&rarr;</span>
            </a>
            <a href="{{ route('head.employees.index') }}" class="min-h-[48px] px-3.5 py-2.5 rounded-xl bg-white hover:bg-[#EDF7F2] border border-[#DCE5DD] hover:border-[#BCE2D0] text-xs font-semibold text-[#172923] flex items-center justify-between gap-2 smooth-transition">
                <span>Pegawai &amp; Role</span>
                <span class="text-[#1C6654]" aria-hidden="true">&rarr;</span>
            </a>
            <a href="{{ route('incidents.index') }}" class="min-h-[48px] px-3.5 py-2.5 rounded-xl bg-white hover:bg-[#EDF7F2] border border-[#DCE5DD] hover:border-[#BCE2D0] text-xs font-semibold text-[#172923] flex items-center justify-between gap-2 smooth-transition">
                <span>Pusat Kendala</span>
                <span class="text-[#1C6654]" aria-hidden="true">&rarr;</span>
            </a>
        </nav>

        <!-- School Coverage Ledger -->
        <x-card title="Sekolah Binaan Aktif" subtitle="Daftar unit sekolah penerima manfaat dalam wilayah layanan {{ $sppg->code }}. Data siswa pokok di bawah merupakan acuan dasar, sedangkan porsi masak harian dikonfirmasi setiap hari oleh masing-masing PIC Sekolah.">
            <x-slot:headerAction>
                <a href="{{ route('head.schools.index') }}"
                   class="min-h-[44px] px-3.5 py-2 text-xs font-semibold text-[#1C6654] bg-[#EDF7F2] hover:bg-[#DCF0E6] border border-[#BCE2D0] rounded-xl inline-flex items-center gap-1.5 smooth-transition">
                    <span>Kelola Sekolah</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </x-slot:headerAction>

            @if ($schools->isEmpty())
                <div class="py-10 px-4 text-center">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-[#EDF7F2] text-[#1C6654] flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-[#172923]">Belum Ada Sekolah Binaan Terdaftar</h3>
                    <p class="text-xs text-[#53645B] mt-1 max-w-sm mx-auto leading-relaxed">
                        Unit {{ $sppg->code }} belum memiliki sekolah binaan. Daftarkan sekolah pertama untuk membuka penjadwalan porsi dan pengantaran.
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('head.schools.create') }}"
                           class="min-h-[44px] inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-white bg-[#1C6654] hover:bg-[#164D42] rounded-xl smooth-transition">
                            + Daftarkan Sekolah Binaan Pertama
                        </a>
                    </div>
                </div>
            @else
                <!-- Desktop Luxury Ledger -->
                <div class="hidden md:block -mx-6 -mb-6 overflow-x-auto">
                    <table class="ledger-table text-xs sm:text-sm">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Sekolah &amp; Lokasi</th>
                                <th>Jenjang</th>
                                <th>PIC Sekolah</th>
                                <th class="text-right">Siswa Pokok</th>
                                <th class="text-right">Tautan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($schools as $school)
                                @php $pic = $school->relationLoaded('picUsers') ? $school->picUsers->first() : null; @endphp
                                <tr>
                                    <td class="font-mono text-xs font-semibold text-[#1C6654]">{{ $school->school_code }}</td>
                                    <td>
                                        <a href="{{ route('head.schools.show', $school) }}" class="font-semibold text-[#172923] hover:text-[#1C6654] block">
                                            {{ $school->name }}
                                        </a>
                                        <span class="text-[11px] text-[#53645B] block truncate max-w-xs mt-0.5">{{ $school->address }}</span>
                                    </td>
                                    <td>
                                        <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-[#FAFBF9] text-[#172923] border border-[#DCE5DD]">
                                            {{ $school->education_level }}
                                        </span>
                                    </td>
                                    <td class="text-xs">
                                        @if ($pic)
                                            <span class="font-medium text-[#172923] block">{{ $pic->name }}</span>
                                            <span class="text-[11px] text-[#53645B] font-mono">{{ $pic->email }}</span>
                                        @else
                                            <span class="text-[11px] text-[#885D1C] font-medium">Belum ditugaskan</span>
                                        @endif
                                    </td>
                                    <td class="text-right font-bold text-[#172923] tabular-nums">
                                        {{ number_format($school->registered_beneficiaries) }} <span class="text-xs font-normal text-[#53645B]">siswa</span>
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        <a href="{{ route('public.schools.show', $school->public_slug) }}"
                                           target="_blank"
                                           rel="noopener"
                                           class="min-h-[44px] px-3 py-2 text-xs font-semibold text-[#1C6654] hover:bg-[#EDF7F2] rounded-xl inline-flex items-center gap-1 smooth-transition">
                                            <span>Publik</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Minimalist Card List -->
                <div class="md:hidden divide-y divide-[#E7ECE8] -my-1">
                    @foreach ($schools as $school)
                        @php $pic = $school->relationLoaded('picUsers') ? $school->picUsers->first() : null; @endphp
                        <div class="py-3.5 flex flex-col gap-2">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('head.schools.show', $school) }}" class="text-sm font-bold text-[#172923] hover:text-[#1C6654] block leading-snug">
                                        {{ $school->name }}
                                    </a>
                                    <span class="text-[11px] text-[#53645B] font-mono block mt-0.5">{{ $school->school_code }} • {{ $school->education_level }}</span>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-sm font-bold text-[#172923] tabular-nums block">{{ number_format($school->registered_beneficiaries) }}</span>
                                    <span class="text-[10px] text-[#53645B]">siswa</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-xs pt-1">
                                <span class="text-[11px] text-[#53645B] truncate max-w-[200px]">
                                    PIC: {{ $pic ? $pic->email : 'Belum ada PIC' }}
                                </span>
                                <a href="{{ route('public.schools.show', $school->public_slug) }}"
                                   target="_blank"
                                   rel="noopener"
                                   class="text-xs font-semibold text-[#1C6654] hover:underline inline-flex items-center gap-1 py-1">
                                    <span>Portal Publik</span>
                                    <span aria-hidden="true">&rarr;</span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</x-app-shell>
