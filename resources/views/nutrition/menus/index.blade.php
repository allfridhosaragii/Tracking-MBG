<x-app-shell title="Katalog Menu & Kandungan Gizi">
    <div class="space-y-5">
        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <span class="eyebrow-label text-[#285F80] block mb-1">Pengawas Gizi • Formulasi Resep</span>
                <h1 class="page-title">Katalog Racikan Menu &amp; Kandungan Gizi</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-1">Kelola komposisi bahan, gramasi saji, dan versi menu terstandar untuk produksi SPPG.</p>
            </div>

            <a href="{{ route('nutrition.menus.create') }}"
               class="min-h-[44px] px-4 py-2.5 bg-[#285F80] hover:bg-[#1E4861] text-white text-xs sm:text-sm font-semibold rounded-xl inline-flex items-center justify-center gap-2 shrink-0 smooth-transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Racik Draft Menu Baru</span>
            </a>
        </header>

        <!-- Summary Strip -->
        <section aria-label="Ringkasan Status Formulasi Menu" class="grid grid-cols-3 gap-3 sm:gap-4">
            <div class="luxury-surface p-3.5 sm:p-4">
                <span class="eyebrow-label block">Menu Aktif</span>
                <span class="text-2xl font-bold text-[#21684B] tabular-nums mt-1 block">{{ $activeMenusCount }}</span>
            </div>
            <div class="luxury-surface p-3.5 sm:p-4">
                <span class="eyebrow-label block">Draft</span>
                <span class="text-2xl font-bold text-[#885D1C] tabular-nums mt-1 block">{{ $draftMenusCount }}</span>
            </div>
            <div class="luxury-surface p-3.5 sm:p-4">
                <span class="eyebrow-label block">Belum Lengkap</span>
                <span class="text-2xl font-bold text-[#9B3232] tabular-nums mt-1 block">{{ $incompleteDraftsCount }}</span>
            </div>
        </section>

        <!-- Filter & Search -->
        <div class="luxury-surface p-3.5 sm:p-4">
            <form method="GET" action="{{ route('nutrition.menus.index') }}" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-1.5" role="tablist" aria-label="Filter status menu">
                    <a href="{{ route('nutrition.menus.index', ['q' => $searchQuery]) }}"
                       class="min-h-[44px] px-3.5 py-2 text-xs font-semibold rounded-xl inline-flex items-center whitespace-nowrap {{ $currentFilter === 'all' ? 'bg-[#285F80] text-white' : 'text-[#53645B] hover:text-[#172923] bg-[#FAFBF9]' }}">
                        Semua
                    </a>
                    <a href="{{ route('nutrition.menus.index', ['status' => 'ACTIVE', 'q' => $searchQuery]) }}"
                       class="min-h-[44px] px-3.5 py-2 text-xs font-semibold rounded-xl inline-flex items-center whitespace-nowrap {{ $currentFilter === 'ACTIVE' ? 'bg-[#1C6654] text-white' : 'text-[#53645B] hover:text-[#172923] bg-[#FAFBF9]' }}">
                        Aktif
                    </a>
                    <a href="{{ route('nutrition.menus.index', ['status' => 'DRAFT', 'q' => $searchQuery]) }}"
                       class="min-h-[44px] px-3.5 py-2 text-xs font-semibold rounded-xl inline-flex items-center whitespace-nowrap {{ $currentFilter === 'DRAFT' ? 'bg-[#885D1C] text-white' : 'text-[#53645B] hover:text-[#172923] bg-[#FAFBF9]' }}">
                        Draft
                    </a>
                    <a href="{{ route('nutrition.menus.index', ['status' => 'ARCHIVED', 'q' => $searchQuery]) }}"
                       class="min-h-[44px] px-3.5 py-2 text-xs font-semibold rounded-xl inline-flex items-center whitespace-nowrap {{ $currentFilter === 'ARCHIVED' ? 'bg-[#53645B] text-white' : 'text-[#53645B] hover:text-[#172923] bg-[#FAFBF9]' }}">
                        Arsip
                    </a>
                </div>

                <div class="flex items-center gap-2">
                    <div class="relative flex-1 md:w-64">
                        <input type="text"
                               name="q"
                               value="{{ $searchQuery }}"
                               placeholder="Cari nama atau kode menu..."
                               aria-label="Cari menu"
                               class="w-full min-h-[44px] pl-9 pr-3 py-2 text-xs sm:text-sm bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl text-[#172923]">
                        <svg class="w-4 h-4 text-[#53645B] absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    @if($currentFilter !== 'all')
                        <input type="hidden" name="status" value="{{ $currentFilter }}">
                    @endif

                    <button type="submit"
                            class="min-h-[44px] px-4 py-2 text-xs font-semibold bg-[#285F80] text-white rounded-xl cursor-pointer smooth-transition">
                        Cari
                    </button>

                    @if($searchQuery || $currentFilter !== 'all')
                        <a href="{{ route('nutrition.menus.index') }}"
                           class="min-h-[44px] px-3 py-2 text-xs font-semibold text-[#53645B] bg-[#EDF2ED] rounded-xl inline-flex items-center">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Menu Listing -->
        <div class="luxury-surface overflow-hidden">
            @if ($menus->isEmpty())
                <div class="py-10 px-6 text-center max-w-md mx-auto">
                    <h2 class="text-sm font-bold text-[#172923]">Belum Ada Data Formulasi Menu</h2>
                    <p class="text-xs text-[#53645B] mt-1 leading-relaxed">
                        {{ ($searchQuery || $currentFilter !== 'all') ? 'Tidak ditemukan menu yang sesuai dengan filter saat ini.' : 'Mulai susun draft menu makanan untuk kebutuhan nutrisi siswa sekolah sasaran.' }}
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('nutrition.menus.create') }}"
                           class="min-h-[44px] inline-flex items-center px-4 py-2 bg-[#285F80] text-white text-xs font-semibold rounded-xl">
                            Mulai Susun Draft Menu
                        </a>
                    </div>
                </div>
            @else
                <!-- Desktop Ledger Table -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="ledger-table text-xs sm:text-sm">
                        <thead>
                            <tr>
                                <th>Menu &amp; Kode</th>
                                <th>Versi</th>
                                <th>Status</th>
                                <th>Komponen</th>
                                <th>Profil Gizi</th>
                                <th>Berlaku</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($menus as $menu)
                                @php
                                    $isComplete = $menu->isComplete();
                                    $totalEnergy = $menu->totalEnergy();
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('nutrition.menus.show', $menu) }}" class="font-semibold text-[#172923] hover:text-[#285F80] block">
                                            {{ $menu->name }}
                                        </a>
                                        <span class="text-[11px] font-mono text-[#53645B]">{{ $menu->menu_code }}</span>
                                    </td>
                                    <td>
                                        <span class="px-2 py-0.5 rounded-md text-[11px] font-mono font-bold bg-[#FAFBF9] text-[#285F80] border border-[#DCE5DD]">v{{ $menu->version }}</span>
                                    </td>
                                    <td>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[11px] font-semibold border {{ $menu->status->badgeClasses() }}">
                                            <span>{{ $menu->status->label() }}</span>
                                        </span>
                                    </td>
                                    <td class="tabular-nums">
                                        <span class="font-medium text-[#172923] block">{{ $menu->items->count() }} bahan</span>
                                        <span class="text-[11px] text-[#53645B]">{{ number_format($menu->totalServingWeight(), 0) }} g</span>
                                    </td>
                                    <td class="tabular-nums">
                                        @if ($isComplete)
                                            <span class="font-semibold text-[#172923] block">{{ number_format($totalEnergy, 0) }} kkal</span>
                                            <span class="text-[11px] text-[#53645B]">
                                                P {{ number_format($menu->totalProtein(), 1) }}g • K {{ number_format($menu->totalCarbohydrates(), 1) }}g • L {{ number_format($menu->totalFat(), 1) }}g
                                            </span>
                                        @else
                                            <span class="text-[11px] font-semibold text-[#885D1C] bg-[#FEF7EC] px-2 py-0.5 rounded-md border border-[#F7D7A3]">Belum Lengkap</span>
                                        @endif
                                    </td>
                                    <td class="text-xs text-[#53645B]">
                                        {{ $menu->effective_from ? $menu->effective_from->translatedFormat('d M Y') : 'Segera' }}
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('nutrition.menus.show', $menu) }}"
                                           class="min-h-[40px] px-3 py-1.5 text-xs font-semibold text-[#285F80] bg-[#F0F6FA] hover:bg-[#E1EEF6] border border-[#C2DBE8] rounded-xl inline-flex items-center">
                                            Dossier &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Minimalist Menu Cards -->
                <div class="md:hidden divide-y divide-[#E7ECE8]">
                    @foreach ($menus as $menu)
                        @php $isComplete = $menu->isComplete(); @endphp
                        <a href="{{ route('nutrition.menus.show', $menu) }}" class="p-4 block hover:bg-[#FAFBF9] space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <span class="text-sm font-bold text-[#172923] block leading-snug">{{ $menu->name }}</span>
                                    <span class="text-[11px] font-mono text-[#285F80] mt-0.5 block">{{ $menu->menu_code }} • v{{ $menu->version }}</span>
                                </div>
                                <span class="shrink-0 px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $menu->status->badgeClasses() }}">
                                    {{ $menu->status->label() }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between text-xs pt-1">
                                <span class="text-[#53645B] tabular-nums">{{ $menu->items->count() }} bahan ({{ number_format($menu->totalServingWeight(), 0) }} g)</span>
                                @if ($isComplete)
                                    <span class="font-bold text-[#172923] tabular-nums">{{ number_format($menu->totalEnergy(), 0) }} kkal</span>
                                @else
                                    <span class="text-[11px] font-semibold text-[#885D1C]">Data belum lengkap</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

                @if ($menus->hasPages())
                    <div class="p-4 border-t border-[#E7ECE8] bg-[#FAFBF9]">
                        {{ $menus->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-shell>
