<x-app-shell title="Desk Pengawas Gizi">
    <div class="space-y-6">
        <!-- Minimalist First-Fold Header -->
        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <span class="eyebrow-label text-[#285F80] block mb-1">Pengawas Gizi • {{ $sppg->code }}</span>
                <h1 class="page-title">Desk Standar Mutu &amp; Gizi</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-1">Inspeksi organoleptik batch makanan siap distribusi dan pengawasan kecukupan nutrisi menu.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <a href="{{ route('nutrition.qc.index') }}"
                   class="min-h-[44px] px-4 py-2.5 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs sm:text-sm font-semibold inline-flex items-center justify-center gap-2 smooth-transition">
                    <span>Meja Uji Mutu (QC)</span>
                    @if (($pendingQcBatches ?? collect())->count() > 0)
                        <span class="px-1.5 py-0.5 rounded-md bg-white text-[#1C6654] text-[11px] font-bold tabular-nums">
                            {{ $pendingQcBatches->count() }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('nutrition.menus.index') }}"
                   class="min-h-[44px] px-4 py-2.5 rounded-xl bg-white hover:bg-[#FAFBF9] text-[#172923] border border-[#DCE5DD] text-xs sm:text-sm font-semibold inline-flex items-center justify-center smooth-transition">
                    Katalog Menu
                </a>
            </div>
        </header>

        <!-- Operational Metrics Strip -->
        <section aria-label="Indikator Mutu & Gizi" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <a href="{{ route('nutrition.qc.index') }}" class="luxury-surface p-4 sm:p-5 hover:border-[#285F80] smooth-transition block">
                <span class="eyebrow-label block">Antrean Uji QC</span>
                <div class="mt-2 flex items-baseline gap-1.5">
                    <span class="text-2xl sm:text-3xl font-bold {{ ($pendingQcBatches ?? collect())->count() > 0 ? 'text-[#885D1C]' : 'text-[#21684B]' }} tabular-nums">
                        {{ ($pendingQcBatches ?? collect())->count() }}
                    </span>
                    <span class="text-xs text-[#53645B]">batch</span>
                </div>
                <span class="text-[11px] text-[#53645B] block mt-1">Menunggu keputusan mutu</span>
            </a>

            <div class="luxury-surface p-4 sm:p-5">
                <span class="eyebrow-label block">Lolos QC Hari Ini</span>
                <div class="mt-2 flex items-baseline gap-1.5">
                    <span class="text-2xl sm:text-3xl font-bold text-[#21684B] tabular-nums">{{ $releasedTodayCount ?? 0 }}</span>
                    <span class="text-xs text-[#53645B]">batch</span>
                </div>
                <span class="text-[11px] text-[#53645B] block mt-1">Siap dimuat kurir</span>
            </div>

            <a href="{{ route('nutrition.menus.index') }}" class="luxury-surface p-4 sm:p-5 hover:border-[#285F80] smooth-transition block">
                <span class="eyebrow-label block">Formulasi Menu</span>
                <div class="mt-2 flex items-baseline gap-1.5">
                    <span class="text-2xl sm:text-3xl font-bold text-[#285F80] tabular-nums">{{ $activeMenus->count() }}</span>
                    <span class="text-xs text-[#53645B]">aktif ({{ $draftMenusCount }} draft)</span>
                </div>
                <span class="text-[11px] text-[#53645B] block mt-1">Resep terverifikasi AKG</span>
            </a>

            <div class="luxury-surface p-4 sm:p-5">
                <span class="eyebrow-label block">Sasaran Layanan</span>
                <div class="mt-2 flex items-baseline gap-1.5">
                    <span class="text-2xl sm:text-3xl font-bold text-[#172923] tabular-nums">{{ number_format($totalBeneficiaries) }}</span>
                    <span class="text-xs text-[#53645B]">siswa</span>
                </div>
                <span class="text-[11px] text-[#53645B] block mt-1">{{ $schools->count() }} sekolah binaan</span>
            </div>
        </section>

        <!-- Live Pending QC Queue (Promoted to First Fold if non-empty) -->
        @if (isset($pendingQcBatches) && $pendingQcBatches->isNotEmpty())
            <x-card title="Batch Menunggu Inspeksi QC" subtitle="Segera periksa parameter sensorik dan suhu inti sebelum batas waktu keberangkatan." badge="{{ $pendingQcBatches->count() }} Antrean">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach ($pendingQcBatches as $batch)
                        <div class="inset-surface p-4 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <span class="font-mono text-xs font-bold text-[#172923] block">{{ $batch->batch_code }}</span>
                                <span class="text-xs text-[#53645B] block truncate mt-0.5">{{ $batch->productionPlan?->menu?->name }}</span>
                                <span class="text-[11px] font-semibold text-[#885D1C] mt-1 inline-block tabular-nums">
                                    {{ number_format($batch->actual_portions ?? $batch->planned_portions) }} porsi • {{ $batch->status->label() }}
                                </span>
                            </div>
                            <a href="{{ route('nutrition.qc.show', $batch) }}"
                               class="min-h-[44px] px-4 py-2 rounded-xl bg-[#285F80] hover:bg-[#1E4861] text-white text-xs font-semibold inline-flex items-center shrink-0 smooth-transition">
                                Uji Mutu
                            </a>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif

        <!-- Active Menu Registry -->
        <x-card title="Katalog Formulasi Menu Aktif" subtitle="Resep sah yang menjadi acuan takaran masak dapur SPPG.">
            <x-slot:headerAction>
                <a href="{{ route('nutrition.menus.create') }}"
                   class="min-h-[44px] px-3.5 py-2 text-xs font-semibold text-[#285F80] bg-[#F0F6FA] hover:bg-[#E1EEF6] border border-[#C2DBE8] rounded-xl inline-flex items-center smooth-transition">
                    + Racik Draft Baru
                </a>
            </x-slot:headerAction>

            @if ($activeMenus->isEmpty())
                <div class="py-8 text-center text-xs text-[#53645B] inset-surface p-4">
                    <p class="font-semibold text-[#172923]">Belum Ada Menu Berstatus Aktif</p>
                    <p class="mt-1">Susun draft menu baru dan lengkapi gramasi gizi untuk mengaktifkan rujukan produksi.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
                    @foreach ($activeMenus as $activeMenu)
                        <a href="{{ route('nutrition.menus.show', $activeMenu) }}"
                           class="inset-surface p-4 hover:border-[#285F80] smooth-transition flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="font-mono text-[11px] font-bold text-[#285F80]">{{ $activeMenu->menu_code }} • v{{ $activeMenu->version }}</span>
                                    <span class="text-[10px] font-semibold text-[#21684B] bg-[#EDF7F2] px-2 py-0.5 rounded-md border border-[#BCE2D0]">Aktif</span>
                                </div>
                                <h3 class="text-sm font-bold text-[#172923] leading-snug">{{ $activeMenu->name }}</h3>
                                <p class="mt-2 text-xs text-[#53645B] tabular-nums">
                                    <strong class="text-[#172923]">{{ number_format($activeMenu->totalEnergy(), 0) }} kkal</strong> •
                                    P {{ number_format($activeMenu->totalProtein(), 1) }}g •
                                    K {{ number_format($activeMenu->totalCarbohydrates(), 1) }}g •
                                    L {{ number_format($activeMenu->totalFat(), 1) }}g
                                </p>
                            </div>
                            <div class="pt-3 mt-3 border-t border-[#E7ECE8] flex items-center justify-between text-xs">
                                <span class="text-[#53645B]">{{ $activeMenu->items->count() }} bahan</span>
                                <span class="font-semibold text-[#285F80]">Buka Dossier &rarr;</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-card>

        <!-- Progressive Disclosure for SOP & AKG Reference (Zero Clutter on Mobile) -->
        <details class="sop-disclosure">
            <summary>
                <span>Panduan Standar AKG &amp; 3 Titik Uji Keamanan Pangan</span>
                <svg class="w-4 h-4 text-[#53645B] disclosure-chevron shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>
            <div class="p-4 sm:p-5 space-y-4 text-xs text-[#53645B]">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="inset-surface p-3">
                        <span class="font-bold text-[#172923] block">SD (6 hingga 12 tahun)</span>
                        <span class="text-[#1C6654] font-semibold tabular-nums">450 hingga 550 kkal / porsi</span>
                    </div>
                    <div class="inset-surface p-3">
                        <span class="font-bold text-[#172923] block">SMP (13 hingga 15 tahun)</span>
                        <span class="text-[#1C6654] font-semibold tabular-nums">600 hingga 700 kkal / porsi</span>
                    </div>
                    <div class="inset-surface p-3">
                        <span class="font-bold text-[#172923] block">SMA (16 hingga 18 tahun)</span>
                        <span class="text-[#1C6654] font-semibold tabular-nums">700 hingga 800 kkal / porsi</span>
                    </div>
                </div>
                <p class="leading-relaxed">
                    <strong>Protokol Keamanan Pangan:</strong> Pemeriksaan dilakukan pada 3 titik (Dapur SPPG dengan suhu kemas &gt;60°C, Kedatangan Sekolah untuk keutuhan segel wadah, dan Uji Pra-Konsumsi oleh PIC Sekolah). Status <strong>DITAHAN (ON HOLD)</strong> memblokir pemuatan batch ke armada kurir secara otomatis.
                </p>
            </div>
        </details>

        <!-- Covered Schools Reference -->
        <x-card title="Sekolah Binaan Penerima Nutrisi" subtitle="Unit sekolah di bawah pengawasan gizi {{ $sppg->code }}.">
            <div class="divide-y divide-[#E7ECE8] -my-1">
                @foreach ($schools as $school)
                    <div class="py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <a href="{{ route('schools.show', $school) }}" class="font-semibold text-xs sm:text-sm text-[#172923] hover:text-[#1C6654] block truncate">
                                {{ $school->name }}
                            </a>
                            <span class="text-[11px] text-[#53645B] block truncate">{{ $school->education_level }} • {{ $school->address }}</span>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-sm font-bold text-[#172923] tabular-nums block">{{ number_format($school->registered_beneficiaries) }}</span>
                            <span class="text-[10px] text-[#53645B]">siswa</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>
</x-app-shell>
