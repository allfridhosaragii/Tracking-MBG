<x-app-shell title="Monitoring Porsi Sekolah : Kepala SPPG">
    <!-- Header & Unit Identity -->
    <header class="mb-5 pb-4 border-b border-[#DCE5DD]">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                    <x-role-badge :role="$user->role" />
                    <span class="text-xs font-mono text-[#53645B]">SPPG: {{ $sppg->code }}</span>
                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-[#EDF7F2] text-[#1B573E] border border-[#BCE2D0]">
                        PBI-04 Kebutuhan Porsi
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">Monitoring Kebutuhan Porsi Sekolah</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">
                    Unit Pelayanan: <strong>{{ $sppg->name }}</strong>
                </p>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto">
                <span class="px-3 py-1.5 rounded-xl bg-white border border-[#DCE5DD] text-xs font-mono text-[#1C6654] font-bold subtle-shadow">
                    WIB (Asia/Jakarta)
                </span>
            </div>
        </div>
    </header>

    <!-- Date Navigator & Quick Select -->
    <section aria-label="Navigasi Tanggal Layanan" class="mb-6 bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
        <form method="GET" action="{{ route('head.portions.index') }}" class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <label for="date-picker" class="block text-xs font-bold text-[#172923] uppercase tracking-wider mb-1">
                    Pilih Tanggal Layanan
                </label>
                <div class="flex items-center gap-2">
                    <input 
                        type="date" 
                        id="date-picker" 
                        name="date" 
                        value="{{ $selectedDate }}"
                        class="min-h-[44px] px-3.5 py-2 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] text-sm font-medium text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654] focus:border-[#1C6654]"
                    >
                    <button 
                        type="submit" 
                        class="min-h-[44px] px-4 py-2 rounded-xl bg-[#1C6654] hover:bg-[#154D40] text-white text-xs font-bold transition-colors subtle-shadow focus:outline-none focus:ring-2 focus:ring-[#1C6654] focus:ring-offset-2">
                        Tampilkan
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
                    <a href="{{ route('head.portions.index', ['date' => $todayStr]) }}"
                       class="min-h-[44px] px-3.5 py-2 rounded-xl text-xs font-bold transition-colors inline-flex items-center border {{ $selectedDate === $todayStr ? 'bg-[#1C6654] text-white border-[#1C6654]' : 'bg-[#FAFBF9] text-[#172923] border-[#DCE5DD] hover:bg-white' }}">
                        Hari Ini
                    </a>
                    <a href="{{ route('head.portions.index', ['date' => $tomorrowStr]) }}"
                       class="min-h-[44px] px-3.5 py-2 rounded-xl text-xs font-bold transition-colors inline-flex items-center border {{ $selectedDate === $tomorrowStr ? 'bg-[#1C6654] text-white border-[#1C6654]' : 'bg-[#FAFBF9] text-[#172923] border-[#DCE5DD] hover:bg-white' }}">
                        Besok (Target H-1)
                    </a>
                    <a href="{{ route('head.portions.index', ['date' => $dayAfterStr]) }}"
                       class="min-h-[44px] px-3.5 py-2 rounded-xl text-xs font-bold transition-colors inline-flex items-center border {{ $selectedDate === $dayAfterStr ? 'bg-[#1C6654] text-white border-[#1C6654]' : 'bg-[#FAFBF9] text-[#172923] border-[#DCE5DD] hover:bg-white' }}">
                        Lusa
                    </a>
                </div>
            </div>
        </form>
    </section>

    <!-- Aggregation Invariant Warning Banner -->
    @if($aggregate['pending_count'] > 0)
        <div class="mb-6 p-4 rounded-xl bg-[#FAF4EB] border border-[#E8D7BE] text-[#885D1C] flex items-start gap-3">
            <svg class="w-5 h-5 text-[#885D1C] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="text-xs leading-relaxed">
                <strong class="font-bold text-sm block mb-0.5 text-[#172923]">Agregasi Parsial: Terdapat {{ $aggregate['pending_count'] }} sekolah belum konfirmasi!</strong>
                Total porsi di bawah hanya menjumlahkan sekolah yang telah berstatus resmi <strong>KONFIRMASI / TERKUNCI / REVISI</strong>. Sekolah yang belum konfirmasi <strong>TIDAK dihitung nol porsi</strong>, melainkan berstatus belum diketahui. Jangan menggunakan angka ini sebagai kuota final pengolahan sebelum seluruh sekolah selesai konfirmasi.
            </div>
        </div>
    @endif

    <!-- Metrics Cards Grid -->
    <section aria-label="Ringkasan Kebutuhan Porsi" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
            <span class="text-xs text-[#53645B] block mb-1">Total Porsi Terkonfirmasi</span>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-[#1C6654] tabular-nums">{{ number_format($aggregate['total_confirmed_portions']) }}</span>
                <span class="text-xs text-[#53645B]">porsi</span>
            </div>
            <p class="text-[11px] text-[#53645B] mt-1.5 leading-relaxed">
                Dari {{ $aggregate['confirmed_count'] }} dari total {{ $aggregate['total_schools'] }} sekolah aktif.
            </p>
        </div>

        <div class="bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
            <span class="text-xs text-[#53645B] block mb-1">Menunggu Konfirmasi PIC</span>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold {{ $aggregate['pending_count'] > 0 ? 'text-[#885D1C]' : 'text-[#21684B]' }} tabular-nums">
                    {{ $aggregate['pending_count'] }}
                </span>
                <span class="text-xs text-[#53645B]">sekolah</span>
            </div>
            <p class="text-[11px] text-[#53645B] mt-1.5 leading-relaxed">
                {{ $aggregate['pending_count'] > 0 ? 'Kebutuhan belum terdata (bukan nol).' : 'Semua sekolah telah konfirmasi.' }}
            </p>
        </div>

        <div class="bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
            <span class="text-xs text-[#53645B] block mb-1">Sekolah Terkonfirmasi Sah</span>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-[#172923] tabular-nums">{{ $aggregate['confirmed_count'] }}</span>
                <span class="text-xs text-[#53645B]">sekolah</span>
            </div>
            <p class="text-[11px] text-[#53645B] mt-1.5 leading-relaxed">
                Telah melalui validasi data dan batas cutoff.
            </p>
        </div>

        <div class="bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
            <span class="text-xs text-[#53645B] block mb-1">Layanan Dibatalkan</span>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-[#53645B] tabular-nums">{{ $aggregate['cancelled_count'] }}</span>
                <span class="text-xs text-[#53645B]">sekolah</span>
            </div>
            <p class="text-[11px] text-[#53645B] mt-1.5 leading-relaxed">
                Libur sekolah atau pembatalan sah dengan alasan.
            </p>
        </div>
    </section>

    <!-- Section 1: Pending Schools Table (Requires Attention) -->
    <section aria-label="Sekolah Menunggu Konfirmasi" class="mb-6">
        <x-card title="Sekolah Menunggu Konfirmasi PIC" subtitle="Daftar sekolah yang belum mengirimkan konfirmasi porsi final untuk tanggal layanan ini">
            @if(count($aggregate['pending_schools']) === 0)
                <div class="p-6 text-center text-xs text-[#53645B] bg-[#FAFBF9] rounded-xl border border-[#DCE5DD]">
                    <svg class="w-8 h-8 text-[#21684B] mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="font-bold text-[#172923] text-sm">Seluruh Sekolah Telah Konfirmasi</p>
                    <p class="mt-1">Tidak ada sekolah yang berstatus menunggu atau draft tersisa untuk tanggal layanan ini.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-[#DCE5DD] bg-[#FAFBF9] text-[#53645B]">
                                <th scope="col" class="py-3 px-3 font-bold">Sekolah</th>
                                <th scope="col" class="py-3 px-3 font-bold">Jenjang</th>
                                <th scope="col" class="py-3 px-3 font-bold">Siswa Terdaftar</th>
                                <th scope="col" class="py-3 px-3 font-bold">PIC Sekolah</th>
                                <th scope="col" class="py-3 px-3 font-bold">Batas Cutoff</th>
                                <th scope="col" class="py-3 px-3 font-bold">Status Layanan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#DCE5DD]">
                            @foreach($aggregate['pending_schools'] as $item)
                                <tr class="hover:bg-[#FAFBF9] transition-colors">
                                    <td class="py-3 px-3 font-medium text-[#172923]">
                                        <div class="font-bold">{{ $item['school']->name }}</div>
                                        <div class="text-[11px] font-mono text-[#53645B]">{{ $item['school']->school_code }}</div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#EDF2ED] text-[#172923] border border-[#DCE5DD]">
                                            {{ $item['school']->education_level }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 font-medium text-[#53645B]">
                                        <span class="font-bold text-[#172923] tabular-nums">{{ number_format($item['registered_count']) }}</span> siswa
                                        <div class="text-[10px] text-[#885D1C] italic">Estimasi data pokok</div>
                                    </td>
                                    <td class="py-3 px-3 text-[#172923]">
                                        <div class="font-medium">{{ $item['pic_name'] }}</div>
                                        @if($item['school']->contact_phone)
                                            <div class="text-[11px] font-mono text-[#53645B]">{{ $item['school']->contact_phone }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="font-mono text-[11px] text-[#172923]">
                                            {{ $item['cutoff_datetime']->translatedFormat('d M Y, H:i') }} WIB
                                        </div>
                                        @if($item['is_past_cutoff'])
                                            <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-bold bg-[#FDE8E8] text-[#9B1C1C] border border-[#F8B4B4]">
                                                Lewat Cutoff
                                            </span>
                                        @else
                                            <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-bold bg-[#FAF4EB] text-[#885D1C] border border-[#E8D7BE]">
                                                Menjelang Cutoff
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3">
                                        @if($item['request'])
                                            <x-portion-status-badge :status="$item['request']->status" />
                                        @else
                                            <span class="px-2 py-1 rounded text-[11px] font-bold bg-[#FAF4EB] text-[#885D1C] border border-[#E8D7BE] inline-block">
                                                Belum Ada Input
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </section>

    <!-- Section 2: Confirmed Schools Breakdown -->
    <section aria-label="Rincian Sekolah Terkonfirmasi" class="mb-6">
        <x-card title="Rincian Sekolah Terkonfirmasi & Terkunci" subtitle="Daftar alokasi porsi yang sah dan siap dijadikan acuan persiapan dapur SPPG">
            @if(count($aggregate['confirmed_schools']) === 0)
                <div class="p-6 text-center text-xs text-[#53645B] bg-[#FAFBF9] rounded-xl border border-[#DCE5DD]">
                    <p class="font-bold text-[#172923] text-sm">Belum Ada Sekolah Terkonfirmasi</p>
                    <p class="mt-1">Belum ada sekolah yang mengonfirmasi kebutuhan porsi untuk tanggal layanan {{ $parsedDate->translatedFormat('d F Y') }}.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-[#DCE5DD] bg-[#FAFBF9] text-[#53645B]">
                                <th scope="col" class="py-3 px-3 font-bold">Sekolah</th>
                                <th scope="col" class="py-3 px-3 font-bold">Snapshot Terdaftar</th>
                                <th scope="col" class="py-3 px-3 font-bold">Siswa Hadir</th>
                                <th scope="col" class="py-3 px-3 font-bold">Porsi Dikonfirmasi</th>
                                <th scope="col" class="py-3 px-3 font-bold">Status</th>
                                <th scope="col" class="py-3 px-3 font-bold">Konfirmator</th>
                                <th scope="col" class="py-3 px-3 font-bold">Waktu Konfirmasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#DCE5DD]">
                            @foreach($aggregate['confirmed_schools'] as $item)
                                <tr class="hover:bg-[#FAFBF9] transition-colors">
                                    <td class="py-3 px-3 font-medium text-[#172923]">
                                        <div class="font-bold">{{ $item['school']->name }}</div>
                                        <div class="text-[11px] font-mono text-[#53645B]">{{ $item['school']->school_code }}</div>
                                        @if($item['request']->revision_reason)
                                            <div class="mt-1 text-[11px] text-[#885D1C] bg-[#FAF4EB] p-1.5 rounded border border-[#E8D7BE]">
                                                Catatan: {{ $item['request']->revision_reason }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 font-mono tabular-nums text-[#53645B]">
                                        {{ number_format($item['request']->registered_count) }}
                                    </td>
                                    <td class="py-3 px-3 font-mono tabular-nums text-[#172923]">
                                        {{ $item['present_count'] !== null ? number_format($item['present_count']) : '-' }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="text-sm font-bold text-[#1C6654] font-mono tabular-nums">
                                            {{ number_format($item['requested_portions']) }}
                                        </span>
                                        <span class="text-[11px] text-[#53645B]">porsi</span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <x-portion-status-badge :status="$item['status']" />
                                    </td>
                                    <td class="py-3 px-3 text-[#172923]">
                                        <div class="font-medium">{{ $item['confirmed_by'] }}</div>
                                    </td>
                                    <td class="py-3 px-3 font-mono text-[11px] text-[#53645B]">
                                        {{ $item['confirmed_at'] ? $item['confirmed_at']->translatedFormat('d M Y, H:i') : '-' }} WIB
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-[#1C6654] bg-[#EDF7F2] font-bold text-xs text-[#1B573E]">
                                <td class="py-3 px-3" colspan="3">
                                    TOTAL PORSI TERKONFIRMASI ({{ count($aggregate['confirmed_schools']) }} Sekolah)
                                </td>
                                <td class="py-3 px-3 font-mono tabular-nums text-sm text-[#1C6654]">
                                    {{ number_format($aggregate['total_confirmed_portions']) }} porsi
                                </td>
                                <td class="py-3 px-3" colspan="3">
                                    @if($aggregate['pending_count'] > 0)
                                        <span class="text-[#885D1C] text-[11px] font-normal">
                                            * Belum termasuk {{ $aggregate['pending_count'] }} sekolah menunggu
                                        </span>
                                    @else
                                        <span class="text-[#21684B] text-[11px] font-normal">
                                            Lengkap (100% sekolah)
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </x-card>
    </section>

    <!-- Section 3: Cancelled Schools (If Any) -->
    @if(count($aggregate['cancelled_schools']) > 0)
        <section aria-label="Sekolah Layanan Batal" class="mb-6">
            <x-card title="Sekolah Tidak Menerima Layanan (Batal/Libur)" subtitle="Daftar sekolah yang mengajukan pembatalan resmi untuk tanggal ini">
                <div class="divide-y divide-[#DCE5DD]">
                    @foreach($aggregate['cancelled_schools'] as $item)
                        <div class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div>
                                <span class="font-bold text-sm text-[#172923]">{{ $item['school']->name }}</span>
                                <span class="text-xs font-mono text-[#53645B] ml-2">({{ $item['school']->school_code }})</span>
                                <p class="text-xs text-[#53645B] mt-1">
                                    Alasan: <em>{{ $item['reason'] ?? 'Tidak ada keterangan rinci' }}</em>
                                </p>
                            </div>
                            <span class="px-2.5 py-1 rounded text-xs font-bold bg-[#FDE8E8] text-[#9B1C1C] border border-[#F8B4B4] self-start sm:self-auto">
                                LAYANAN DIBATALKAN
                            </span>
                        </div>
                    @endforeach
                </div>
            </x-card>
        </section>
    @endif
</x-app-shell>
