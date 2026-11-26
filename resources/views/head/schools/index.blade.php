<x-app-shell title="Manajemen Sekolah & PIC">
    <div class="space-y-5">
        <!-- Clean Header -->
        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <span class="eyebrow-label text-[#1C6654] block mb-1">Kepala SPPG • {{ $sppg->code }}</span>
                <h1 class="page-title">Manajemen Sekolah &amp; PIC Binaan</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-1">Kelola daftar sekolah penerima manfaat, kuota dasar siswa, serta penugasan email PIC masing-masing sekolah.</p>
            </div>

            <a href="{{ route('head.schools.create') }}"
               class="min-h-[44px] px-4 py-2.5 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs sm:text-sm font-semibold inline-flex items-center justify-center gap-2 shrink-0 smooth-transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Tambah Sekolah</span>
            </a>
        </header>

        <!-- Compact Summary Strip -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
            <div class="luxury-surface p-4">
                <span class="eyebrow-label block">Sekolah Aktif</span>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl font-bold text-[#1C6654] tabular-nums">{{ $totalActive }}</span>
                    <span class="text-xs text-[#53645B]">/ {{ $schools->count() }} unit</span>
                </div>
            </div>

            <div class="luxury-surface p-4">
                <span class="eyebrow-label block">Siswa Terdaftar</span>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl font-bold text-[#172923] tabular-nums">{{ number_format($totalBeneficiaries) }}</span>
                    <span class="text-xs text-[#53645B]">anak</span>
                </div>
            </div>

            <div class="luxury-surface p-4 col-span-2 lg:col-span-1">
                <span class="eyebrow-label block">Kebijakan Data</span>
                <p class="text-xs font-semibold text-[#172923] mt-1.5">Arsip Non-Destruktif</p>
                <span class="text-[11px] text-[#53645B] block mt-0.5">Penonaktifan menjaga histori pengiriman</span>
            </div>
        </div>

        <!-- Streamlined Search & Filter Bar -->
        <form method="GET" action="{{ route('head.schools.index') }}" class="luxury-surface p-3.5 sm:p-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
            <div class="relative flex-1">
                <label for="school-search" class="sr-only">Cari sekolah</label>
                <svg class="w-4 h-4 text-[#53645B] absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text"
                       id="school-search"
                       name="q"
                       value="{{ $search }}"
                       placeholder="Cari nama sekolah, kode NPSN, atau alamat..."
                       class="w-full min-h-[44px] pl-10 pr-4 py-2 text-xs sm:text-sm rounded-xl border border-[#DCE5DD] bg-[#FAFBF9] focus:bg-white text-[#172923]">
            </div>

            <div class="flex items-center gap-2">
                <label for="school-status" class="sr-only">Status Sekolah</label>
                <select id="school-status"
                        name="status"
                        onchange="this.form.submit()"
                        class="flex-1 sm:flex-none min-h-[44px] px-3 py-2 text-xs sm:text-sm rounded-xl border border-[#DCE5DD] bg-[#FAFBF9] focus:bg-white text-[#172923]">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Hanya Aktif</option>
                    <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Hanya Nonaktif</option>
                </select>

                <button type="submit"
                        class="min-h-[44px] px-4 py-2 text-xs sm:text-sm font-semibold text-white bg-[#1C6654] hover:bg-[#164D42] rounded-xl cursor-pointer smooth-transition">
                    Cari
                </button>

                @if($search || $status !== 'all')
                    <a href="{{ route('head.schools.index') }}"
                       class="min-h-[44px] px-3 py-2 text-xs font-semibold text-[#53645B] hover:text-[#172923] bg-[#EDF2ED] rounded-xl inline-flex items-center justify-center smooth-transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        <!-- Schools List -->
        <div class="luxury-surface overflow-hidden">
            @if ($schools->isEmpty())
                <div class="p-10 text-center">
                    <h2 class="text-sm font-bold text-[#172923]">Tidak ada sekolah ditemukan</h2>
                    <p class="text-xs text-[#53645B] mt-1 max-w-sm mx-auto leading-relaxed">
                        {{ $search ? 'Tidak ada sekolah yang cocok dengan kriteria pencarian saat ini.' : 'Belum ada sekolah binaan yang terdaftar pada unit SPPG ini.' }}
                    </p>
                    <div class="mt-4">
                        @if($search || $status !== 'all')
                            <a href="{{ route('head.schools.index') }}" class="min-h-[44px] inline-flex items-center px-4 py-2 text-xs font-semibold text-[#1C6654] bg-[#EDF7F2] rounded-xl">
                                Tampilkan Semua Sekolah
                            </a>
                        @else
                            <a href="{{ route('head.schools.create') }}" class="min-h-[44px] inline-flex items-center px-4 py-2 text-xs font-semibold text-white bg-[#1C6654] rounded-xl">
                                + Tambah Sekolah Pertama
                            </a>
                        @endif
                    </div>
                </div>
            @else
                <!-- Desktop Table -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="ledger-table text-xs sm:text-sm">
                        <thead>
                            <tr>
                                <th>Kode / NPSN</th>
                                <th>Nama Sekolah</th>
                                <th>Jenjang</th>
                                <th class="text-right">Data Siswa</th>
                                <th>Akun PIC</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($schools as $school)
                                @php $pic = $school->picUsers->first(); @endphp
                                <tr class="{{ ! $school->is_active ? 'opacity-70 bg-[#FAFBF9]' : '' }}">
                                    <td class="font-mono text-xs font-semibold text-[#1C6654]">{{ $school->school_code }}</td>
                                    <td>
                                        <a href="{{ route('head.schools.show', $school) }}" class="font-semibold text-[#172923] hover:text-[#1C6654] block">
                                            {{ $school->name }}
                                        </a>
                                        <span class="text-[11px] text-[#53645B] block truncate max-w-xs mt-0.5">{{ $school->address }}</span>
                                    </td>
                                    <td>
                                        <span class="px-2 py-0.5 rounded-md text-xs font-semibold bg-[#FAFBF9] text-[#172923] border border-[#DCE5DD]">
                                            {{ $school->education_level }}
                                        </span>
                                    </td>
                                    <td class="text-right font-bold text-[#172923] tabular-nums">
                                        {{ number_format($school->registered_beneficiaries) }} <span class="text-xs font-normal text-[#53645B]">siswa</span>
                                    </td>
                                    <td>
                                        @if ($pic)
                                            <span class="font-medium text-[#172923] block text-xs">{{ $pic->name }}</span>
                                            <span class="text-[11px] font-mono text-[#53645B] block truncate max-w-[180px]">{{ $pic->email }}</span>
                                        @else
                                            <span class="text-xs text-[#9B3232] font-medium">Belum ada PIC</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($school->is_active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-[#EDF7F2] text-[#21684B] border border-[#BCE2D0]">Aktif</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-[#FDF2F2] text-[#9B3232] border border-[#F5C2C2]">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5 justify-end">
                                            <a href="{{ route('head.schools.show', $school) }}"
                                               class="min-h-[44px] px-3 py-2 text-xs font-semibold text-[#172923] bg-white hover:bg-[#F5F7F3] border border-[#DCE5DD] rounded-xl inline-flex items-center">
                                                Detail
                                            </a>
                                            <a href="{{ route('head.schools.edit', $school) }}"
                                               class="min-h-[44px] px-3 py-2 text-xs font-semibold text-[#1C6654] bg-[#EDF7F2] hover:bg-[#DCF0E6] border border-[#BCE2D0] rounded-xl inline-flex items-center">
                                                Edit
                                            </a>
                                            <form method="POST" action="{{ route('head.schools.toggle-active', $school) }}" class="inline" onsubmit="return confirm('Ubah status aktif sekolah ini?')">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                        class="min-h-[44px] px-3 py-2 text-xs font-semibold {{ $school->is_active ? 'text-[#9B3232] bg-[#FDF2F2] hover:bg-[#FBE8E8] border border-[#F5C2C2]' : 'text-[#21684B] bg-[#EDF7F2] hover:bg-[#DCF0E6] border border-[#BCE2D0]' }} rounded-xl cursor-pointer">
                                                    {{ $school->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Minimalist Cards -->
                <div class="md:hidden divide-y divide-[#E7ECE8]">
                    @foreach ($schools as $school)
                        @php $pic = $school->picUsers->first(); @endphp
                        <div class="p-4 space-y-3 {{ ! $school->is_active ? 'bg-[#FAFBF9] opacity-80' : '' }}">
                            <div class="flex items-start justify-between gap-2.5">
                                <div class="min-w-0">
                                    <a href="{{ route('head.schools.show', $school) }}" class="text-sm font-bold text-[#172923] hover:text-[#1C6654] block leading-snug">
                                        {{ $school->name }}
                                    </a>
                                    <span class="text-[11px] font-mono text-[#53645B] block mt-0.5">{{ $school->school_code }} • {{ $school->education_level }}</span>
                                </div>
                                <span class="shrink-0 px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $school->is_active ? 'bg-[#EDF7F2] text-[#21684B] border-[#BCE2D0]' : 'bg-[#FDF2F2] text-[#9B3232] border-[#F5C2C2]' }}">
                                    {{ $school->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>

                            <div class="inset-surface p-2.5 flex items-center justify-between gap-3 text-xs">
                                <div class="min-w-0">
                                    <span class="text-[10px] text-[#53645B] block">PIC Bertugas</span>
                                    @if ($pic)
                                        <span class="font-medium text-[#172923] block truncate">{{ $pic->email }}</span>
                                    @else
                                        <span class="text-[#9B3232] font-medium">Belum ditugaskan</span>
                                    @endif
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-[10px] text-[#53645B] block">Siswa</span>
                                    <span class="font-bold text-[#172923] tabular-nums">{{ number_format($school->registered_beneficiaries) }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="{{ route('head.schools.show', $school) }}"
                                   class="flex-1 min-h-[44px] text-xs font-semibold text-[#172923] bg-white border border-[#DCE5DD] rounded-xl inline-flex items-center justify-center">
                                    Detail
                                </a>
                                <a href="{{ route('head.schools.edit', $school) }}"
                                   class="flex-1 min-h-[44px] text-xs font-semibold text-[#1C6654] bg-[#EDF7F2] border border-[#BCE2D0] rounded-xl inline-flex items-center justify-center">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('head.schools.toggle-active', $school) }}" class="inline" onsubmit="return confirm('Ubah status aktif sekolah ini?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="min-h-[44px] px-3.5 text-xs font-semibold {{ $school->is_active ? 'text-[#9B3232] bg-[#FDF2F2] border border-[#F5C2C2]' : 'text-[#21684B] bg-[#EDF7F2] border border-[#BCE2D0]' }} rounded-xl cursor-pointer">
                                        {{ $school->is_active ? 'Nonaktif' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-shell>
