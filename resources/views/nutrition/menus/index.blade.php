<x-app-shell title="Katalog Menu & Kandungan Gizi : MBG-Track">
    <div class="space-y-6">
        <!-- Breadcrumb & Editorial Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <div class="flex items-center gap-2 text-xs font-medium text-[#53645B] mb-1">
                    <a href="{{ route('nutrition.dashboard') }}" class="hover:text-[#172923] smooth-transition">Desk Pengawas Gizi</a>
                    <span aria-hidden="true">/</span>
                    <span class="text-[#285F80] font-semibold">Katalog Menu &amp; Gizi</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-[#172923]">
                    Katalog Racikan Menu &amp; Kandungan Gizi
                </h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-1 max-w-2xl leading-relaxed">
                    Kelola formulasi menu makanan bergizi, audit gramasi dan zat gizi per komponen, serta pastikan versi resep telah sah sebelum digunakan pada rencana produksi SPPG.
                </p>
            </div>

            <!-- Primary Action -->
            <div class="shrink-0">
                <a href="{{ route('nutrition.menus.create') }}" 
                   class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-[#285F80] hover:bg-[#1E4861] text-white text-xs sm:text-sm font-semibold rounded-xl shadow-xs smooth-transition touch-target focus:outline-none focus:ring-2 focus:ring-[#285F80] focus:ring-offset-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Racik Draft Menu Baru</span>
                </a>
            </div>
        </div>

        <!-- Operational Status Rhythm Bar (Not Generic Cards) -->
        <section aria-labelledby="status-metrics-heading" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <h2 id="status-metrics-heading" class="sr-only">Ringkasan Status Formulasi Menu</h2>

            <!-- Metric 1: Menu Aktif & Sah -->
            <div class="p-4 rounded-xl bg-white border border-[#DCE5DD] shadow-2xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-[#53645B] block">Menu Aktif (Sah Produksi)</span>
                    <span class="text-2xl font-extrabold text-[#1B573E] mt-0.5 block">{{ $activeMenusCount }}</span>
                    <span class="text-[11px] text-[#53645B] mt-0.5 block">Siap dialokasikan ke rencana dapur</span>
                </div>
                <div class="w-10 h-10 rounded-lg bg-[#EDF7F2] border border-[#BCE2D0] flex items-center justify-center text-[#1B573E] shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>

            <!-- Metric 2: Draft Dalam Penyusunan -->
            <div class="p-4 rounded-xl bg-white border border-[#DCE5DD] shadow-2xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-[#53645B] block">Draft Penyusunan</span>
                    <span class="text-2xl font-extrabold text-[#B54708] mt-0.5 block">{{ $draftMenusCount }}</span>
                    <span class="text-[11px] text-[#53645B] mt-0.5 block">Dalam proses perhitungan nutrisi</span>
                </div>
                <div class="w-10 h-10 rounded-lg bg-[#FEF6EE] border border-[#F9DBAF] flex items-center justify-center text-[#B54708] shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
            </div>

            <!-- Metric 3: Draft Belum Lengkap -->
            <div class="p-4 rounded-xl bg-white border border-[#DCE5DD] shadow-2xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-[#53645B] block">Perlu Kelengkapan</span>
                    <span class="text-2xl font-extrabold text-[#9B3232] mt-0.5 block">{{ $incompleteDraftsCount }}</span>
                    <span class="text-[11px] text-[#53645B] mt-0.5 block">Belum memenuhi syarat aktivasi</span>
                </div>
                <div class="w-10 h-10 rounded-lg bg-[#FDF2F2] border border-[#F5C2C2] flex items-center justify-center text-[#9B3232] shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </section>

        <!-- Filters & Search Toolbar -->
        <div class="p-4 rounded-xl bg-white border border-[#DCE5DD] shadow-2xs">
            <form method="GET" action="{{ route('nutrition.menus.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <!-- Status Filter Tabs -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0" role="tablist" aria-label="Filter status menu">
                    <a href="{{ route('nutrition.menus.index', ['q' => $searchQuery]) }}" 
                       class="px-3 py-1.5 text-xs font-semibold rounded-lg touch-target smooth-transition {{ $currentFilter === 'all' ? 'bg-[#285F80] text-white shadow-xs' : 'text-[#53645B] hover:text-[#172923] hover:bg-[#F5F7F3]' }}">
                        Semua
                    </a>
                    <a href="{{ route('nutrition.menus.index', ['status' => 'ACTIVE', 'q' => $searchQuery]) }}" 
                       class="px-3 py-1.5 text-xs font-semibold rounded-lg touch-target smooth-transition {{ $currentFilter === 'ACTIVE' ? 'bg-[#1B573E] text-white shadow-xs' : 'text-[#53645B] hover:text-[#172923] hover:bg-[#F5F7F3]' }}">
                        Aktif
                    </a>
                    <a href="{{ route('nutrition.menus.index', ['status' => 'DRAFT', 'q' => $searchQuery]) }}" 
                       class="px-3 py-1.5 text-xs font-semibold rounded-lg touch-target smooth-transition {{ $currentFilter === 'DRAFT' ? 'bg-[#B54708] text-white shadow-xs' : 'text-[#53645B] hover:text-[#172923] hover:bg-[#F5F7F3]' }}">
                        Draft
                    </a>
                    <a href="{{ route('nutrition.menus.index', ['status' => 'ARCHIVED', 'q' => $searchQuery]) }}" 
                       class="px-3 py-1.5 text-xs font-semibold rounded-lg touch-target smooth-transition {{ $currentFilter === 'ARCHIVED' ? 'bg-[#475467] text-white shadow-xs' : 'text-[#53645B] hover:text-[#172923] hover:bg-[#F5F7F3]' }}">
                        Arsip
                    </a>
                </div>

                <!-- Keyword Search Box -->
                <div class="flex items-center gap-2">
                    <div class="relative w-full sm:w-64">
                        <input type="text" 
                               name="q" 
                               value="{{ $searchQuery }}" 
                               placeholder="Cari nama atau kode menu..." 
                               class="w-full pl-9 pr-3 py-2 text-xs sm:text-sm bg-[#F5F7F3] border border-[#DCE5DD] rounded-xl text-[#172923] placeholder-[#708177] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#285F80] smooth-transition touch-target">
                        <svg class="w-4 h-4 text-[#708177] absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    @if($currentFilter !== 'all')
                        <input type="hidden" name="status" value="{{ $currentFilter }}">
                    @endif

                    <button type="submit" 
                            class="px-3.5 py-2 text-xs font-semibold bg-[#F5F7F3] hover:bg-[#EDF2ED] text-[#172923] border border-[#DCE5DD] rounded-xl smooth-transition touch-target">
                        Filter
                    </button>

                    @if($searchQuery || $currentFilter !== 'all')
                        <a href="{{ route('nutrition.menus.index') }}" 
                           class="px-3 py-2 text-xs font-medium text-[#708177] hover:text-[#9B3232] smooth-transition touch-target" 
                           title="Reset filter">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Menu Records Listing -->
        <div class="bg-white border border-[#DCE5DD] rounded-xl shadow-2xs overflow-hidden">
            @if ($menus->isEmpty())
                <!-- Honest Empty State -->
                <div class="py-12 px-6 text-center max-w-md mx-auto">
                    <div class="w-12 h-12 rounded-xl bg-[#EDF2ED] border border-[#DCE5DD] text-[#285F80] flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-[#172923]">Belum ada data formulasi menu</h3>
                    <p class="text-xs sm:text-sm text-[#53645B] mt-1 leading-relaxed">
                        @if($searchQuery || $currentFilter !== 'all')
                            Tidak ditemukan menu yang sesuai dengan kriteria filter saat ini. Silakan sesuaikan kata kunci pencarian Anda.
                        @else
                            SPPG Anda belum memiliki racikan menu terdaftar. Mulai susun draft menu makanan untuk kebutuhan nutrisi siswa sekolah sasaran.
                        @endif
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('nutrition.menus.create') }}" 
                           class="inline-flex items-center gap-2 px-4 py-2 bg-[#285F80] text-white text-xs font-semibold rounded-xl hover:bg-[#1E4861] smooth-transition touch-target">
                            <span>Mulai Susun Draft Menu</span>
                        </a>
                    </div>
                </div>
            @else
                <!-- Responsive Table / Card Container -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs sm:text-sm">
                        <thead>
                            <tr class="bg-[#F5F7F3] border-b border-[#DCE5DD] text-[#53645B] text-[11px] uppercase tracking-wider font-semibold">
                                <th scope="col" class="py-3 px-4">Menu &amp; Kode</th>
                                <th scope="col" class="py-3 px-3">Versi</th>
                                <th scope="col" class="py-3 px-3">Status</th>
                                <th scope="col" class="py-3 px-3">Komponen</th>
                                <th scope="col" class="py-3 px-3">Profil Gizi (Kkal / P / K / L)</th>
                                <th scope="col" class="py-3 px-3 hidden md:table-cell">Mulai Berlaku</th>
                                <th scope="col" class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#DCE5DD]">
                            @foreach ($menus as $menu)
                                @php
                                    $isComplete = $menu->isComplete();
                                    $totalEnergy = $menu->totalEnergy();
                                    $totalProtein = $menu->totalProtein();
                                    $totalCarbs = $menu->totalCarbohydrates();
                                    $totalFat = $menu->totalFat();
                                @endphp
                                <tr class="hover:bg-[#F9FAF8] smooth-transition">
                                    <!-- Menu & Code -->
                                    <td class="py-3.5 px-4">
                                        <div class="font-semibold text-[#172923] text-sm leading-snug">
                                            <a href="{{ route('nutrition.menus.show', $menu) }}" class="hover:text-[#285F80] smooth-transition">
                                                {{ $menu->name }}
                                            </a>
                                        </div>
                                        <div class="text-[11px] text-[#53645B] font-mono mt-0.5">
                                            {{ $menu->menu_code }}
                                        </div>
                                    </td>

                                    <!-- Version Badge -->
                                    <td class="py-3.5 px-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-mono font-bold bg-[#EDF2ED] text-[#285F80] border border-[#DCE5DD]">
                                            v{{ $menu->version }}
                                        </span>
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="py-3.5 px-3">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $menu->status->badgeClasses() }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $menu->status->dotClasses() }}" aria-hidden="true"></span>
                                            <span>{{ $menu->status->label() }}</span>
                                        </span>
                                    </td>

                                    <!-- Component Count -->
                                    <td class="py-3.5 px-3 text-[#172923]">
                                        <span class="font-medium">{{ $menu->items->count() }} bahan</span>
                                        <span class="text-[11px] text-[#53645B] block">{{ number_format($menu->totalServingWeight(), 0) }} g saji</span>
                                    </td>

                                    <!-- Nutrition Profile Summary -->
                                    <td class="py-3.5 px-3">
                                        @if ($isComplete)
                                            <div class="font-semibold text-[#172923] leading-snug">
                                                {{ number_format($totalEnergy, 0) }} kkal
                                            </div>
                                            <div class="text-[11px] text-[#53645B] mt-0.5">
                                                P: {{ number_format($totalProtein, 1) }}g • K: {{ number_format($totalCarbs, 1) }}g • L: {{ number_format($totalFat, 1) }}g
                                            </div>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[11px] font-medium text-[#B54708] bg-[#FEF6EE] px-2 py-0.5 rounded border border-[#F9DBAF]">
                                                <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                                <span>Data Belum Lengkap</span>
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Effective Date -->
                                    <td class="py-3.5 px-3 hidden md:table-cell text-xs text-[#53645B]">
                                        {{ $menu->effective_from ? $menu->effective_from->translatedFormat('d M Y') : 'Segera' }}
                                    </td>

                                    <!-- Action Link -->
                                    <td class="py-3.5 px-4 text-right">
                                        <a href="{{ route('nutrition.menus.show', $menu) }}" 
                                           class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-semibold text-[#285F80] hover:text-[#1E4861] bg-[#EDF2ED] hover:bg-[#E2EBE5] rounded-lg smooth-transition touch-target">
                                            <span>Dossier</span>
                                            <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($menus->hasPages())
                    <div class="p-4 border-t border-[#DCE5DD] bg-[#F5F7F3]">
                        {{ $menus->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-shell>
