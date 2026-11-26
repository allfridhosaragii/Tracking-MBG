<x-app-shell title="Monitoring Porsi Sekolah">
    <div class="space-y-5">
        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <span class="eyebrow-label text-[#1C6654] block mb-1">Kepala SPPG • {{ $sppg->code }}</span>
                <h1 class="page-title">Monitoring Kebutuhan Porsi Sekolah</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-1">{{ $sppg->name }} • Rekapitulasi konfirmasi porsi harian dari PIC Sekolah.</p>
            </div>

            <a href="{{ route('head.production.index', ['date' => $selectedDate]) }}"
               class="min-h-[44px] px-4 py-2.5 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs font-semibold inline-flex items-center justify-center gap-2 shrink-0 smooth-transition">
                <span>Lanjut ke Rencana Produksi</span>
                <span aria-hidden="true">&rarr;</span>
            </a>
        </header>

        <!-- Compact Date Bar -->
        <section aria-label="Navigasi Tanggal Layanan" class="luxury-surface p-3.5 sm:p-4">
            <form method="GET" action="{{ route('head.portions.index') }}" class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <label for="date-picker" class="text-xs font-semibold text-[#53645B] shrink-0">Tanggal:</label>
                    <input type="date"
                           id="date-picker"
                           name="date"
                           value="{{ $selectedDate }}"
                           class="min-h-[44px] min-w-0 flex-1 sm:flex-initial px-3 py-2 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] text-xs sm:text-sm font-medium text-[#172923]">
                    <button type="submit"
                            class="min-h-[44px] px-4 py-2 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs font-semibold cursor-pointer smooth-transition shrink-0">
                        Tampilkan
                    </button>
                </div>

                @php
                    $todayStr = \Carbon\Carbon::today('Asia/Jakarta')->format('Y-m-d');
                    $tomorrowStr = \Carbon\Carbon::tomorrow('Asia/Jakarta')->format('Y-m-d');
                    $dayAfterStr = \Carbon\Carbon::today('Asia/Jakarta')->addDays(2)->format('Y-m-d');
                @endphp
                <div class="flex flex-wrap items-center gap-1.5">
                    <a href="{{ route('head.portions.index', ['date' => $todayStr]) }}"
                       class="min-h-[44px] px-3 py-1.5 rounded-xl text-xs font-semibold inline-flex items-center border whitespace-nowrap {{ $selectedDate === $todayStr ? 'bg-[#1C6654] text-white border-[#1C6654]' : 'bg-[#FAFBF9] text-[#172923] border-[#DCE5DD] hover:bg-white' }}">
                        Hari Ini
                    </a>
                    <a href="{{ route('head.portions.index', ['date' => $tomorrowStr]) }}"
                       class="min-h-[44px] px-3 py-1.5 rounded-xl text-xs font-semibold inline-flex items-center border whitespace-nowrap {{ $selectedDate === $tomorrowStr ? 'bg-[#1C6654] text-white border-[#1C6654]' : 'bg-[#FAFBF9] text-[#172923] border-[#DCE5DD] hover:bg-white' }}">
                        Besok (H-1)
                    </a>
                    <a href="{{ route('head.portions.index', ['date' => $dayAfterStr]) }}"
                       class="min-h-[44px] px-3 py-1.5 rounded-xl text-xs font-semibold inline-flex items-center border whitespace-nowrap {{ $selectedDate === $dayAfterStr ? 'bg-[#1C6654] text-white border-[#1C6654]' : 'bg-[#FAFBF9] text-[#172923] border-[#DCE5DD] hover:bg-white' }}">
                        Lusa
                    </a>
                </div>
            </form>
        </section>

        <!-- Partial Aggregation Invariant Alert -->
        @if($aggregate['pending_count'] > 0)
            <div class="p-4 rounded-xl bg-[#FEF7EC] border border-[#F7D7A3] text-[#885D1C] flex items-start gap-3">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="text-xs leading-relaxed">
                    <strong class="font-bold text-sm block mb-0.5 text-[#172923]">Agregasi Parsial: {{ $aggregate['pending_count'] }} sekolah belum konfirmasi</strong>
                    Total porsi terkonfirmasi hanya menjumlahkan sekolah berstatus sah. Sekolah yang belum konfirmasi <strong>TIDAK dihitung nol porsi</strong>, melainkan menunggu input PIC.
                </div>
            </div>
        @endif

        <!-- KPI Strip -->
        <section aria-label="Ringkasan Kebutuhan Porsi" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="luxury-surface p-4">
                <span class="eyebrow-label block">Porsi Terkonfirmasi</span>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl sm:text-3xl font-bold text-[#1C6654] tabular-nums">{{ number_format($aggregate['total_confirmed_portions']) }}</span>
                    <span class="text-xs text-[#53645B]">porsi</span>
                </div>
            </div>

            <div class="luxury-surface p-4">
                <span class="eyebrow-label block">Menunggu Konfirmasi PIC</span>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl sm:text-3xl font-bold {{ $aggregate['pending_count'] > 0 ? 'text-[#885D1C]' : 'text-[#21684B]' }} tabular-nums">{{ $aggregate['pending_count'] }}</span>
                    <span class="text-xs text-[#53645B]">sekolah</span>
                </div>
            </div>

            <div class="luxury-surface p-4">
                <span class="eyebrow-label block">Sekolah Sah</span>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl sm:text-3xl font-bold text-[#172923] tabular-nums">{{ $aggregate['confirmed_count'] }}</span>
                    <span class="text-xs text-[#53645B]">/ {{ $aggregate['total_schools'] }}</span>
                </div>
            </div>

            <div class="luxury-surface p-4">
                <span class="eyebrow-label block">Dibatalkan / Libur</span>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl sm:text-3xl font-bold text-[#53645B] tabular-nums">{{ $aggregate['cancelled_count'] }}</span>
                    <span class="text-xs text-[#53645B]">sekolah</span>
                </div>
            </div>
        </section>

        <!-- Pending Schools -->
        <x-card title="Sekolah Menunggu Konfirmasi PIC" subtitle="Daftar sekolah yang belum mengunci permintaan porsi untuk tanggal layanan ini.">
            @if(count($aggregate['pending_schools']) === 0)
                <div class="p-6 text-center text-xs text-[#53645B] inset-surface">
                    <p class="font-bold text-[#21684B] text-sm">Seluruh Sekolah Telah Konfirmasi</p>
                    <p class="mt-1">Tidak ada sekolah yang berstatus menunggu pada tanggal layanan ini.</p>
                </div>
            @else
                <!-- Desktop Table -->
                <div class="hidden md:block -mx-6 -mb-6 overflow-x-auto">
                    <table class="ledger-table text-xs">
                        <thead>
                            <tr>
                                <th>Sekolah</th>
                                <th>Jenjang</th>
                                <th class="text-right">Siswa Pokok</th>
                                <th>PIC Sekolah</th>
                                <th>Batas Cutoff</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($aggregate['pending_schools'] as $item)
                                <tr>
                                    <td>
                                        <span class="font-semibold text-[#172923] block">{{ $item['school']->name }}</span>
                                        <span class="text-[11px] font-mono text-[#53645B]">{{ $item['school']->school_code }}</span>
                                    </td>
                                    <td>
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-[#FAFBF9] border border-[#DCE5DD]">{{ $item['school']->education_level }}</span>
                                    </td>
                                    <td class="text-right tabular-nums">
                                        <strong class="text-[#172923]">{{ number_format($item['registered_count']) }}</strong> siswa
                                    </td>
                                    <td>{{ $item['pic_name'] }}</td>
                                    <td>
                                        <span class="font-mono text-[11px] text-[#172923] block">{{ $item['cutoff_datetime']->translatedFormat('d M Y, H:i') }} WIB</span>
                                        <span class="inline-block mt-0.5 px-2 py-0.5 rounded-md text-[10px] font-semibold {{ $item['is_past_cutoff'] ? 'bg-[#FDF2F2] text-[#9B3232] border border-[#F5C2C2]' : 'bg-[#FEF7EC] text-[#885D1C] border border-[#F7D7A3]' }}">
                                            {{ $item['is_past_cutoff'] ? 'Lewat Cutoff' : 'Menjelang Cutoff' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($item['request'])
                                            <x-portion-status-badge :status="$item['request']->status" />
                                        @else
                                            <span class="px-2 py-1 rounded-lg text-[11px] font-semibold bg-[#FEF7EC] text-[#885D1C] border border-[#F7D7A3]">Belum Ada Input</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden divide-y divide-[#E7ECE8] -my-1">
                    @foreach($aggregate['pending_schools'] as $item)
                        <div class="py-3.5 space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="text-sm font-bold text-[#172923] block leading-snug">{{ $item['school']->name }}</span>
                                    <span class="text-[11px] font-mono text-[#53645B]">{{ $item['school']->school_code }} • {{ $item['school']->education_level }}</span>
                                </div>
                                @if($item['request'])
                                    <x-portion-status-badge :status="$item['request']->status" />
                                @else
                                    <span class="shrink-0 px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-[#FEF7EC] text-[#885D1C] border border-[#F7D7A3]">Belum Input</span>
                                @endif
                            </div>

                            <div class="flex items-center justify-between text-xs text-[#53645B]">
                                <span>PIC: <strong class="text-[#172923]">{{ $item['pic_name'] }}</strong></span>
                                <span class="tabular-nums">Pokok: <strong class="text-[#172923]">{{ number_format($item['registered_count']) }}</strong></span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

        <!-- Confirmed Schools -->
        <x-card title="Rincian Sekolah Terkonfirmasi &amp; Terkunci" subtitle="Permintaan porsi sah untuk {{ $parsedDate->translatedFormat('d F Y') }}.">
            @if(count($aggregate['confirmed_schools']) === 0)
                <div class="p-6 text-center text-xs text-[#53645B] inset-surface">
                    <p class="font-bold text-[#172923] text-sm">Belum Ada Sekolah Terkonfirmasi</p>
                    <p class="mt-1">Belum ada permintaan porsi yang disahkan untuk tanggal ini.</p>
                </div>
            @else
                <!-- Desktop Table -->
                <div class="hidden md:block -mx-6 -mb-6 overflow-x-auto">
                    <table class="ledger-table text-xs">
                        <thead>
                            <tr>
                                <th>Sekolah</th>
                                <th class="text-right">Data Pokok</th>
                                <th class="text-right">Hadir</th>
                                <th class="text-right">Porsi Sah</th>
                                <th>Status</th>
                                <th>Konfirmator</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($aggregate['confirmed_schools'] as $item)
                                <tr>
                                    <td>
                                        <span class="font-semibold text-[#172923] block">{{ $item['school']->name }}</span>
                                        <span class="text-[11px] font-mono text-[#53645B]">{{ $item['school']->school_code }}</span>
                                        @if($item['request']->revision_reason)
                                            <span class="block mt-1 text-[11px] text-[#885D1C]">Catatan: {{ $item['request']->revision_reason }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right font-mono tabular-nums text-[#53645B]">{{ number_format($item['request']->registered_count) }}</td>
                                    <td class="text-right font-mono tabular-nums text-[#172923]">{{ $item['present_count'] !== null ? number_format($item['present_count']) : '-' }}</td>
                                    <td class="text-right">
                                        <span class="text-sm font-bold text-[#1C6654] font-mono tabular-nums">{{ number_format($item['requested_portions']) }}</span>
                                        <span class="text-[11px] text-[#53645B]">porsi</span>
                                    </td>
                                    <td><x-portion-status-badge :status="$item['status']" /></td>
                                    <td>
                                        <span class="font-medium text-[#172923] block">{{ $item['confirmed_by'] }}</span>
                                        <span class="text-[11px] font-mono text-[#53645B]">{{ $item['confirmed_at'] ? $item['confirmed_at']->translatedFormat('d M Y, H:i') : '-' }} WIB</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-[#1C6654] bg-[#EDF7F2] font-bold text-xs text-[#1B573E]">
                                <td class="py-3 px-4" colspan="3">Total Terkonfirmasi ({{ count($aggregate['confirmed_schools']) }} Sekolah)</td>
                                <td class="py-3 px-4 text-right font-mono tabular-nums text-sm text-[#1C6654]">{{ number_format($aggregate['total_confirmed_portions']) }} porsi</td>
                                <td class="py-3 px-4" colspan="2">
                                    {{ $aggregate['pending_count'] > 0 ? 'Belum termasuk ' . $aggregate['pending_count'] . ' sekolah menunggu' : 'Lengkap (100% sekolah)' }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden space-y-3">
                    <div class="divide-y divide-[#E7ECE8] -my-1">
                        @foreach($aggregate['confirmed_schools'] as $item)
                            <div class="py-3.5 space-y-2">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <span class="text-sm font-bold text-[#172923] block leading-snug">{{ $item['school']->name }}</span>
                                        <span class="text-[11px] font-mono text-[#53645B]">{{ $item['school']->school_code }}</span>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="text-base font-bold text-[#1C6654] tabular-nums block">{{ number_format($item['requested_portions']) }}</span>
                                        <span class="text-[10px] text-[#53645B]">porsi sah</span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 text-xs">
                                    <x-portion-status-badge :status="$item['status']" />
                                    <span class="text-[11px] text-[#53645B]">{{ $item['confirmed_by'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="p-3 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] flex items-center justify-between text-xs font-bold text-[#1B573E]">
                        <span>Total Terkonfirmasi</span>
                        <span class="text-sm font-mono tabular-nums text-[#1C6654]">{{ number_format($aggregate['total_confirmed_portions']) }} porsi</span>
                    </div>
                </div>
            @endif
        </x-card>

        @if(count($aggregate['cancelled_schools']) > 0)
            <x-card title="Sekolah Tidak Menerima Layanan" subtitle="Pembatalan resmi / libur pada tanggal ini.">
                <div class="divide-y divide-[#E7ECE8] -my-1">
                    @foreach($aggregate['cancelled_schools'] as $item)
                        <div class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div>
                                <span class="font-bold text-sm text-[#172923]">{{ $item['school']->name }}</span>
                                <span class="text-xs font-mono text-[#53645B] ml-1">({{ $item['school']->school_code }})</span>
                                <p class="text-xs text-[#53645B] mt-0.5">Alasan: {{ $item['reason'] ?? 'Tidak ada keterangan' }}</p>
                            </div>
                            <span class="self-start sm:self-auto px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-[#FDF2F2] text-[#9B3232] border border-[#F5C2C2]">
                                Dibatalkan
                            </span>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif
    </div>
</x-app-shell>
