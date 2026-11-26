<x-app-shell title="Dossier Menu {{ $menu->menu_code }} v{{ $menu->version }}">
    <div class="space-y-5">
        <header class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <a href="{{ route('nutrition.menus.index') }}" class="text-xs font-semibold text-[#285F80] hover:underline inline-flex items-center gap-1 mb-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Katalog Menu &amp; Gizi</span>
                </a>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="page-title">{{ $menu->name }}</h1>
                    <span class="px-2 py-0.5 rounded-md text-xs font-mono font-bold bg-[#FAFBF9] text-[#285F80] border border-[#DCE5DD]">v{{ $menu->version }}</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-xs font-semibold border {{ $menu->status->badgeClasses() }}">
                        <span>{{ $menu->status->label() }}</span>
                    </span>
                </div>
                <p class="text-xs text-[#53645B] mt-1 font-mono">{{ $menu->menu_code }} • {{ $menu->sppg?->name ?? 'SPPG' }}</p>
            </div>

            @if ($versionHistory->count() > 1)
                <div class="flex items-center gap-1.5 overflow-x-auto">
                    @foreach ($versionHistory as $v)
                        <a href="{{ route('nutrition.menus.show', $v) }}"
                           class="min-h-[38px] px-3 py-1.5 text-xs font-mono rounded-xl inline-flex items-center border {{ $v->id === $menu->id ? 'bg-[#285F80] text-white border-[#285F80] font-bold' : 'bg-white text-[#53645B] border-[#DCE5DD] hover:text-[#172923]' }}">
                            v{{ $v->version }}
                        </a>
                    @endforeach
                </div>
            @endif
        </header>

        <!-- Governance Notice -->
        @if ($menu->isActive())
            <div role="status" class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-[#1B573E] text-xs leading-relaxed">
                <strong>Versi Menu Aktif &amp; Terkunci:</strong> Disahkan oleh {{ $menu->approver?->name ?? 'Pengawas Gizi' }} pada {{ $menu->approved_at?->translatedFormat('d M Y, H:i') }} WIB. Untuk mengubah gramasi, gunakan tombol <strong>Buat Versi Baru</strong>.
            </div>
        @elseif ($menu->isDraft() && ! $isComplete)
            <div role="alert" class="p-4 rounded-xl bg-[#FEF7EC] border border-[#F7D7A3] text-[#885D1C] text-xs space-y-1.5">
                <p class="font-bold text-[#172923]">Draft Belum Memenuhi Syarat Aktivasi</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($violations as $violation)
                        <li>{{ $violation }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
            <!-- Left: Ingredients & Add Form (8 cols) -->
            <div class="lg:col-span-8 space-y-5">
                <x-card title="Komposisi Bahan &amp; Takaran Gizi" badge="{{ $items->count() }} Bahan">
                    @if ($items->isEmpty())
                        <p class="py-6 text-center text-xs text-[#53645B]">Belum ada komponen makanan. Tambahkan bahan melalui formulir di bawah.</p>
                    @else
                        <!-- Desktop Table -->
                        <div class="hidden md:block -mx-6 overflow-x-auto">
                            <table class="ledger-table text-xs">
                                <thead>
                                    <tr>
                                        <th>Komponen</th>
                                        <th class="text-right">Berat</th>
                                        <th class="text-right">Kalori</th>
                                        <th class="text-right">P / K / L / S</th>
                                        <th>Sumber Gizi</th>
                                        @if ($menu->isDraft())
                                            <th class="text-center">Aksi</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $item)
                                        <tr>
                                            <td>
                                                <span class="font-semibold text-[#172923] block">{{ $item->item_name }}</span>
                                                @if ($item->notes)
                                                    <span class="text-[11px] text-[#53645B] block">{{ $item->notes }}</span>
                                                @endif
                                            </td>
                                            <td class="text-right font-mono tabular-nums">{{ number_format($item->serving_weight_g, 1) }}g</td>
                                            <td class="text-right font-mono font-bold tabular-nums">
                                                {{ $item->energy_kcal !== null ? number_format($item->energy_kcal, 1) . ' kkal' : 'Kosong' }}
                                            </td>
                                            <td class="text-right font-mono text-[11px] text-[#53645B] tabular-nums">
                                                {{ $item->protein_g !== null ? number_format($item->protein_g, 1) : '-' }} /
                                                {{ $item->carbohydrate_g !== null ? number_format($item->carbohydrate_g, 1) : '-' }} /
                                                {{ $item->fat_g !== null ? number_format($item->fat_g, 1) : '-' }} /
                                                {{ $item->fiber_g !== null ? number_format($item->fiber_g, 1) : '-' }}g
                                            </td>
                                            <td class="text-[#53645B]">
                                                @if ($item->nutrition_source)
                                                    <span class="truncate block max-w-[150px]" title="{{ $item->nutrition_source }}">{{ $item->nutrition_source }}</span>
                                                @else
                                                    <span class="text-[#9B3232] font-semibold">Wajib Diisi</span>
                                                @endif
                                            </td>
                                            @if ($menu->isDraft())
                                                <td class="text-center">
                                                    <form method="POST" action="{{ route('nutrition.menus.items.destroy', [$menu, $item]) }}"
                                                          onsubmit="return confirm('Hapus komponen ini dari draft menu?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="min-h-[36px] px-2 text-xs font-semibold text-[#9B3232] hover:bg-[#FDF2F2] rounded-lg cursor-pointer">
                                                            Hapus
                                                        </button>
                                                    </form>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Ingredient Cards -->
                        <div class="md:hidden divide-y divide-[#E7ECE8] -my-1">
                            @foreach ($items as $item)
                                <div class="py-3.5 space-y-2">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <span class="text-sm font-bold text-[#172923] block">{{ $item->item_name }}</span>
                                            <span class="text-[11px] text-[#53645B] font-mono">{{ number_format($item->serving_weight_g, 1) }}g saji</span>
                                        </div>
                                        <span class="font-mono text-xs font-bold text-[#285F80] tabular-nums">
                                            {{ $item->energy_kcal !== null ? number_format($item->energy_kcal, 1) . ' kkal' : 'Belum lengkap' }}
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-between text-[11px] text-[#53645B] tabular-nums">
                                        <span>
                                            P {{ $item->protein_g ?? '-' }}g • K {{ $item->carbohydrate_g ?? '-' }}g • L {{ $item->fat_g ?? '-' }}g • S {{ $item->fiber_g ?? '-' }}g
                                        </span>
                                        @if ($menu->isDraft())
                                            <form method="POST" action="{{ route('nutrition.menus.items.destroy', [$menu, $item]) }}"
                                                  onsubmit="return confirm('Hapus komponen ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="min-h-[36px] px-2.5 text-xs font-semibold text-[#9B3232] bg-[#FDF2F2] rounded-lg cursor-pointer">
                                                    Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>

                @if ($menu->isDraft())
                    <x-card title="Tambah Komponen Bahan Makanan" subtitle="Masukkan gramasi saji dan nilai zat gizi terukur.">
                        <form method="POST" action="{{ route('nutrition.menus.items.store', $menu) }}" class="space-y-4">
                            @csrf

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="sm:col-span-2">
                                    <label for="item_name" class="block text-xs font-semibold text-[#172923] mb-1">
                                        Nama Komponen Bahan <span class="text-[#9B3232]">*</span>
                                    </label>
                                    <input type="text"
                                           id="item_name"
                                           name="item_name"
                                           value="{{ old('item_name') }}"
                                           required
                                           placeholder="Contoh: Dada Ayam Panggang"
                                           class="w-full min-h-[44px] px-3 py-2 text-xs bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl text-[#172923]">
                                </div>

                                <div>
                                    <label for="serving_weight_g" class="block text-xs font-semibold text-[#172923] mb-1">
                                        Berat Saji (g) <span class="text-[#9B3232]">*</span>
                                    </label>
                                    <input type="number"
                                           step="0.01"
                                           min="0.01"
                                           id="serving_weight_g"
                                           name="serving_weight_g"
                                           value="{{ old('serving_weight_g') }}"
                                           required
                                           placeholder="150"
                                           class="w-full min-h-[44px] px-3 py-2 text-xs bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl text-[#172923] font-mono tabular-nums">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5">
                                <div>
                                    <label for="energy_kcal" class="block text-[11px] font-semibold text-[#172923] mb-1">Energi (kkal)</label>
                                    <input type="number" step="0.01" min="0" id="energy_kcal" name="energy_kcal" value="{{ old('energy_kcal') }}" class="w-full min-h-[44px] px-2.5 py-2 text-xs bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl font-mono tabular-nums">
                                </div>
                                <div>
                                    <label for="protein_g" class="block text-[11px] font-semibold text-[#172923] mb-1">Protein (g)</label>
                                    <input type="number" step="0.01" min="0" id="protein_g" name="protein_g" value="{{ old('protein_g') }}" class="w-full min-h-[44px] px-2.5 py-2 text-xs bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl font-mono tabular-nums">
                                </div>
                                <div>
                                    <label for="carbohydrate_g" class="block text-[11px] font-semibold text-[#172923] mb-1">Karbo (g)</label>
                                    <input type="number" step="0.01" min="0" id="carbohydrate_g" name="carbohydrate_g" value="{{ old('carbohydrate_g') }}" class="w-full min-h-[44px] px-2.5 py-2 text-xs bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl font-mono tabular-nums">
                                </div>
                                <div>
                                    <label for="fat_g" class="block text-[11px] font-semibold text-[#172923] mb-1">Lemak (g)</label>
                                    <input type="number" step="0.01" min="0" id="fat_g" name="fat_g" value="{{ old('fat_g') }}" class="w-full min-h-[44px] px-2.5 py-2 text-xs bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl font-mono tabular-nums">
                                </div>
                                <div class="col-span-2 sm:col-span-1">
                                    <label for="fiber_g" class="block text-[11px] font-semibold text-[#172923] mb-1">Serat (g)</label>
                                    <input type="number" step="0.01" min="0" id="fiber_g" name="fiber_g" value="{{ old('fiber_g') }}" class="w-full min-h-[44px] px-2.5 py-2 text-xs bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl font-mono tabular-nums">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label for="nutrition_source" class="block text-xs font-semibold text-[#172923] mb-1">
                                        Sumber Rujukan Gizi <span class="text-[#9B3232]">*</span>
                                    </label>
                                    <input type="text"
                                           id="nutrition_source"
                                           name="nutrition_source"
                                           value="{{ old('nutrition_source') }}"
                                           placeholder="TKPI 2019"
                                           class="w-full min-h-[44px] px-3 py-2 text-xs bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl text-[#172923]">
                                </div>

                                <div>
                                    <label for="notes" class="block text-xs font-semibold text-[#172923] mb-1">Catatan Pengolahan</label>
                                    <input type="text"
                                           id="notes"
                                           name="notes"
                                           value="{{ old('notes') }}"
                                           placeholder="Opsional"
                                           class="w-full min-h-[44px] px-3 py-2 text-xs bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl text-[#172923]">
                                </div>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button type="submit"
                                        class="w-full sm:w-auto min-h-[44px] px-5 py-2 bg-[#285F80] hover:bg-[#1E4861] text-white text-xs font-semibold rounded-xl cursor-pointer smooth-transition">
                                    Tambahkan ke Menu
                                </button>
                            </div>
                        </form>
                    </x-card>
                @endif
            </div>

            <!-- Right: Totals & Governance (4 cols) -->
            <div class="lg:col-span-4 space-y-5">
                <x-card title="Total Zat Gizi per Porsi" badge="{{ number_format($totalWeight, 0) }}g">
                    <div class="space-y-4">
                        <div class="flex items-baseline justify-between">
                            <span class="text-xs font-semibold text-[#53645B]">Energi Total</span>
                            @if ($totalEnergy !== null)
                                <span class="text-2xl font-bold text-[#172923] tabular-nums">{{ number_format($totalEnergy, 0) }} <span class="text-xs font-normal text-[#53645B]">kkal</span></span>
                            @else
                                <span class="text-xs font-semibold text-[#885D1C]">Belum Lengkap</span>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-2.5 text-xs">
                            <div class="inset-surface p-3">
                                <span class="eyebrow-label block">Protein</span>
                                <span class="text-base font-bold text-[#172923] tabular-nums mt-1 block">{{ $totalProtein !== null ? number_format($totalProtein, 1) . ' g' : '-' }}</span>
                            </div>
                            <div class="inset-surface p-3">
                                <span class="eyebrow-label block">Karbohidrat</span>
                                <span class="text-base font-bold text-[#172923] tabular-nums mt-1 block">{{ $totalCarbs !== null ? number_format($totalCarbs, 1) . ' g' : '-' }}</span>
                            </div>
                            <div class="inset-surface p-3">
                                <span class="eyebrow-label block">Lemak</span>
                                <span class="text-base font-bold text-[#172923] tabular-nums mt-1 block">{{ $totalFat !== null ? number_format($totalFat, 1) . ' g' : '-' }}</span>
                            </div>
                            <div class="inset-surface p-3">
                                <span class="eyebrow-label block">Serat</span>
                                <span class="text-base font-bold text-[#172923] tabular-nums mt-1 block">{{ $totalFiber !== null ? number_format($totalFiber, 1) . ' g' : '-' }}</span>
                            </div>
                        </div>
                    </div>
                </x-card>

                <x-card title="Status &amp; Pengesahan">
                    <div class="space-y-3">
                        @if ($menu->isDraft())
                            @if ($isComplete)
                                <form method="POST" action="{{ route('nutrition.menus.activate', $menu) }}"
                                      onsubmit="return confirm('Sahkan dan aktifkan versi menu ini?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="w-full min-h-[46px] px-4 py-2.5 bg-[#1C6654] hover:bg-[#164D42] text-white text-xs sm:text-sm font-bold rounded-xl cursor-pointer smooth-transition">
                                        Sahkan &amp; Aktifkan Versi Menu
                                    </button>
                                </form>
                            @else
                                <button type="button" disabled class="w-full min-h-[44px] px-4 py-2.5 bg-[#EDF2ED] text-[#53645B] text-xs font-semibold rounded-xl cursor-not-allowed">
                                    Lengkapi Data Gizi untuk Aktivasi
                                </button>
                            @endif

                            <a href="{{ route('nutrition.menus.edit', $menu) }}"
                               class="w-full min-h-[42px] px-4 py-2 text-xs font-semibold text-[#285F80] bg-[#F0F6FA] border border-[#C2DBE8] rounded-xl inline-flex items-center justify-center">
                                Sunting Metadata Draft
                            </a>
                        @elseif ($menu->isActive())
                            <form method="POST" action="{{ route('nutrition.menus.new-version', $menu) }}"
                                  onsubmit="return confirm('Buat cabang versi baru (v{{ $menu->version + 1 }}) dari menu ini?');">
                                @csrf
                                <button type="submit"
                                        class="w-full min-h-[46px] px-4 py-2.5 bg-[#285F80] hover:bg-[#1E4861] text-white text-xs sm:text-sm font-bold rounded-xl cursor-pointer smooth-transition">
                                    Buat Versi Baru (v{{ $menu->version + 1 }})
                                </button>
                            </form>
                        @endif

                        <div class="pt-3 border-t border-[#E7ECE8] space-y-1.5 text-xs text-[#53645B]">
                            <div class="flex justify-between">
                                <span>Penyusun:</span>
                                <strong class="text-[#172923]">{{ $menu->creator?->name ?? 'Staf Gizi' }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Pengesah:</span>
                                <strong class="text-[#172923]">{{ $menu->approver?->name ?? 'Belum disahkan' }}</strong>
                            </div>
                        </div>
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</x-app-shell>
