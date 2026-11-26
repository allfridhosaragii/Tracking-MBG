<x-app-shell title="Perencanaan Produksi & Alokasi">
    <div class="space-y-5">
        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <span class="eyebrow-label text-[#1C6654]">Kepala SPPG • {{ $sppg->code }}</span>
                    @if ($active_plan)
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-semibold border {{ $active_plan->status->badgeClasses() }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $active_plan->status->dotClasses() }}" aria-hidden="true"></span>
                            <span>{{ $active_plan->status->label() }}</span>
                        </span>
                    @endif
                </div>
                <h1 class="page-title">Perencanaan Produksi &amp; Alokasi Dapur</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-1">{{ $sppg->name }} • Penetapan target masak resmi dari konfirmasi porsi sekolah.</p>
            </div>

            <a href="{{ route('head.portions.index', ['date' => $service_date]) }}"
               class="min-h-[44px] px-3.5 py-2 rounded-xl bg-white hover:bg-[#FAFBF9] text-xs font-semibold text-[#172923] border border-[#DCE5DD] inline-flex items-center gap-1.5 shrink-0 smooth-transition">
                <span>Monitoring Porsi</span>
            </a>
        </header>

        <!-- Date Bar -->
        <section aria-label="Navigasi Tanggal Layanan" class="luxury-surface p-3.5 sm:p-4">
            <form method="GET" action="{{ route('head.production.index') }}" class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <label for="date-picker" class="text-xs font-semibold text-[#53645B] shrink-0">Tanggal:</label>
                    <input type="date"
                           id="date-picker"
                           name="date"
                           value="{{ $service_date }}"
                           class="min-h-[44px] min-w-0 flex-1 sm:flex-initial px-3 py-2 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] text-xs sm:text-sm font-medium text-[#172923]">
                    @if (request('menu_id'))
                        <input type="hidden" name="menu_id" value="{{ request('menu_id') }}">
                    @endif
                    <button type="submit"
                            class="min-h-[44px] px-4 py-2 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs font-semibold cursor-pointer smooth-transition shrink-0">
                        Pilih Tanggal
                    </button>
                </div>

                @php
                    $todayStr = \Carbon\Carbon::today('Asia/Jakarta')->format('Y-m-d');
                    $tomorrowStr = \Carbon\Carbon::tomorrow('Asia/Jakarta')->format('Y-m-d');
                    $dayAfterStr = \Carbon\Carbon::today('Asia/Jakarta')->addDays(2)->format('Y-m-d');
                @endphp
                <div class="flex flex-wrap items-center gap-1.5">
                    <a href="{{ route('head.production.index', ['date' => $todayStr]) }}"
                       class="min-h-[44px] px-3 py-1.5 rounded-xl text-xs font-semibold inline-flex items-center border whitespace-nowrap {{ $service_date === $todayStr ? 'bg-[#1C6654] text-white border-[#1C6654]' : 'bg-[#FAFBF9] text-[#172923] border-[#DCE5DD] hover:bg-white' }}">
                        Hari Ini
                    </a>
                    <a href="{{ route('head.production.index', ['date' => $tomorrowStr]) }}"
                       class="min-h-[44px] px-3 py-1.5 rounded-xl text-xs font-semibold inline-flex items-center border whitespace-nowrap {{ $service_date === $tomorrowStr ? 'bg-[#1C6654] text-white border-[#1C6654]' : 'bg-[#FAFBF9] text-[#172923] border-[#DCE5DD] hover:bg-white' }}">
                        Besok (H-1)
                    </a>
                    <a href="{{ route('head.production.index', ['date' => $dayAfterStr]) }}"
                       class="min-h-[44px] px-3 py-1.5 rounded-xl text-xs font-semibold inline-flex items-center border whitespace-nowrap {{ $service_date === $dayAfterStr ? 'bg-[#1C6654] text-white border-[#1C6654]' : 'bg-[#FAFBF9] text-[#172923] border-[#DCE5DD] hover:bg-white' }}">
                        Lusa
                    </a>
                </div>
            </form>
        </section>

        <!-- KPI Strip -->
        <section aria-label="Ringkasan Kapasitas dan Target Produksi" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="luxury-surface p-4">
                <span class="eyebrow-label block">Permintaan Sah</span>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl sm:text-3xl font-bold text-[#172923] tabular-nums">{{ number_format($total_confirmed_eligible) }}</span>
                    <span class="text-xs text-[#53645B]">porsi</span>
                </div>
            </div>

            <div class="luxury-surface p-4">
                <span class="eyebrow-label block">Alokasi Rencana</span>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl sm:text-3xl font-bold text-[#1C6654] tabular-nums">{{ number_format($total_allocated_portions) }}</span>
                    <span class="text-xs text-[#53645B]">porsi</span>
                </div>
            </div>

            <div class="luxury-surface p-4">
                <div class="flex items-center justify-between">
                    <span class="eyebrow-label">Kapasitas Dapur</span>
                    <span class="text-[11px] font-mono font-bold text-[#172923]">{{ $capacity_percent }}%</span>
                </div>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl sm:text-3xl font-bold text-[#172923] tabular-nums">{{ number_format($kitchen_capacity) }}</span>
                    <span class="text-xs text-[#53645B]">maks</span>
                </div>
                <div class="w-full bg-[#EDF2ED] h-1.5 rounded-full overflow-hidden mt-2">
                    <div class="h-full rounded-full {{ $is_over_capacity ? 'bg-[#9B3232]' : 'bg-[#1C6654]' }}" style="width: {{ min(100, $capacity_percent) }}%"></div>
                </div>
            </div>

            <div class="rounded-2xl border p-4 subtle-shadow {{ $can_finalize ? 'bg-[#EDF7F2] border-[#BCE2D0] text-[#1B573E]' : ($active_plan?->isFinalized() ? 'bg-[#F0F6FA] border-[#C2DBE8] text-[#285F80]' : 'bg-[#FEF7EC] border-[#F7D7A3] text-[#885D1C]') }}">
                <span class="eyebrow-label block opacity-80">Status Rencana</span>
                <div class="mt-1.5 text-sm sm:text-base font-bold leading-snug">
                    @if ($active_plan?->isFinalized())
                        Rencana Difinalisasi
                    @elseif ($can_finalize)
                        Siap Difinalisasi
                    @else
                        Menunggu Kelengkapan
                    @endif
                </div>
            </div>
        </section>

        @if ($has_blockers && (! $active_plan || ! $active_plan->isFinalized()))
            <div role="alert" class="p-4 rounded-xl bg-[#FEF7EC] border border-[#F7D7A3] text-[#885D1C] flex items-start gap-3">
                <svg class="w-5 h-5 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="space-y-1 text-xs">
                    <p class="font-bold text-sm text-[#172923]">Finalisasi Rencana Produksi Tertahan</p>
                    <ul class="space-y-0.5 list-disc list-inside">
                        @foreach ($blockers as $blocker)
                            <li>{{ $blocker }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
            <!-- Allocation Matrix (8 cols) -->
            <div class="lg:col-span-8">
                <x-card title="Matriks Alokasi Sekolah Penerima" subtitle="Layanan {{ $parsed_date->translatedFormat('l, d F Y') }}" badge="{{ count($allocation_rows) }} Sekolah">
                    @if (count($allocation_rows) === 0)
                        <p class="py-6 text-center text-xs text-[#53645B]">Tidak ada sekolah terdaftar untuk unit pelayanan ini.</p>
                    @else
                        <!-- Desktop Ledger Table -->
                        <div class="hidden md:block -mx-6 overflow-x-auto">
                            <table class="ledger-table text-xs">
                                <thead>
                                    <tr>
                                        <th>Sekolah Penerima</th>
                                        <th class="text-right">Data Pokok</th>
                                        <th>Status</th>
                                        <th class="text-right">Permintaan</th>
                                        <th class="text-right">Alokasi</th>
                                        <th class="text-center">Tindakan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($allocation_rows as $row)
                                        <tr class="{{ ! $row['is_eligible'] ? 'bg-[#FAFBF9]' : '' }}">
                                            <td>
                                                <span class="font-semibold text-[#172923] block">{{ $row['school']->name }}</span>
                                                <span class="text-[11px] text-[#53645B] font-mono">{{ $row['school']->school_code }}</span>
                                            </td>
                                            <td class="text-right font-mono tabular-nums text-[#53645B]">{{ number_format($row['school']->registered_beneficiaries) }}</td>
                                            <td>
                                                @if ($row['request'])
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $row['request']->status->badgeClasses() }}">
                                                        <span>{{ $row['request']->status->label() }}</span>
                                                    </span>
                                                    @if ($row['has_exception'])
                                                        <span class="block text-[10px] text-[#885D1C] font-semibold mt-0.5">Melebihi Kuota Siswa</span>
                                                    @endif
                                                @else
                                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-[#FAFBF9] text-[#53645B] border border-[#DCE5DD]">Belum Dibuat</span>
                                                @endif
                                            </td>
                                            <td class="text-right font-mono tabular-nums font-bold {{ $row['is_eligible'] ? 'text-[#172923]' : 'text-[#53645B]' }}">
                                                @if ($row['is_eligible'])
                                                    {{ number_format($row['confirmed_portions']) }} porsi
                                                @elseif ($row['is_revised'])
                                                    <span class="text-[#885D1C]">{{ number_format($row['request']->revised_portions) }} (Revisi)</span>
                                                @elseif ($row['is_pending'])
                                                    <span class="font-normal text-[11px]">Menunggu PIC</span>
                                                @else
                                                    <span class="text-[#9B3232] text-[11px]">Batal</span>
                                                @endif
                                            </td>
                                            <td class="text-right font-mono tabular-nums font-bold text-[#1C6654]">
                                                {{ number_format($row['allocated_portions']) }} porsi
                                            </td>
                                            <td class="text-center">
                                                @if ($row['is_revised'] && (! $active_plan || ! $active_plan->isFinalized()))
                                                    <div class="inline-flex items-center gap-1">
                                                        <form method="POST" action="{{ route('head.production.revisions.approve', $row['request']) }}">
                                                            @csrf
                                                            <button type="submit"
                                                                    onclick="return confirm('Sahkan revisi porsi sebesar {{ number_format($row['request']->revised_portions) }} untuk {{ $row['school']->name }}?');"
                                                                    class="min-h-[38px] px-2.5 py-1 bg-[#1C6654] hover:bg-[#164D42] text-white rounded-lg text-[11px] font-semibold cursor-pointer">
                                                                Sahkan
                                                            </button>
                                                        </form>
                                                        <details class="relative inline-block text-left">
                                                            <summary class="min-h-[38px] px-2.5 py-1 bg-[#FEF7EC] text-[#885D1C] border border-[#F7D7A3] rounded-lg text-[11px] font-semibold cursor-pointer inline-flex items-center">
                                                                Tolak
                                                            </summary>
                                                            <div class="absolute right-0 mt-1 w-64 p-3 bg-white border border-[#DCE5DD] rounded-xl floating-shadow z-20 text-left">
                                                                <form method="POST" action="{{ route('head.production.revisions.reject', $row['request']) }}" class="space-y-2">
                                                                    @csrf
                                                                    <label class="block text-[11px] font-semibold text-[#172923]">Alasan Penolakan</label>
                                                                    <input type="text" name="reason" required placeholder="Bahan baku sudah disiapkan" class="w-full min-h-[38px] px-2.5 py-1 text-xs border border-[#DCE5DD] rounded-lg">
                                                                    <button type="submit" class="w-full min-h-[38px] bg-[#9B3232] text-white rounded-lg text-xs font-semibold cursor-pointer">
                                                                        Tolak Revisi
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </details>
                                                    </div>
                                                @elseif ($row['has_exception'] && (! $active_plan || ! $active_plan->isFinalized()))
                                                    <form method="POST" action="{{ route('head.production.exceptions.approve', $row['request']) }}">
                                                        @csrf
                                                        <button type="submit"
                                                                onclick="return confirm('Sahkan permintaan porsi yang melebihi kuota siswa untuk {{ $row['school']->name }}?');"
                                                                class="min-h-[38px] px-2.5 py-1 bg-[#885D1C] text-white rounded-lg text-[11px] font-semibold cursor-pointer">
                                                            Sahkan Kuota
                                                        </button>
                                                    </form>
                                                @elseif ($row['is_eligible'])
                                                    <span class="text-[11px] text-[#21684B] font-semibold">Memenuhi Syarat</span>
                                                @else
                                                    <span class="text-[11px] text-[#53645B]">Belum Siap</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Minimalist Allocation Cards -->
                        <div class="md:hidden divide-y divide-[#E7ECE8] -my-1">
                            @foreach ($allocation_rows as $row)
                                <div class="py-3.5 space-y-2.5">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <span class="text-sm font-bold text-[#172923] block leading-snug">{{ $row['school']->name }}</span>
                                            <span class="text-[11px] font-mono text-[#53645B]">{{ $row['school']->school_code }} • Pokok {{ number_format($row['school']->registered_beneficiaries) }}</span>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="text-base font-bold text-[#1C6654] tabular-nums block">{{ number_format($row['allocated_portions']) }}</span>
                                            <span class="text-[10px] text-[#53645B]">alokasi</span>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                                        @if ($row['request'])
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $row['request']->status->badgeClasses() }}">
                                                {{ $row['request']->status->label() }}
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-[#FAFBF9] text-[#53645B] border border-[#DCE5DD]">Belum Dibuat</span>
                                        @endif

                                        <span class="text-[11px] font-mono text-[#53645B]">
                                            @if ($row['is_eligible'])
                                                Permintaan: {{ number_format($row['confirmed_portions']) }}
                                            @elseif ($row['is_revised'])
                                                Revisi: {{ number_format($row['request']->revised_portions) }}
                                            @elseif ($row['is_pending'])
                                                Menunggu PIC
                                            @else
                                                Dibatalkan
                                            @endif
                                        </span>
                                    </div>

                                    @if ($row['is_revised'] && (! $active_plan || ! $active_plan->isFinalized()))
                                        <div class="space-y-2 pt-1">
                                            <form method="POST" action="{{ route('head.production.revisions.approve', $row['request']) }}">
                                                @csrf
                                                <button type="submit"
                                                        onclick="return confirm('Sahkan revisi porsi untuk {{ $row['school']->name }}?');"
                                                        class="w-full min-h-[44px] bg-[#1C6654] text-white rounded-xl text-xs font-semibold cursor-pointer">
                                                    Sahkan Revisi ({{ number_format($row['request']->revised_portions) }} porsi)
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('head.production.revisions.reject', $row['request']) }}" class="flex gap-2">
                                                @csrf
                                                <input type="text" name="reason" required placeholder="Alasan penolakan..." class="flex-1 min-h-[44px] px-3 text-xs border border-[#DCE5DD] rounded-xl bg-[#FAFBF9]">
                                                <button type="submit" class="min-h-[44px] px-3.5 bg-[#FDF2F2] text-[#9B3232] border border-[#F5C2C2] rounded-xl text-xs font-semibold cursor-pointer">
                                                    Tolak
                                                </button>
                                            </form>
                                        </div>
                                    @elseif ($row['has_exception'] && (! $active_plan || ! $active_plan->isFinalized()))
                                        <form method="POST" action="{{ route('head.production.exceptions.approve', $row['request']) }}" class="pt-1">
                                            @csrf
                                            <button type="submit"
                                                    onclick="return confirm('Sahkan kuota tambahan untuk {{ $row['school']->name }}?');"
                                                    class="w-full min-h-[44px] bg-[#885D1C] text-white rounded-xl text-xs font-semibold cursor-pointer">
                                                Sahkan Kuota Melebihi Pokok
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 pt-3.5 border-t border-[#E7ECE8] flex items-center justify-between text-xs">
                            <span class="text-[#53645B] font-medium">Total Alokasi Direncanakan</span>
                            <span class="text-base font-mono font-bold text-[#1C6654] tabular-nums">{{ number_format($total_allocated_portions) }} porsi</span>
                        </div>
                    @endif
                </x-card>
            </div>

            <!-- Menu & Finalization Panel (4 cols) -->
            <div class="lg:col-span-4 space-y-5">
                <x-card title="Menu Hidangan Aktif" subtitle="Rujukan resep dan gramasi produksi">
                    @if ($active_menus->isEmpty())
                        <div class="p-3.5 rounded-xl bg-[#FEF7EC] border border-[#F7D7A3] text-xs text-[#885D1C]">
                            Belum ada menu berstatus Aktif. Pengawas Gizi perlu mengaktifkan menu terlebih dahulu.
                        </div>
                    @else
                        <div class="space-y-3.5">
                            @if (! $active_plan || ! $active_plan->isFinalized())
                                <form method="GET" action="{{ route('head.production.index') }}" class="space-y-1.5">
                                    <input type="hidden" name="date" value="{{ $service_date }}">
                                    <label for="menu_select" class="block text-xs font-semibold text-[#172923]">Pilih Menu Aktif</label>
                                    <select id="menu_select"
                                            name="menu_id"
                                            onchange="this.form.submit()"
                                            class="w-full min-h-[44px] px-3 py-2 text-xs bg-[#FAFBF9] border border-[#DCE5DD] rounded-xl text-[#172923] font-medium">
                                        @foreach ($active_menus as $m)
                                            <option value="{{ $m->id }}" {{ $selected_menu?->id === $m->id ? 'selected' : '' }}>
                                                {{ $m->name }} (v{{ $m->version }})
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            @endif

                            @if ($selected_menu)
                                <div class="inset-surface p-3.5 space-y-3">
                                    <div>
                                        <span class="text-[11px] font-mono font-semibold text-[#1C6654] block">{{ $selected_menu->menu_code }} • v{{ $selected_menu->version }}</span>
                                        <h3 class="text-sm font-bold text-[#172923] mt-0.5 leading-snug">{{ $selected_menu->name }}</h3>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div class="bg-white p-2.5 rounded-lg border border-[#E7ECE8]">
                                            <span class="text-[10px] text-[#53645B] block">Energi</span>
                                            <span class="font-mono font-bold text-[#172923] tabular-nums">{{ number_format($selected_menu->totalEnergy() ?? 0, 0) }} kkal</span>
                                        </div>
                                        <div class="bg-white p-2.5 rounded-lg border border-[#E7ECE8]">
                                            <span class="text-[10px] text-[#53645B] block">Protein</span>
                                            <span class="font-mono font-bold text-[#172923] tabular-nums">{{ number_format($selected_menu->totalProtein() ?? 0, 1) }} g</span>
                                        </div>
                                    </div>

                                    <ul class="text-xs text-[#172923] space-y-1 pt-2 border-t border-[#E7ECE8]">
                                        @foreach ($selected_menu->items as $item)
                                            <li class="flex items-center justify-between gap-2">
                                                <span class="truncate">{{ $item->item_name }}</span>
                                                <span class="font-mono text-[#53645B] shrink-0 tabular-nums">{{ number_format($item->serving_weight_g) }}g</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endif
                </x-card>

                <x-card title="Finalisasi Rencana Operasional" subtitle="Penguncian target resmi ke dapur SPPG">
                    @if ($active_plan && $active_plan->isFinalized())
                        <div class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-xs text-[#1B573E] space-y-2">
                            <p class="font-bold text-sm">Rencana Produksi Resmi Difinalisasi</p>
                            <p>Target: <strong>{{ number_format($active_plan->totalAllocatedPortions()) }} porsi</strong></p>
                            <p>Oleh: <strong>{{ $active_plan->finalizer?->name }}</strong> ({{ $active_plan->finalized_at?->translatedFormat('d M Y, H:i') }} WIB)</p>
                        </div>
                    @elseif (! $active_plan)
                        @if ($selected_menu)
                            <form method="POST" action="{{ route('head.production.plans.store') }}" class="space-y-3.5">
                                @csrf
                                <input type="hidden" name="service_date" value="{{ $service_date }}">
                                <input type="hidden" name="menu_id" value="{{ $selected_menu->id }}">
                                <div>
                                    <label for="plan_notes" class="block text-xs font-semibold text-[#172923] mb-1.5">Catatan Dapur (Opsional)</label>
                                    <textarea id="plan_notes"
                                              name="notes"
                                              rows="2"
                                              placeholder="Catatan shift atau persiapan bahan..."
                                              class="w-full px-3 py-2 text-xs bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl text-[#172923]"></textarea>
                                </div>

                                <button type="submit"
                                        class="w-full min-h-[44px] px-4 py-2.5 bg-[#1C6654] hover:bg-[#164D42] text-white text-xs font-semibold rounded-xl cursor-pointer smooth-transition">
                                    Buat Rencana Produksi Awal
                                </button>
                            </form>
                        @endif
                    @else
                        <form method="POST" action="{{ route('head.production.plans.finalize', $active_plan) }}" class="space-y-3.5">
                            @csrf
                            @foreach ($allocation_rows as $idx => $row)
                                @if ($row['is_eligible'])
                                    <input type="hidden" name="allocations[{{ $idx }}][school_id]" value="{{ $row['school']->id }}">
                                    <input type="hidden" name="allocations[{{ $idx }}][portion_request_id]" value="{{ $row['request']->id }}">
                                    <input type="hidden" name="allocations[{{ $idx }}][allocated_portions]" value="{{ $row['allocated_portions'] }}">
                                @endif
                            @endforeach

                            <p class="text-xs text-[#53645B] leading-relaxed">
                                Pengesahan ini mengunci seluruh permintaan porsi sekolah dan menerbitkan target <strong>{{ number_format($total_allocated_portions) }} porsi</strong> ke Asisten Lapangan.
                            </p>

                            <button type="submit"
                                    {{ ! $can_finalize ? 'disabled' : '' }}
                                    onclick="return confirm('Finalisasi rencana produksi ini? Tindakan ini mengunci alokasi sekolah secara resmi.');"
                                    class="w-full min-h-[46px] px-4 py-2.5 {{ $can_finalize ? 'bg-[#1C6654] hover:bg-[#164D42] text-white cursor-pointer' : 'bg-[#EDF2ED] text-[#53645B] cursor-not-allowed' }} text-xs font-bold rounded-xl smooth-transition">
                                Finalisasi Rencana Produksi
                            </button>
                        </form>
                    @endif
                </x-card>

                @if ($existing_plans->isNotEmpty())
                    <x-card title="Riwayat Rencana Tanggal Ini" compact>
                        <div class="divide-y divide-[#E7ECE8] text-xs -my-1">
                            @foreach ($existing_plans as $ep)
                                <div class="py-2.5 flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <span class="font-semibold text-[#172923] block truncate">{{ $ep->menu->name }}</span>
                                        <span class="text-[11px] text-[#53645B]">v{{ $ep->menu->version }} • {{ $ep->status->label() }}</span>
                                    </div>
                                    <span class="font-mono font-bold text-[#172923] shrink-0 tabular-nums">{{ number_format($ep->totalAllocatedPortions()) }} porsi</span>
                                </div>
                            @endforeach
                        </div>
                    </x-card>
                @endif
            </div>
        </div>
    </div>
</x-app-shell>
