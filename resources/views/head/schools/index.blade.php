<x-app-shell title="Kelola Sekolah Binaan — Kepala SPPG">
    <!-- Header Navigation & Action -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
        <div>
            <div class="flex items-center gap-2 mb-1.5">
                <a href="{{ route('head.dashboard') }}" class="text-xs font-medium text-[#1C6654] hover:underline inline-flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span>Dashboard Operasional</span>
                </a>
                <span class="text-xs text-[#53645B]">•</span>
                <span class="text-xs text-[#53645B] font-mono">SPPG: {{ $sppg->code }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">Manajemen Sekolah & PIC Binaan</h1>
            <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">Kelola data master sekolah penerima manfaat dan penugasan PIC lapangan (PBI-01)</p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('head.schools.create') }}" 
               class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs sm:text-sm font-semibold text-white bg-[#1C6654] hover:bg-[#164D42] rounded-xl shadow-xs transition-colors min-h-[44px]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Tambah Sekolah</span>
            </a>
        </div>
    </div>

    <!-- Metric Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 mb-6">
        <div class="bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#53645B] block mb-1">Sekolah Aktif</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-bold text-[#1C6654] tabular-nums">{{ $totalActive }}</span>
                <span class="text-xs font-medium text-[#53645B]">/ {{ $schools->count() }} total unit</span>
            </div>
            <span class="text-[11px] text-[#21684B] font-medium block mt-1">Siap Menerima Distribusi</span>
        </div>

        <div class="bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#53645B] block mb-1">Total Siswa Terdaftar</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-bold text-[#172923] tabular-nums">{{ number_format($totalBeneficiaries) }}</span>
                <span class="text-xs font-medium text-[#53645B]">siswa</span>
            </div>
            <span class="text-[11px] text-[#53645B] block mt-1">Master Data Pokok Siswa</span>
        </div>

        <div class="col-span-2 lg:col-span-1 bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#53645B] block mb-1">Integritas Historis</span>
            <div class="flex items-baseline gap-2">
                <span class="text-xl sm:text-2xl font-bold text-[#172923]">Non-Destruktif</span>
            </div>
            <span class="text-[11px] text-[#53645B] block mt-1">Sekolah dinonaktifkan tanpa hapus riwayat</span>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white rounded-xl border border-[#DCE5DD] p-4 mb-6 subtle-shadow">
        <form method="GET" action="{{ route('head.schools.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#53645B]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" 
                       name="q" 
                       value="{{ $search }}" 
                       placeholder="Cari nama sekolah, kode NPSN, atau alamat..."
                       class="w-full pl-10 pr-4 py-2.5 text-xs sm:text-sm rounded-lg border border-[#DCE5DD] bg-[#F5F7F3] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] focus:border-transparent min-h-[44px]">
            </div>

            <div class="flex items-center gap-2">
                <select name="status" 
                        onchange="this.form.submit()"
                        class="px-3 py-2.5 text-xs sm:text-sm rounded-lg border border-[#DCE5DD] bg-[#F5F7F3] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] text-[#172923] min-h-[44px]">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Hanya Aktif</option>
                    <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Hanya Nonaktif</option>
                </select>

                <button type="submit" 
                        class="px-4 py-2.5 text-xs sm:text-sm font-semibold text-white bg-[#1C6654] hover:bg-[#164D42] rounded-lg transition-colors min-h-[44px] cursor-pointer">
                    Cari
                </button>

                @if($search || $status !== 'all')
                    <a href="{{ route('head.schools.index') }}" 
                       class="px-3 py-2.5 text-xs sm:text-sm font-medium text-[#53645B] hover:text-[#172923] bg-[#EDF2ED] hover:bg-[#E2EBE2] rounded-lg transition-colors min-h-[44px] flex items-center justify-center">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- School Listing -->
    <div class="bg-white rounded-xl border border-[#DCE5DD] overflow-hidden subtle-shadow">
        <div class="p-4 border-b border-[#DCE5DD] flex items-center justify-between">
            <h2 class="text-sm font-bold text-[#172923] uppercase tracking-wider">
                Daftar Sekolah Binaan ({{ $schools->count() }})
            </h2>
            <span class="text-xs text-[#53645B]">Terisolasi untuk SPPG {{ $sppg->code }}</span>
        </div>

        @if ($schools->isEmpty())
            <div class="p-12 text-center">
                <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-[#EDF2ED] flex items-center justify-center text-[#53645B]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-[#172923]">Tidak ada sekolah ditemukan</h3>
                <p class="text-xs text-[#53645B] mt-1 max-w-sm mx-auto">
                    {{ $search ? 'Tidak ada sekolah yang cocok dengan kata kunci pencarian Anda.' : 'Belum ada sekolah yang terdaftar dalam binaan SPPG ini.' }}
                </p>
                <div class="mt-4">
                    @if($search)
                        <a href="{{ route('head.schools.index') }}" class="text-xs font-semibold text-[#1C6654] hover:underline">
                            Hapus filter pencarian
                        </a>
                    @else
                        <a href="{{ route('head.schools.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-[#1C6654] hover:bg-[#164D42] rounded-lg">
                            + Tambah Sekolah Pertama
                        </a>
                    @endif
                </div>
            </div>
        @else
            <!-- Desktop Table View (Hidden on mobile) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs sm:text-sm">
                    <thead>
                        <tr class="border-b border-[#DCE5DD] bg-[#FAFBF9] text-[#53645B] text-xs uppercase tracking-wider">
                            <th class="py-3 px-4">Kode / NPSN</th>
                            <th class="py-3 px-4">Sekolah & Alamat</th>
                            <th class="py-3 px-4">Jenjang</th>
                            <th class="py-3 px-4 text-right">Data Siswa</th>
                            <th class="py-3 px-4">PIC Ditugaskan</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EDF2ED]">
                        @foreach ($schools as $school)
                            @php
                                $pic = $school->picUsers->first();
                            @endphp
                            <tr class="hover:bg-[#F5F7F3] transition-colors {{ ! $school->is_active ? 'opacity-70 bg-[#FAFBF9]' : '' }}">
                                <td class="py-3.5 px-4 font-mono font-semibold text-xs text-[#1C6654]">
                                    {{ $school->school_code }}
                                </td>
                                <td class="py-3.5 px-4 font-medium text-[#172923]">
                                    <a href="{{ route('head.schools.show', $school) }}" class="hover:underline font-semibold block text-[#172923]">
                                        {{ $school->name }}
                                    </a>
                                    <span class="block text-[11px] text-[#53645B] truncate max-w-xs mt-0.5">{{ $school->address }}</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-[#EDF2ED] text-[#172923] border border-[#DCE5DD]">
                                        {{ $school->education_level }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-bold text-[#172923] tabular-nums">
                                    {{ number_format($school->registered_beneficiaries) }} 
                                    <span class="text-xs font-normal text-[#53645B]">siswa</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    @if ($pic)
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#1C6654]"></span>
                                            <span class="font-medium text-[#172923]">{{ $pic->name }}</span>
                                        </div>
                                        <span class="block text-[10px] text-[#53645B] truncate max-w-[150px]">{{ $pic->email }}</span>
                                    @else
                                        <span class="text-xs text-[#9B3232] italic flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            Belum ada PIC
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if ($school->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-[#EDF7F2] text-[#21684B] border border-[#BCE2D0]">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-[#FDF2F2] text-[#9B3232] border border-[#F5C2C2]">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <a href="{{ route('head.schools.show', $school) }}" 
                                           class="px-2.5 py-1 text-xs font-medium text-[#172923] bg-white border border-[#DCE5DD] rounded-lg hover:bg-[#F5F7F3] transition-colors"
                                           title="Lihat Detail">
                                            Detail
                                        </a>
                                        <a href="{{ route('head.schools.edit', $school) }}" 
                                           class="px-2.5 py-1 text-xs font-semibold text-[#1C6654] bg-[#EDF2ED] border border-[#DCE5DD] rounded-lg hover:bg-[#E2EBE2] transition-colors"
                                           title="Edit Sekolah">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('head.schools.toggle-active', $school) }}" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status aktif sekolah ini?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" 
                                                    class="px-2.5 py-1 text-xs font-medium {{ $school->is_active ? 'text-[#9B3232] hover:bg-[#FDF2F2]' : 'text-[#21684B] hover:bg-[#EDF7F2]' }} rounded-lg transition-colors cursor-pointer"
                                                    title="{{ $school->is_active ? 'Nonaktifkan Sekolah' : 'Aktifkan Sekolah' }}">
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

            <!-- Mobile Card View (Optimized for 320px - 430px) -->
            <div class="md:hidden divide-y divide-[#EDF2ED]">
                @foreach ($schools as $school)
                    @php
                        $pic = $school->picUsers->first();
                    @endphp
                    <div class="p-4 {{ ! $school->is_active ? 'bg-[#FAFBF9] opacity-75' : '' }}">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div>
                                <span class="font-mono text-[11px] font-bold text-[#1C6654] block leading-tight">{{ $school->school_code }}</span>
                                <a href="{{ route('head.schools.show', $school) }}" class="text-sm font-bold text-[#172923] hover:underline block mt-0.5">
                                    {{ $school->name }}
                                </a>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $school->is_active ? 'bg-[#EDF7F2] text-[#21684B] border border-[#BCE2D0]' : 'bg-[#FDF2F2] text-[#9B3232] border border-[#F5C2C2]' }}">
                                {{ $school->is_active ? 'AKTIF' : 'NONAKTIF' }}
                            </span>
                        </div>

                        <p class="text-xs text-[#53645B] line-clamp-2 mb-3">{{ $school->address }}</p>

                        <div class="grid grid-cols-2 gap-2 text-xs bg-[#F5F7F3] p-2.5 rounded-lg border border-[#DCE5DD] mb-3">
                            <div>
                                <span class="text-[10px] text-[#53645B] block uppercase font-medium">Jenjang</span>
                                <span class="font-semibold text-[#172923]">{{ $school->education_level }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-[#53645B] block uppercase font-medium">Data Siswa</span>
                                <span class="font-bold text-[#172923] tabular-nums">{{ number_format($school->registered_beneficiaries) }} anak</span>
                            </div>
                            <div class="col-span-2 pt-1 border-t border-[#DCE5DD]">
                                <span class="text-[10px] text-[#53645B] block uppercase font-medium">PIC Bertugas</span>
                                @if ($pic)
                                    <span class="font-medium text-[#172923]">{{ $pic->name }} <span class="text-[10px] text-[#53645B]">({{ $pic->email }})</span></span>
                                @else
                                    <span class="text-[#9B3232] italic text-xs">Belum ditugaskan</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-2 pt-1">
                            <a href="{{ route('head.schools.show', $school) }}" 
                               class="flex-1 py-2 text-center text-xs font-medium text-[#172923] bg-white border border-[#DCE5DD] rounded-lg hover:bg-[#F5F7F3] min-h-[38px] flex items-center justify-center">
                                Detail
                            </a>
                            <a href="{{ route('head.schools.edit', $school) }}" 
                               class="flex-1 py-2 text-center text-xs font-semibold text-[#1C6654] bg-[#EDF2ED] border border-[#DCE5DD] rounded-lg hover:bg-[#E2EBE2] min-h-[38px] flex items-center justify-center">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('head.schools.toggle-active', $school) }}" class="inline" onsubmit="return confirm('Ubah status aktif sekolah ini?')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" 
                                        class="px-3 py-2 text-xs font-medium {{ $school->is_active ? 'text-[#9B3232] bg-[#FDF2F2]' : 'text-[#21684B] bg-[#EDF7F2]' }} rounded-lg min-h-[38px] cursor-pointer">
                                    {{ $school->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-shell>
