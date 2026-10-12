<x-app-shell title="Dossier Menu {{ $menu->menu_code }} v{{ $menu->version }} : MBG-Track">
    <div class="space-y-6">
        <!-- Breadcrumb & Top Navigation -->
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <div class="flex items-center gap-2 text-xs font-medium text-[#53645B] mb-1">
                    <a href="{{ route('nutrition.dashboard') }}" class="hover:text-[#172923] smooth-transition">Desk Pengawas Gizi</a>
                    <span aria-hidden="true">/</span>
                    <a href="{{ route('nutrition.menus.index') }}" class="hover:text-[#172923] smooth-transition">Katalog Menu</a>
                    <span aria-hidden="true">/</span>
                    <span class="font-mono text-[#285F80] font-semibold">{{ $menu->menu_code }}</span>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-[#172923]">
                        {{ $menu->name }}
                    </h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-mono font-bold bg-[#EDF2ED] text-[#285F80] border border-[#DCE5DD]">
                        Versi {{ $menu->version }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border {{ $menu->status->badgeClasses() }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $menu->status->dotClasses() }}" aria-hidden="true"></span>
                        <span>{{ $menu->status->label() }}</span>
                    </span>
                </div>
                <div class="text-xs text-[#53645B] mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1">
                    <span>Kode Sistem: <strong class="font-mono text-[#172923]">{{ $menu->menu_code }}</strong></span>
                    <span aria-hidden="true">•</span>
                    <span>SPPG: <strong>{{ $menu->sppg?->name ?? 'SPPG Unit' }}</strong></span>
                    <span aria-hidden="true">•</span>
                    <span>Disusun: {{ $menu->created_at->translatedFormat('d M Y, H:i') }} WIB</span>
                </div>
            </div>

            <!-- Version Switcher Pills -->
            @if ($versionHistory->count() > 1)
                <div class="flex items-center gap-1.5 p-1 rounded-xl bg-white border border-[#DCE5DD] shadow-2xs self-start">
                    <span class="text-[11px] font-semibold text-[#53645B] px-2">Versi:</span>
                    @foreach ($versionHistory as $v)
                        <a href="{{ route('nutrition.menus.show', $v) }}" 
                           class="px-2.5 py-1 text-xs font-mono rounded-lg smooth-transition touch-target {{ $v->id === $menu->id ? 'bg-[#285F80] text-white font-bold shadow-xs' : 'text-[#53645B] hover:text-[#172923] hover:bg-[#F5F7F3]' }}">
                            v{{ $v->version }} ({{ $v->status->value }})
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Status & Governance Notices -->
        @if ($menu->isActive())
            <div role="status" class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-[#1B573E] text-xs sm:text-sm flex items-start gap-3">
                <svg class="w-5 h-5 mt-0.5 shrink-0 text-[#1B573E]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <div class="leading-relaxed">
                    <p class="font-bold text-[#1B573E]">Versi Menu Aktif &amp; Terkunci (Immutable)</p>
                    <p class="text-[11px] sm:text-xs mt-0.5">
                        Versi ini telah diverifikasi dan disahkan oleh <strong>{{ $menu->approver?->name ?? 'Pengawas Gizi' }}</strong> pada {{ $menu->approved_at?->translatedFormat('d M Y, H:i') }} WIB. Nilai gramasi dan gizi terkunci agar tidak mengubah riwayat produksi yang sedang atau telah berjalan. Untuk merevisi takaran, gunakan tombol <strong>Buat Versi Baru</strong>.
                    </p>
                </div>
            </div>
        @elseif ($menu->isArchived())
            <div role="status" class="p-4 rounded-xl bg-[#F2F4F7] border border-[#D0D5DD] text-[#475467] text-xs sm:text-sm flex items-start gap-3">
                <svg class="w-5 h-5 mt-0.5 shrink-0 text-[#475467]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                <div class="leading-relaxed">
                    <p class="font-bold text-[#344054]">Arsip Versi Histori</p>
                    <p class="text-[11px] sm:text-xs mt-0.5">
                        Versi ini telah diarsipkan dan digantikan oleh rilis menu yang lebih mutakhir. Data dipertahankan sebagai arsip audit nutrisi operasional.
                    </p>
                </div>
            </div>
        @elseif ($menu->isDraft())
            @if ($isComplete)
                <div role="status" class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-[#1B573E] text-xs sm:text-sm flex items-start gap-3">
                    <svg class="w-5 h-5 mt-0.5 shrink-0 text-[#1B573E]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <div class="leading-relaxed">
                        <p class="font-bold text-[#1B573E]">Semua Kriteria Kelengkapan Nutrisi Terpenuhi</p>
                        <p class="text-[11px] sm:text-xs mt-0.5">
                            Seluruh komponen makanan telah memiliki takaran gramasi, makronutrien lengkap, serta rujukan sumber gizi yang valid. Draft ini siap disahkan dan diaktifkan.
                        </p>
                    </div>
                </div>
            @else
                <div role="alert" class="p-4 rounded-xl bg-[#FEF6EE] border border-[#F9DBAF] text-[#B54708] text-xs sm:text-sm flex items-start gap-3">
                    <svg class="w-5 h-5 mt-0.5 shrink-0 text-[#B54708]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="leading-relaxed">
                        <p class="font-bold text-[#B54708]">Draft Belum Memenuhi Syarat Aktivasi</p>
                        <p class="text-[11px] sm:text-xs mt-0.5">
                            Sistem melarang aktivasi menu dengan data gizi yang belum lengkap atau tanpa sumber rujukan. Periksa catatan berikut:
                        </p>
                        <ul class="list-disc list-inside mt-2 space-y-0.5 text-[11px]">
                            @foreach ($violations as $violation)
                                <li>{{ $violation }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        @endif

        <!-- Split Editorial Workspace: Left Dossier / Right Nutrition Gauges -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- LEFT WORKSPACE: Components Table & Add Form (7 cols on lg) -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Components Section -->
                <div class="bg-white border border-[#DCE5DD] rounded-xl shadow-2xs overflow-hidden">
                    <div class="p-4 sm:p-5 border-b border-[#DCE5DD] flex items-center justify-between">
                        <div>
                            <h2 class="text-sm sm:text-base font-bold text-[#172923]">
                                Komposisi Bahan &amp; Takaran Gizi
                            </h2>
                            <p class="text-xs text-[#53645B] mt-0.5">
                                Rincian komponen makanan, porsi saji per anak, dan angka gizi per sajian.
                            </p>
                        </div>
                        <span class="text-xs font-medium text-[#53645B] bg-[#F5F7F3] px-2.5 py-1 rounded-lg border border-[#DCE5DD]">
                            {{ $items->count() }} Bahan Terdaftar
                        </span>
                    </div>

                    @if ($items->isEmpty())
                        <div class="p-8 text-center">
                            <div class="w-10 h-10 rounded-lg bg-[#EDF2ED] text-[#285F80] flex items-center justify-center mx-auto mb-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold text-[#172923]">Belum ada komponen makanan</h3>
                            <p class="text-xs text-[#53645B] mt-1">
                                Tambahkan komponen karbohidrat, lauk pauk, sayuran, dan buah menggunakan formulir di bawah.
                            </p>
                        </div>
                    @else
                        <!-- Responsive Components Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-[#F5F7F3] border-b border-[#DCE5DD] text-[#53645B] text-[11px] uppercase tracking-wider font-semibold">
                                        <th scope="col" class="py-2.5 px-3">Komponen</th>
                                        <th scope="col" class="py-2.5 px-2 text-right">Berat (g)</th>
                                        <th scope="col" class="py-2.5 px-2 text-right">Kalori</th>
                                        <th scope="col" class="py-2.5 px-2 text-right">Protein</th>
                                        <th scope="col" class="py-2.5 px-2 text-right">Karbo</th>
                                        <th scope="col" class="py-2.5 px-2 text-right">Lemak</th>
                                        <th scope="col" class="py-2.5 px-2 text-right">Serat</th>
                                        <th scope="col" class="py-2.5 px-3">Sumber Rujukan</th>
                                        @if ($menu->isDraft())
                                            <th scope="col" class="py-2.5 px-2 text-center">Hapus</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#DCE5DD]">
                                    @foreach ($items as $item)
                                        <tr class="hover:bg-[#F9FAF8] smooth-transition">
                                            <!-- Item Name & Notes -->
                                            <td class="py-2.5 px-3">
                                                <span class="font-semibold text-[#172923] block">{{ $item->item_name }}</span>
                                                @if ($item->notes)
                                                    <span class="text-[10px] text-[#53645B] block">{{ $item->notes }}</span>
                                                @endif
                                            </td>

                                            <!-- Weight -->
                                            <td class="py-2.5 px-2 text-right font-mono font-medium text-[#172923]">
                                                {{ number_format($item->serving_weight_g, 1) }}
                                            </td>

                                            <!-- Energy -->
                                            <td class="py-2.5 px-2 text-right font-mono">
                                                @if ($item->energy_kcal !== null)
                                                    <span class="font-bold text-[#172923]">{{ number_format($item->energy_kcal, 1) }}</span>
                                                @else
                                                    <span class="text-[#B54708] italic">null</span>
                                                @endif
                                            </td>

                                            <!-- Protein -->
                                            <td class="py-2.5 px-2 text-right font-mono">
                                                @if ($item->protein_g !== null)
                                                    <span>{{ number_format($item->protein_g, 1) }}</span>
                                                @else
                                                    <span class="text-[#B54708] italic">null</span>
                                                @endif
                                            </td>

                                            <!-- Carbohydrate -->
                                            <td class="py-2.5 px-2 text-right font-mono">
                                                @if ($item->carbohydrate_g !== null)
                                                    <span>{{ number_format($item->carbohydrate_g, 1) }}</span>
                                                @else
                                                    <span class="text-[#B54708] italic">null</span>
                                                @endif
                                            </td>

                                            <!-- Fat -->
                                            <td class="py-2.5 px-2 text-right font-mono">
                                                @if ($item->fat_g !== null)
                                                    <span>{{ number_format($item->fat_g, 1) }}</span>
                                                @else
                                                    <span class="text-[#B54708] italic">null</span>
                                                @endif
                                            </td>

                                            <!-- Fiber -->
                                            <td class="py-2.5 px-2 text-right font-mono">
                                                @if ($item->fiber_g !== null)
                                                    <span>{{ number_format($item->fiber_g, 1) }}</span>
                                                @else
                                                    <span class="text-[#B54708] italic">null</span>
                                                @endif
                                            </td>

                                            <!-- Nutrition Source -->
                                            <td class="py-2.5 px-3 text-[#53645B]">
                                                @if ($item->nutrition_source)
                                                    <span class="truncate block max-w-[130px]" title="{{ $item->nutrition_source }}">
                                                        {{ $item->nutrition_source }}
                                                    </span>
                                                @else
                                                    <span class="text-[#9B3232] font-semibold text-[10px]">Wajib Diisi</span>
                                                @endif
                                            </td>

                                            <!-- Delete Action (Draft Only) -->
                                            @if ($menu->isDraft())
                                                <td class="py-2.5 px-2 text-center">
                                                    <form method="POST" action="{{ route('nutrition.menus.items.destroy', [$menu, $item]) }}" 
                                                          onsubmit="return confirm('Hapus komponen \'{{ $item->item_name }}\' dari draft menu ini?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                class="p-1 text-[#9B3232] hover:bg-[#FDF2F2] rounded-md smooth-transition touch-target" 
                                                                title="Hapus komponen ini">
                                                            <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="bg-[#F5F7F3] border-t-2 border-[#DCE5DD] font-bold text-[#172923]">
                                        <td class="py-2.5 px-3">Total Komponen</td>
                                        <td class="py-2.5 px-2 text-right font-mono">{{ number_format($totalWeight, 1) }} g</td>
                                        <td class="py-2.5 px-2 text-right font-mono">
                                            {{ $totalEnergy !== null ? number_format($totalEnergy, 1) . ' kkal' : 'Belum Lengkap' }}
                                        </td>
                                        <td class="py-2.5 px-2 text-right font-mono">
                                            {{ $totalProtein !== null ? number_format($totalProtein, 1) . 'g' : '-' }}
                                        </td>
                                        <td class="py-2.5 px-2 text-right font-mono">
                                            {{ $totalCarbs !== null ? number_format($totalCarbs, 1) . 'g' : '-' }}
                                        </td>
                                        <td class="py-2.5 px-2 text-right font-mono">
                                            {{ $totalFat !== null ? number_format($totalFat, 1) . 'g' : '-' }}
                                        </td>
                                        <td class="py-2.5 px-2 text-right font-mono">
                                            {{ $totalFiber !== null ? number_format($totalFiber, 1) . 'g' : '-' }}
                                        </td>
                                        <td colspan="{{ $menu->isDraft() ? 2 : 1 }}" class="py-2.5 px-3 text-[10px] text-[#53645B] font-normal">
                                            Kalkulasi backend Laravel
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- ADD COMPONENT FORM (DRAFT ONLY) -->
                @if ($menu->isDraft())
                    <div class="bg-white border border-[#DCE5DD] rounded-xl shadow-2xs p-5">
                        <div class="pb-3 border-b border-[#DCE5DD] mb-4">
                            <h3 class="text-sm font-bold text-[#172923]">
                                Tambah Komponen Bahan Makanan
                            </h3>
                            <p class="text-xs text-[#53645B] mt-0.5">
                                Masukkan rincian gramasi saji dan kandungan zat gizi terukur dari sumber terpercaya.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('nutrition.menus.items.store', $menu) }}" class="space-y-4">
                            @csrf

                            <!-- Item Name & Serving Weight -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="sm:col-span-2 space-y-1">
                                    <label for="item_name" class="block text-xs font-semibold text-[#172923]">
                                        Nama Komponen Bahan <span class="text-[#9B3232]">*</span>
                                    </label>
                                    <input type="text" 
                                           id="item_name" 
                                           name="item_name" 
                                           value="{{ old('item_name') }}" 
                                           required 
                                           placeholder="Contoh: Nasi Putih Pulen, Dada Ayam Panggang, Tumis Buncis Jagung" 
                                           class="w-full px-3 py-2 text-xs bg-white border border-[#DCE5DD] rounded-lg text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#285F80] touch-target">
                                </div>

                                <div class="space-y-1">
                                    <label for="serving_weight_g" class="block text-xs font-semibold text-[#172923]">
                                        Berat Saji (gram) <span class="text-[#9B3232]">*</span>
                                    </label>
                                    <input type="number" 
                                           step="0.01" 
                                           min="0.01" 
                                           id="serving_weight_g" 
                                           name="serving_weight_g" 
                                           value="{{ old('serving_weight_g') }}" 
                                           required 
                                           placeholder="Contoh: 150" 
                                           class="w-full px-3 py-2 text-xs bg-white border border-[#DCE5DD] rounded-lg text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#285F80] font-mono touch-target">
                                </div>
                            </div>

                            <!-- Macronutrients Grid -->
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5">
                                <div class="space-y-1">
                                    <label for="energy_kcal" class="block text-[11px] font-semibold text-[#172923]">
                                        Energi (kkal)
                                    </label>
                                    <input type="number" 
                                           step="0.01" 
                                           min="0" 
                                           id="energy_kcal" 
                                           name="energy_kcal" 
                                           value="{{ old('energy_kcal') }}" 
                                           placeholder="kkal" 
                                           class="w-full px-2.5 py-2 text-xs bg-white border border-[#DCE5DD] rounded-lg text-[#172923] font-mono focus:outline-none focus:ring-2 focus:ring-[#285F80] touch-target">
                                </div>

                                <div class="space-y-1">
                                    <label for="protein_g" class="block text-[11px] font-semibold text-[#172923]">
                                        Protein (g)
                                    </label>
                                    <input type="number" 
                                           step="0.01" 
                                           min="0" 
                                           id="protein_g" 
                                           name="protein_g" 
                                           value="{{ old('protein_g') }}" 
                                           placeholder="g" 
                                           class="w-full px-2.5 py-2 text-xs bg-white border border-[#DCE5DD] rounded-lg text-[#172923] font-mono focus:outline-none focus:ring-2 focus:ring-[#285F80] touch-target">
                                </div>

                                <div class="space-y-1">
                                    <label for="carbohydrate_g" class="block text-[11px] font-semibold text-[#172923]">
                                        Karbo (g)
                                    </label>
                                    <input type="number" 
                                           step="0.01" 
                                           min="0" 
                                           id="carbohydrate_g" 
                                           name="carbohydrate_g" 
                                           value="{{ old('carbohydrate_g') }}" 
                                           placeholder="g" 
                                           class="w-full px-2.5 py-2 text-xs bg-white border border-[#DCE5DD] rounded-lg text-[#172923] font-mono focus:outline-none focus:ring-2 focus:ring-[#285F80] touch-target">
                                </div>

                                <div class="space-y-1">
                                    <label for="fat_g" class="block text-[11px] font-semibold text-[#172923]">
                                        Lemak (g)
                                    </label>
                                    <input type="number" 
                                           step="0.01" 
                                           min="0" 
                                           id="fat_g" 
                                           name="fat_g" 
                                           value="{{ old('fat_g') }}" 
                                           placeholder="g" 
                                           class="w-full px-2.5 py-2 text-xs bg-white border border-[#DCE5DD] rounded-lg text-[#172923] font-mono focus:outline-none focus:ring-2 focus:ring-[#285F80] touch-target">
                                </div>

                                <div class="space-y-1 col-span-2 sm:col-span-1">
                                    <label for="fiber_g" class="block text-[11px] font-semibold text-[#172923]">
                                        Serat (g)
                                    </label>
                                    <input type="number" 
                                           step="0.01" 
                                           min="0" 
                                           id="fiber_g" 
                                           name="fiber_g" 
                                           value="{{ old('fiber_g') }}" 
                                           placeholder="g" 
                                           class="w-full px-2.5 py-2 text-xs bg-white border border-[#DCE5DD] rounded-lg text-[#172923] font-mono focus:outline-none focus:ring-2 focus:ring-[#285F80] touch-target">
                                </div>
                            </div>

                            <!-- Nutrition Source & Notes -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <label for="nutrition_source" class="block text-xs font-semibold text-[#172923]">
                                        Sumber Rujukan Nilai Gizi <span class="text-[#9B3232]">*</span>
                                    </label>
                                    <input type="text" 
                                           id="nutrition_source" 
                                           name="nutrition_source" 
                                           value="{{ old('nutrition_source') }}" 
                                           placeholder="Contoh: Tabel Komposisi Pangan Indonesia (TKPI 2019)" 
                                           class="w-full px-3 py-2 text-xs bg-white border border-[#DCE5DD] rounded-lg text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#285F80] touch-target">
                                    <p class="text-[10px] text-[#53645B]">Wajib untuk syarat sah audit kelengkapan gizi.</p>
                                </div>

                                <div class="space-y-1">
                                    <label for="notes" class="block text-xs font-semibold text-[#172923]">
                                        Catatan Pengolahan (Opsional)
                                    </label>
                                    <input type="text" 
                                           id="notes" 
                                           name="notes" 
                                           value="{{ old('notes') }}" 
                                           placeholder="Contoh: Dimasak dengan minyak canola rendah jenuh" 
                                           class="w-full px-3 py-2 text-xs bg-white border border-[#DCE5DD] rounded-lg text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#285F80] touch-target">
                                </div>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button type="submit" 
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-[#285F80] hover:bg-[#1E4861] text-white text-xs font-semibold rounded-lg shadow-xs smooth-transition touch-target">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <span>Tambahkan ke Menu</span>
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>

            <!-- RIGHT WORKSPACE: Nutrition Totals & Governance Control Panel (5 cols on lg) -->
            <div class="lg:col-span-4 space-y-6">
                <!-- NUTRITION TOTALS GAUGE CARD -->
                <div class="bg-white border border-[#DCE5DD] rounded-xl shadow-2xs p-5 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-[#DCE5DD]">
                        <h3 class="text-sm font-bold text-[#172923]">
                            Ringkasan Profil Gizi Saji
                        </h3>
                        <span class="text-[11px] font-mono font-semibold px-2 py-0.5 rounded bg-[#EDF2ED] text-[#285F80]">
                            {{ number_format($totalWeight, 0) }}g Saji
                        </span>
                    </div>

                    <!-- Energy / Calorie Gauge -->
                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-xs font-semibold text-[#53645B]">Nilai Energi Total</span>
                            @if ($totalEnergy !== null)
                                <span class="text-xl font-extrabold text-[#172923]">
                                    {{ number_format($totalEnergy, 0) }} <span class="text-xs font-medium text-[#53645B]">kkal</span>
                                </span>
                            @else
                                <span class="text-xs font-bold text-[#B54708] bg-[#FEF6EE] px-2 py-0.5 rounded">
                                    Belum Lengkap
                                </span>
                            @endif
                        </div>

                        <!-- Progress Bar against 650 kcal benchmark -->
                        @php
                            $targetCal = 650;
                            $pctCal = $totalEnergy !== null ? min(100, round(($totalEnergy / $targetCal) * 100)) : 0;
                        @endphp
                        <div class="w-full bg-[#EDF2ED] rounded-full h-2 mt-2 overflow-hidden">
                            <div class="bg-[#285F80] h-2 rounded-full smooth-transition" style="width: {{ $pctCal }}%"></div>
                        </div>
                        <div class="flex justify-between text-[10px] text-[#53645B] mt-1">
                            <span>0 kkal</span>
                            <span>Acuan MBG (~{{ $targetCal }} kkal)</span>
                        </div>
                    </div>

                    <!-- Macronutrient Metric Grid -->
                    <div class="grid grid-cols-2 gap-2.5 pt-2">
                        <!-- Protein -->
                        <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD]">
                            <span class="text-[10px] uppercase font-bold text-[#53645B] block">Protein</span>
                            <span class="text-base font-bold text-[#172923] mt-0.5 block">
                                {{ $totalProtein !== null ? number_format($totalProtein, 1) . ' g' : '-' }}
                            </span>
                            <span class="text-[10px] text-[#53645B]">Pembangun jaringan</span>
                        </div>

                        <!-- Karbohidrat -->
                        <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD]">
                            <span class="text-[10px] uppercase font-bold text-[#53645B] block">Karbohidrat</span>
                            <span class="text-base font-bold text-[#172923] mt-0.5 block">
                                {{ $totalCarbs !== null ? number_format($totalCarbs, 1) . ' g' : '-' }}
                            </span>
                            <span class="text-[10px] text-[#53645B]">Energi harian</span>
                        </div>

                        <!-- Lemak -->
                        <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD]">
                            <span class="text-[10px] uppercase font-bold text-[#53645B] block">Lemak</span>
                            <span class="text-base font-bold text-[#172923] mt-0.5 block">
                                {{ $totalFat !== null ? number_format($totalFat, 1) . ' g' : '-' }}
                            </span>
                            <span class="text-[10px] text-[#53645B]">Asam lemak esensial</span>
                        </div>

                        <!-- Serat -->
                        <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD]">
                            <span class="text-[10px] uppercase font-bold text-[#53645B] block">Serat Pangan</span>
                            <span class="text-base font-bold text-[#172923] mt-0.5 block">
                                {{ $totalFiber !== null ? number_format($totalFiber, 1) . ' g' : '-' }}
                            </span>
                            <span class="text-[10px] text-[#53645B]">Pencernaan sehat</span>
                        </div>
                    </div>
                </div>

                <!-- GOVERNANCE & LIFECYCLE ACTION PANEL -->
                <div class="bg-white border border-[#DCE5DD] rounded-xl shadow-2xs p-5 space-y-4">
                    <h3 class="text-sm font-bold text-[#172923] pb-2 border-b border-[#DCE5DD]">
                        Aksi &amp; Status Operasional
                    </h3>

                    @if ($menu->isDraft())
                        @if ($isComplete)
                            <!-- Activate Menu Action Form -->
                            <form method="POST" action="{{ route('nutrition.menus.activate', $menu) }}" 
                                  onsubmit="return confirm('Sahkan versi menu ini? Menu yang telah disahkan akan berstatus Aktif dan terkunci (immutable) untuk produksi SPPG.');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" 
                                        class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-[#1B573E] hover:bg-[#14422F] text-white text-xs sm:text-sm font-bold rounded-xl shadow-xs smooth-transition touch-target focus:outline-none focus:ring-2 focus:ring-[#1B573E] focus:ring-offset-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Sahkan &amp; Aktifkan Versi Menu</span>
                                </button>
                            </form>
                            <p class="text-[11px] text-[#53645B] text-center leading-tight">
                                Pengesahan akan otomatis mengarsipkan versi aktif lama jika ada.
                            </p>
                        @else
                            <button type="button" 
                                    disabled 
                                    class="w-full px-4 py-3 bg-[#EDF2ED] text-[#708177] text-xs sm:text-sm font-semibold rounded-xl cursor-not-allowed border border-[#DCE5DD]">
                                Sahkan &amp; Aktifkan (Lengkapi Data)
                            </button>
                            <p class="text-[11px] text-[#B54708] text-center leading-tight">
                                Tombol aktivasi akan terbuka otomatis setelah semua komponen memiliki data gizi dan sumber rujukan.
                            </p>
                        @endif

                        <div class="pt-2 border-t border-[#DCE5DD] flex items-center justify-between">
                            <a href="{{ route('nutrition.menus.edit', $menu) }}" 
                               class="text-xs font-semibold text-[#285F80] hover:text-[#1E4861] smooth-transition touch-target">
                                Sunting Metadata Draft
                            </a>
                        </div>
                    @elseif ($menu->isActive())
                        <!-- Branch New Version Action Form -->
                        <form method="POST" action="{{ route('nutrition.menus.new-version', $menu) }}" 
                              onsubmit="return confirm('Buat cabang versi baru (v{{ $menu->version + 1 }}) dari menu ini? Versi saat ini (v{{ $menu->version }}) akan tetap utuh.');">
                            @csrf
                            <button type="submit" 
                                    class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-[#285F80] hover:bg-[#1E4861] text-white text-xs sm:text-sm font-bold rounded-xl shadow-xs smooth-transition touch-target focus:outline-none focus:ring-2 focus:ring-[#285F80] focus:ring-offset-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                                </svg>
                                <span>Buat Versi Baru (v{{ $menu->version + 1 }})</span>
                            </button>
                        </form>
                        <p class="text-[11px] text-[#53645B] text-center leading-tight">
                            Membuat salinan resep sebagai Draft versi {{ $menu->version + 1 }} untuk revisi gramasi tanpa merusak histori masa lalu.
                        </p>
                    @endif
                </div>

                <!-- AUDIT TRAIL CARD -->
                <div class="bg-white border border-[#DCE5DD] rounded-xl shadow-2xs p-5 space-y-3 text-xs text-[#53645B]">
                    <h3 class="font-bold text-[#172923] pb-2 border-b border-[#DCE5DD]">
                        Jejak Audit &amp; Integritas Data
                    </h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span>Disusun oleh:</span>
                            <strong class="text-[#172923]">{{ $menu->creator?->name ?? 'Staf Gizi' }}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span>Waktu pembuatan:</span>
                            <span class="text-[#172923]">{{ $menu->created_at->translatedFormat('d M Y, H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Pengesah (Approver):</span>
                            <strong class="text-[#172923]">{{ $menu->approver?->name ?? 'Belum disahkan' }}</strong>
                        </div>
                        @if ($menu->approved_at)
                            <div class="flex justify-between">
                                <span>Waktu pengesahan:</span>
                                <span class="text-[#172923]">{{ $menu->approved_at->translatedFormat('d M Y, H:i') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-shell>
