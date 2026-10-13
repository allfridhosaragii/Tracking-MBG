<x-app-shell title="Perencanaan Produksi & Alokasi : Kepala SPPG">
    <!-- Header & Unit Identity -->
    <header class="mb-5 pb-4 border-b border-[#DCE5DD]">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                    <x-role-badge :role="auth()->user()->role" />
                    <span class="text-xs font-mono text-[#53645B]">SPPG: {{ $sppg->code }}</span>
                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-[#EDF7F2] text-[#1B573E] border border-[#BCE2D0]">
                        PBI-05 Perencanaan Produksi
                    </span>
                    @if ($active_plan)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $active_plan->status->badgeClasses() }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $active_plan->status->dotClasses() }}" aria-hidden="true"></span>
                            <span>{{ $active_plan->status->label() }}</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border bg-[#FAFBF9] text-[#53645B] border-[#DCE5DD]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#8E9B94]" aria-hidden="true"></span>
                            <span>Rencana Belum Dibuat</span>
                        </span>
                    @endif
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">
                    Perencanaan Produksi & Alokasi Dapur
                </h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">
                    Unit Pelayanan: <strong>{{ $sppg->name }}</strong> : Mengubah permintaan sekolah sah menjadi target produksi dapur resmi.
                </p>
            </div>

            <!-- View Switcher -->
            <div class="flex items-center gap-2 self-start sm:self-auto">
                <a href="{{ route('head.portions.index', ['date' => $service_date]) }}" 
                   class="min-h-[44px] px-3.5 py-2 rounded-xl bg-white hover:bg-[#FAFBF9] text-xs font-semibold text-[#53645B] hover:text-[#172923] border border-[#DCE5DD] inline-flex items-center gap-1.5 transition-colors">
                    <svg class="w-4 h-4 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Monitoring Kebutuhan Porsi</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Operational Date Navigator -->
    <section aria-label="Navigasi Tanggal Layanan" class="mb-6 bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
        <form method="GET" action="{{ route('head.production.index') }}" class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <label for="date-picker" class="block text-xs font-bold text-[#172923] uppercase tracking-wider mb-1">
                    Tanggal Layanan Operasional
                </label>
                <div class="flex items-center gap-2">
                    <input 
                        type="date" 
                        id="date-picker" 
                        name="date" 
                        value="{{ $service_date }}"
                        class="min-h-[44px] px-3.5 py-2 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] text-sm font-medium text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654] focus:border-[#1C6654]"
                    >
                    @if (request('menu_id'))
                        <input type="hidden" name="menu_id" value="{{ request('menu_id') }}">
                    @endif
                    <button 
                        type="submit" 
                        class="min-h-[44px] px-4 py-2 rounded-xl bg-[#1C6654] hover:bg-[#154D40] text-white text-xs font-bold transition-colors subtle-shadow focus:outline-none focus:ring-2 focus:ring-[#1C6654] focus:ring-offset-2">
                        Pilih Tanggal
                    </button>
                </div>
            </div>

            <!-- Quick Jump Shortcuts -->
            <div>
                <span class="block text-xs font-bold text-[#53645B] mb-1.5 md:text-right">Akses Cepat</span>
                <div class="flex flex-wrap items-center gap-2">
                    @php
                        $todayStr = \Carbon\Carbon::today('Asia/Jakarta')->format('Y-m-d');
                        $tomorrowStr = \Carbon\Carbon::tomorrow('Asia/Jakarta')->format('Y-m-d');
                        $dayAfterStr = \Carbon\Carbon::today('Asia/Jakarta')->addDays(2)->format('Y-m-d');
                    @endphp
                    <a href="{{ route('head.production.index', ['date' => $todayStr]) }}"
                       class="min-h-[44px] px-3.5 py-2 rounded-xl text-xs font-bold transition-colors inline-flex items-center border {{ $service_date === $todayStr ? 'bg-[#1C6654] text-white border-[#1C6654]' : 'bg-[#FAFBF9] text-[#172923] border-[#DCE5DD] hover:bg-white' }}">
                        Hari Ini
                    </a>
                    <a href="{{ route('head.production.index', ['date' => $tomorrowStr]) }}"
                       class="min-h-[44px] px-3.5 py-2 rounded-xl text-xs font-bold transition-colors inline-flex items-center border {{ $service_date === $tomorrowStr ? 'bg-[#1C6654] text-white border-[#1C6654]' : 'bg-[#FAFBF9] text-[#172923] border-[#DCE5DD] hover:bg-white' }}">
                        Besok (H-1)
                    </a>
                    <a href="{{ route('head.production.index', ['date' => $dayAfterStr]) }}"
                       class="min-h-[44px] px-3.5 py-2 rounded-xl text-xs font-bold transition-colors inline-flex items-center border {{ $service_date === $dayAfterStr ? 'bg-[#1C6654] text-white border-[#1C6654]' : 'bg-[#FAFBF9] text-[#172923] border-[#DCE5DD] hover:bg-white' }}">
                        Lusa (H-2)
                    </a>
                </div>
            </div>
        </form>
    </section>

    <!-- Key Metrics & Kitchen Capacity Cards -->
    <section aria-label="Ringkasan Kapasitas dan Target Produksi" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Metric 1: Kebutuhan Resmi Eligible -->
        <div class="bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-[#53645B] block mb-1">
                Kebutuhan Sah Sekolah
            </span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-extrabold text-[#172923] font-mono tabular-nums">
                    {{ number_format($total_confirmed_eligible) }}
                </span>
                <span class="text-xs text-[#53645B]">porsi</span>
            </div>
            <p class="text-[11px] text-[#53645B] mt-1.5">
                Akumulasi porsi dari sekolah dengan status Terkonfirmasi atau Terkunci resmi.
            </p>
        </div>

        <!-- Metric 2: Target Alokasi Rencana -->
        <div class="bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-[#53645B] block mb-1">
                Target Rencana Produksi
            </span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-extrabold text-[#1C6654] font-mono tabular-nums">
                    {{ number_format($total_allocated_portions) }}
                </span>
                <span class="text-xs text-[#53645B]">porsi rencana</span>
            </div>
            <p class="text-[11px] text-[#53645B] mt-1.5">
                Total komitmen porsi yang dimasukkan ke dalam rencana kerja SPPG.
            </p>
        </div>

        <!-- Metric 3: Kapasitas Dapur SPPG -->
        <div class="bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold uppercase tracking-wider text-[#53645B]">
                    Kapasitas Dapur SPPG
                </span>
                <span class="text-[11px] font-mono font-bold text-[#172923]">
                    {{ $capacity_percent }}%
                </span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-extrabold text-[#172923] font-mono tabular-nums">
                    {{ number_format($kitchen_capacity) }}
                </span>
                <span class="text-xs text-[#53645B]">maks/hari</span>
            </div>
            <!-- Capacity Utilization Bar -->
            <div class="w-full bg-[#EAEFEA] h-2 rounded-full overflow-hidden mt-2">
                <div class="h-full rounded-full transition-all duration-300 {{ $is_over_capacity ? 'bg-[#9B3232]' : 'bg-[#1C6654]' }}"
                     style="width: {{ min(100, $capacity_percent) }}%"></div>
            </div>
        </div>

        <!-- Metric 4: Status Kesiapan Finalisasi -->
        <div class="rounded-xl border p-4 subtle-shadow {{ $can_finalize ? 'bg-[#EDF7F2] border-[#BCE2D0] text-[#1B573E]' : ($active_plan?->isFinalized() ? 'bg-[#F0F5FA] border-[#BCD4E6] text-[#285F80]' : 'bg-[#FAF4EB] border-[#E8D7BE] text-[#885D1C]') }}">
            <span class="text-xs font-bold uppercase tracking-wider block mb-1">
                Kesiapan Rencana
            </span>
            <div class="flex items-center gap-2 mt-1">
                @if ($active_plan?->isFinalized())
                    <span class="text-base sm:text-lg font-extrabold">Rencana Difinalisasi</span>
                @elseif ($can_finalize)
                    <span class="text-base sm:text-lg font-extrabold">Siap Difinalisasi</span>
                @else
                    <span class="text-base sm:text-lg font-extrabold">Menunggu Kelengkapan</span>
                @endif
            </div>
            <p class="text-[11px] mt-1.5 opacity-90 leading-relaxed">
                @if ($active_plan?->isFinalized())
                    Target resmi disahkan pada {{ $active_plan->finalized_at?->format('H:i') }} WIB oleh {{ $active_plan->finalizer?->name }}.
                @elseif ($can_finalize)
                    Semua kebutuhan sekolah telah sah dan berada dalam batas kapasitas dapur.
                @else
                    Terdapat kondisi pembatas operasional yang perlu diselesaikan terlebih dahulu.
                @endif
            </p>
        </div>
    </section>

    <!-- Operational Blockers Alert (if any) -->
    @if ($has_blockers && (! $active_plan || ! $active_plan->isFinalized()))
        <div role="alert" class="mb-6 p-4 rounded-xl bg-[#FAF4EB] border border-[#E8D7BE] text-[#885D1C] space-y-2">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 mt-0.5 shrink-0 text-[#885D1C]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="space-y-1">
                    <p class="font-bold text-xs sm:text-sm text-[#885D1C]">
                        Perhatian: Finalisasi Rencana Produksi Tertahan
                    </p>
                    <ul class="text-xs space-y-1 list-disc list-inside">
                        @foreach ($blockers as $blocker)
                            <li>{{ $blocker }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Planning Workspace Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Left: School Allocation Ledger Matrix (8 cols on lg) -->
        <div class="lg:col-span-8 space-y-6">
            <div class="bg-white border border-[#DCE5DD] rounded-xl subtle-shadow overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-[#DCE5DD] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm sm:text-base font-bold text-[#172923]">
                            Matriks Alokasi Sekolah Penerima
                        </h2>
                        <p class="text-xs text-[#53645B] mt-0.5">
                            Layanan Tanggal: <strong>{{ $parsed_date->translatedFormat('l, d F Y') }}</strong>
                        </p>
                    </div>
                    <span class="text-xs font-mono font-bold text-[#1C6654] bg-[#EDF7F2] px-2.5 py-1 rounded border border-[#BCE2D0] self-start sm:self-auto">
                        Total Sekolah: {{ count($allocation_rows) }}
                    </span>
                </div>

                <!-- Matrix Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#F5F7F3] border-b border-[#DCE5DD] text-[#53645B] uppercase text-[10px] tracking-wider">
                            <tr>
                                <th scope="col" class="py-3 px-4 font-bold">Sekolah Penerima</th>
                                <th scope="col" class="py-3 px-3 font-bold text-right">Data Pokok</th>
                                <th scope="col" class="py-3 px-3 font-bold">Status Kebutuhan</th>
                                <th scope="col" class="py-3 px-3 font-bold text-right">Permintaan Sah</th>
                                <th scope="col" class="py-3 px-4 font-bold text-right">Alokasi Rencana</th>
                                <th scope="col" class="py-3 px-3 font-bold text-center">Aksi / Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#DCE5DD]">
                            @forelse ($allocation_rows as $row)
                                <tr class="hover:bg-[#FAFBF9] transition-colors {{ ! $row['is_eligible'] ? 'bg-[#FCFDFB]' : '' }}">
                                    <!-- School Name -->
                                    <td class="py-3 px-4">
                                        <span class="font-bold text-[#172923] block text-xs">{{ $row['school']->name }}</span>
                                        <span class="text-[10px] text-[#53645B] font-mono block">NPSN: {{ $row['school']->school_code }}</span>
                                    </td>

                                    <!-- Registered Count -->
                                    <td class="py-3 px-3 text-right font-mono tabular-nums text-[#53645B]">
                                        {{ number_format($row['school']->registered_beneficiaries) }}
                                    </td>

                                    <!-- Status Kebutuhan -->
                                    <td class="py-3 px-3">
                                        @if ($row['request'])
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold border {{ $row['request']->status->badgeClasses() }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $row['request']->status->dotClasses() }}"></span>
                                                <span>{{ $row['request']->status->label() }}</span>
                                            </span>
                                            @if ($row['has_exception'])
                                                <span class="block text-[10px] text-[#885D1C] font-semibold mt-0.5">
                                                    Melebihi Kuota Siswa
                                                </span>
                                            @endif
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-[#FAFBF9] text-[#53645B] border border-[#DCE5DD]">
                                                <span>Belum Dibuat</span>
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Permintaan Sah -->
                                    <td class="py-3 px-3 text-right font-mono tabular-nums font-bold {{ $row['is_eligible'] ? 'text-[#172923]' : 'text-[#8E9B94]' }}">
                                        @if ($row['is_eligible'])
                                            {{ number_format($row['confirmed_portions']) }} porsi
                                        @elseif ($row['is_revised'])
                                            <span class="text-[#885D1C]" title="Revisi menunggu keputusan Kepala SPPG">
                                                {{ number_format($row['request']->revised_portions) }} (Revisi)
                                            </span>
                                        @elseif ($row['is_pending'])
                                            <span class="text-[#8E9B94] italic font-normal text-[11px]">Menunggu PIC</span>
                                        @else
                                            <span class="text-[#9B3232] text-[11px]">Batal</span>
                                        @endif
                                    </td>

                                    <!-- Alokasi Rencana -->
                                    <td class="py-3 px-4 text-right font-mono tabular-nums font-extrabold text-xs {{ $row['allocated_portions'] > 0 ? 'text-[#1C6654]' : 'text-[#8E9B94]' }}">
                                        {{ number_format($row['allocated_portions']) }} porsi
                                    </td>

                                    <!-- Action / Quick Resolve -->
                                    <td class="py-3 px-3 text-center">
                                        @if ($row['is_revised'] && (! $active_plan || ! $active_plan->isFinalized()))
                                            <!-- Inline Revision Review Action -->
                                            <div class="flex items-center justify-center gap-1">
                                                <form method="POST" action="{{ route('head.production.revisions.approve', $row['request']) }}">
                                                    @csrf
                                                    <button type="submit" 
                                                            onclick="return confirm('Sahkan revisi porsi sebesar {{ number_format($row['request']->revised_portions) }} untuk {{ $row['school']->name }}?');" 
                                                            class="px-2 py-1 bg-[#1C6654] hover:bg-[#154D40] text-white rounded text-[10px] font-bold touch-target">
                                                        Sahkan
                                                    </button>
                                                </form>
                                                <details class="relative inline-block text-left">
                                                    <summary class="px-2 py-1 bg-[#FAF4EB] text-[#885D1C] border border-[#E8D7BE] rounded text-[10px] font-bold cursor-pointer touch-target">
                                                        Tolak
                                                    </summary>
                                                    <div class="absolute right-0 mt-1 w-64 p-3 bg-white border border-[#DCE5DD] rounded-xl shadow-lg z-20 text-left">
                                                        <form method="POST" action="{{ route('head.production.revisions.reject', $row['request']) }}" class="space-y-2">
                                                            @csrf
                                                            <label class="block text-[11px] font-bold text-[#172923]">Alasan Penolakan:</label>
                                                            <input type="text" name="reason" required placeholder="Contoh: Bahan baku sudah siap" class="w-full px-2 py-1 text-xs border border-[#DCE5DD] rounded">
                                                            <button type="submit" class="w-full py-1 bg-[#9B3232] text-white rounded text-[10px] font-bold">
                                                                Tolak Revisi
                                                            </button>
                                                        </form>
                                                    </div>
                                                </details>
                                            </div>
                                        @elseif ($row['has_exception'] && (! $active_plan || ! $active_plan->isFinalized()))
                                            <!-- Inline Exception Approval -->
                                            <form method="POST" action="{{ route('head.production.exceptions.approve', $row['request']) }}">
                                                @csrf
                                                <button type="submit" 
                                                        onclick="return confirm('Sahkan permintaan porsi yang melebihi kuota siswa untuk {{ $row['school']->name }}?');" 
                                                        class="px-2 py-1 bg-[#885D1C] hover:bg-[#6D4916] text-white rounded text-[10px] font-bold touch-target">
                                                    Sahkan Kuota
                                                </button>
                                            </form>
                                        @elseif ($row['is_eligible'])
                                            <span class="text-[10px] text-[#1B573E] font-semibold bg-[#EDF7F2] px-2 py-0.5 rounded">
                                                Memenuhi Syarat
                                            </span>
                                        @else
                                            <span class="text-[10px] text-[#53645B] italic">
                                                Belum Siap
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-xs text-[#53645B]">
                                        Tidak ada data sekolah terdaftar untuk unit pelayanan ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Footer Summary Ledger -->
                <div class="p-4 bg-[#F5F7F3] border-t border-[#DCE5DD] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                    <span class="text-[#53645B]">
                        Total Alokasi Sah Direncanakan:
                    </span>
                    <div class="flex items-baseline gap-2">
                        <span class="text-lg font-mono font-extrabold text-[#1C6654] tabular-nums">
                            {{ number_format($total_allocated_portions) }} Porsi
                        </span>
                        <span class="text-[11px] text-[#53645B]">
                            (Maksimal Kapasitas: {{ number_format($kitchen_capacity) }} porsi)
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Active Menu & Finalization Control (4 cols on lg) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Active Menu Card -->
            <div class="bg-white border border-[#DCE5DD] rounded-xl subtle-shadow p-5 space-y-4">
                <div class="pb-3 border-b border-[#DCE5DD] flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-[#172923]">
                            Menu Hidangan Aktif
                        </h2>
                        <p class="text-xs text-[#53645B] mt-0.5">
                            Rujukan resep dan gramatur gizi produksi
                        </p>
                    </div>
                    @if ($selected_menu)
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#EDF7F2] text-[#1B573E] border border-[#BCE2D0]">
                            Versi {{ $selected_menu->version }}
                        </span>
                    @endif
                </div>

                @if ($active_menus->isEmpty())
                    <div class="p-3 rounded-lg bg-[#FAF4EB] border border-[#E8D7BE] text-xs text-[#885D1C]">
                        Belum ada menu siklus dengan status Aktif. Tim Gizi perlu mengaktifkan menu terlebih dahulu.
                    </div>
                @else
                    <!-- Menu Selector if plan is not finalized -->
                    @if (! $active_plan || ! $active_plan->isFinalized())
                        <form method="GET" action="{{ route('head.production.index') }}" class="space-y-2">
                            <input type="hidden" name="date" value="{{ $service_date }}">
                            <label for="menu_select" class="block text-xs font-semibold text-[#172923]">
                                Pilih Menu Aktif:
                            </label>
                            <select id="menu_select" 
                                    name="menu_id" 
                                    onchange="this.form.submit()" 
                                    class="w-full px-3 py-2 text-xs bg-[#FAFBF9] border border-[#DCE5DD] rounded-xl text-[#172923] font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] touch-target">
                                @foreach ($active_menus as $m)
                                    <option value="{{ $m->id }}" {{ $selected_menu?->id === $m->id ? 'selected' : '' }}>
                                        {{ $m->name }} (v{{ $m->version }})
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif

                    <!-- Selected Menu Details -->
                    @if ($selected_menu)
                        <div class="p-3.5 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] space-y-3">
                            <div>
                                <span class="text-xs font-mono font-bold text-[#1C6654] block">{{ $selected_menu->menu_code }}</span>
                                <h3 class="text-sm font-bold text-[#172923] mt-0.5">{{ $selected_menu->name }}</h3>
                            </div>

                            <!-- Nutrition Highlights -->
                            <div class="grid grid-cols-2 gap-2 text-xs pt-2 border-t border-[#DCE5DD]">
                                <div class="bg-white p-2 rounded-lg border border-[#DCE5DD]/60">
                                    <span class="text-[10px] text-[#53645B] block">Energi</span>
                                    <span class="font-mono font-bold text-[#172923]">{{ number_format($selected_menu->totalEnergy() ?? 0, 0) }} kcal</span>
                                </div>
                                <div class="bg-white p-2 rounded-lg border border-[#DCE5DD]/60">
                                    <span class="text-[10px] text-[#53645B] block">Protein</span>
                                    <span class="font-mono font-bold text-[#172923]">{{ number_format($selected_menu->totalProtein() ?? 0, 1) }} g</span>
                                </div>
                            </div>

                            <!-- Menu Items Component List -->
                            <div class="pt-2 border-t border-[#DCE5DD] space-y-1">
                                <span class="text-[11px] font-bold text-[#53645B] block">Komponen Hidangan:</span>
                                <ul class="text-[11px] text-[#172923] space-y-1">
                                    @foreach ($selected_menu->items as $item)
                                        <li class="flex items-center justify-between">
                                            <span>• {{ $item->item_name }}</span>
                                            <span class="font-mono text-[#53645B]">{{ number_format($item->serving_weight_g) }}g</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                @endif
            </div>

            <!-- Primary Finalization Control Card -->
            <div class="bg-white border border-[#DCE5DD] rounded-xl subtle-shadow p-5 space-y-4">
                <div class="pb-3 border-b border-[#DCE5DD]">
                    <h2 class="text-sm font-bold text-[#172923]">
                        Finalisasi Rencana Operasional
                    </h2>
                    <p class="text-xs text-[#53645B] mt-0.5">
                        Penguncian target resmi untuk diteruskan ke Asisten Lapangan
                    </p>
                </div>

                @if ($active_plan && $active_plan->isFinalized())
                    <!-- FINALIZED STATE DISPLAY -->
                    <div class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] space-y-3">
                        <div class="flex items-center gap-2 text-xs font-bold text-[#1B573E]">
                            <svg class="w-5 h-5 text-[#1B573E]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Rencana Produksi Resmi Difinalisasi</span>
                        </div>
                        <div class="text-xs text-[#1B573E] space-y-1 leading-relaxed pt-2 border-t border-[#BCE2D0]/60">
                            <p>Target porsi: <strong>{{ number_format($active_plan->totalAllocatedPortions()) }} porsi</strong></p>
                            <p>Difinalisasi oleh: <strong>{{ $active_plan->finalizer?->name }}</strong></p>
                            <p>Waktu finalisasi: <strong>{{ $active_plan->finalized_at?->translatedFormat('d M Y, H:i') }} WIB</strong></p>
                        </div>
                        <p class="text-[11px] text-[#53645B] pt-2 border-t border-[#BCE2D0]/60 leading-relaxed">
                            Seluruh permintaan porsi telah dikunci (LOCKED). Target produksi kini dapat diakses oleh Asisten Lapangan untuk pelaksanaan dapur.
                        </p>
                    </div>
                @elseif (! $active_plan)
                    <!-- STEP 1: CREATE INITIAL PLAN -->
                    @if ($selected_menu)
                        <form method="POST" action="{{ route('head.production.plans.store') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="service_date" value="{{ $service_date }}">
                            <input type="hidden" name="menu_id" value="{{ $selected_menu->id }}">
                            <div class="space-y-1.5">
                                <label for="plan_notes" class="block text-xs font-semibold text-[#172923]">
                                    Catatan Rencana Produksi (Opsional)
                                </label>
                                <textarea id="plan_notes" 
                                          name="notes" 
                                          rows="2" 
                                          placeholder="Contoh: Penyesuaian shift pagi untuk menu sayur lodeh" 
                                          class="w-full px-3 py-2 text-xs bg-white border border-[#DCE5DD] rounded-xl text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654]"></textarea>
                            </div>

                            <button type="submit" 
                                    class="w-full min-h-[44px] px-4 py-2.5 bg-[#1C6654] hover:bg-[#154D40] text-white text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center justify-center gap-2 touch-target focus:outline-none focus:ring-2 focus:ring-[#1C6654] focus:ring-offset-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                <span>Buat Rencana Produksi Awal</span>
                            </button>
                        </form>
                    @endif
                @else
                    <!-- STEP 2: FINALIZE EXISTING PLANNED PLAN -->
                    <form method="POST" action="{{ route('head.production.plans.finalize', $active_plan) }}" class="space-y-4">
                        @csrf
                        <!-- Hidden allocation inputs based on valid rows -->
                        @foreach ($allocation_rows as $idx => $row)
                            @if ($row['is_eligible'])
                                <input type="hidden" name="allocations[{{ $idx }}][school_id]" value="{{ $row['school']->id }}">
                                <input type="hidden" name="allocations[{{ $idx }}][portion_request_id]" value="{{ $row['request']->id }}">
                                <input type="hidden" name="allocations[{{ $idx }}][allocated_portions]" value="{{ $row['allocated_portions'] }}">
                            @endif
                        @endforeach

                        <div class="p-3.5 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] text-xs space-y-2">
                            <span class="font-bold text-[#172923] block">Ringkasan Konsekuensi Finalisasi:</span>
                            <ul class="text-[11px] text-[#53645B] space-y-1 list-disc list-inside leading-relaxed">
                                <li>Semua kebutuhan sekolah terkait akan beralih ke status <strong>Terkunci (LOCKED)</strong>.</li>
                                <li>PIC sekolah tidak dapat lagi membatalkan atau mengubah draft secara sepihak.</li>
                                <li>Target {{ number_format($total_allocated_portions) }} porsi resmi disahkan untuk Asisten Lapangan.</li>
                            </ul>
                        </div>

                        <button type="submit" 
                                {{ ! $can_finalize ? 'disabled' : '' }}
                                onclick="return confirm('PERINGATAN: Finalisasi rencana produksi ini? Tindakan ini mengunci seluruh alokasi sekolah secara resmi.');" 
                                class="w-full min-h-[44px] px-4 py-2.5 {{ $can_finalize ? 'bg-[#1C6654] hover:bg-[#154D40] text-white cursor-pointer' : 'bg-[#DCE5DD] text-[#8E9B94] cursor-not-allowed' }} text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center justify-center gap-2 touch-target focus:outline-none focus:ring-2 focus:ring-[#1C6654] focus:ring-offset-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Finalisasi Rencana Produksi</span>
                        </button>
                    </form>
                @endif
            </div>

            <!-- Historical Production Plans Ledger -->
            @if ($existing_plans->isNotEmpty())
                <div class="bg-white border border-[#DCE5DD] rounded-xl subtle-shadow p-5 space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#172923] pb-2 border-b border-[#DCE5DD]">
                        Daftar Rencana Tanggal Ini
                    </h3>
                    <div class="divide-y divide-[#DCE5DD] text-xs">
                        @foreach ($existing_plans as $ep)
                            <div class="py-2.5 flex items-center justify-between gap-2 first:pt-1 last:pb-1">
                                <div>
                                    <span class="font-bold text-[#172923] block">{{ $ep->menu->name }}</span>
                                    <span class="text-[10px] text-[#53645B]">v{{ $ep->menu->version }} : {{ $ep->status->label() }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="font-mono font-bold text-[#172923] block">{{ number_format($ep->totalAllocatedPortions()) }} porsi</span>
                                    <span class="text-[10px] text-[#53645B] block">{{ $ep->finalized_at ? $ep->finalized_at->format('H:i') . ' WIB' : 'Direncanakan' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-shell>
