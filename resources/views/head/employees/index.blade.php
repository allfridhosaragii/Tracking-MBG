<x-app-shell title="Direktori Pegawai & Role SPPG : MBG-Track">
    <div class="space-y-6">

        <!-- Top Header & Breadcrumb -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-[#53645B] mb-1.5">
                    <a href="{{ route('head.dashboard') }}" class="hover:text-[#1C6654] smooth-transition">Beranda</a>
                    <span aria-hidden="true">•</span>
                    <span class="text-[#172923] font-medium" aria-current="page">Manajemen Pegawai</span>
                </nav>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-[#172923]">
                    Direktori Pegawai &amp; Pembagian Role SPPG
                </h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-1 max-w-2xl leading-relaxed">
                    Kelola pelaksana operasional internal {{ $sppg->name ?? 'SPPG' }}: Pengawas Gizi, Asisten Lapangan, dan Kurir.
                </p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ route('head.employees.create') }}" 
                   class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs sm:text-sm font-semibold text-white bg-[#1C6654] hover:bg-[#164D42] rounded-xl shadow-xs smooth-transition touch-target focus:outline-none focus:ring-2 focus:ring-[#1C6654] focus:ring-offset-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Pegawai</span>
                </a>
            </div>
        </div>

        <!-- Metrics Ribbon (Real Scoped Aggregation with 320px reflow) -->
        <div class="grid grid-cols-1 min-[380px]:grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
            <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-[#DCE5DD] subtle-shadow">
                <div class="text-[11px] font-medium text-[#53645B] uppercase tracking-wider">Total Pegawai</div>
                <div class="mt-1 text-lg sm:text-2xl font-bold text-[#172923]">{{ $totalStaff }}</div>
                <div class="mt-0.5 text-[11px] text-[#53645B]">Terdaftar di unit SPPG</div>
            </div>
            <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-[#DCE5DD] subtle-shadow">
                <div class="text-[11px] font-medium text-[#53645B] uppercase tracking-wider">Akun Aktif</div>
                <div class="mt-1 text-lg sm:text-2xl font-bold text-[#1C6654]">{{ $totalActive }}</div>
                <div class="mt-0.5 text-[11px] text-[#53645B]">Dapat login &amp; bertugas</div>
            </div>
            <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-[#DCE5DD] subtle-shadow">
                <div class="text-[11px] font-medium text-[#285F80] uppercase tracking-wider">Pengawas Gizi</div>
                <div class="mt-1 text-lg sm:text-2xl font-bold text-[#285F80]">{{ $totalNutrition }}</div>
                <div class="mt-0.5 text-[11px] text-[#53645B]">Review gizi &amp; menu</div>
            </div>
            <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-[#DCE5DD] subtle-shadow">
                <div class="text-[11px] font-medium text-[#885D1C] uppercase tracking-wider">Asisten Lapangan</div>
                <div class="mt-1 text-lg sm:text-2xl font-bold text-[#885D1C]">{{ $totalProduction }}</div>
                <div class="mt-0.5 text-[11px] text-[#53645B]">Runbook &amp; porsi</div>
            </div>
            <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-[#DCE5DD] subtle-shadow col-span-1 min-[380px]:col-span-2 sm:col-span-1">
                <div class="text-[11px] font-medium text-[#55327A] uppercase tracking-wider">Kurir</div>
                <div class="mt-1 text-lg sm:text-2xl font-bold text-[#55327A]">{{ $totalDrivers }}</div>
                <div class="mt-0.5 text-[11px] text-[#53645B]">Distribusi multi-stop</div>
            </div>
        </div>

        <!-- Filter & Search Section -->
        <div class="bg-white p-4 rounded-xl border border-[#DCE5DD] subtle-shadow">
            <form method="GET" action="{{ route('head.employees.index') }}" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                <div class="flex-1 relative">
                    <label for="q" class="sr-only">Cari Pegawai</label>
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#53645B]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" 
                           id="q" 
                           name="q" 
                           value="{{ $search }}" 
                           placeholder="Cari nama, email, atau nomor telepon..." 
                           class="w-full pl-10 pr-4 py-2.5 text-xs sm:text-sm text-[#172923] bg-[#F5F7F3] border border-[#DCE5DD] rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] smooth-transition" />
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <div>
                        <label for="role" class="sr-only">Filter Role</label>
                        <select id="role" 
                                name="role" 
                                onchange="this.form.submit()"
                                class="px-3 py-2.5 text-xs sm:text-sm text-[#172923] bg-[#F5F7F3] border border-[#DCE5DD] rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] smooth-transition touch-target">
                            <option value="all" {{ $roleFilter === 'all' ? 'selected' : '' }}>Semua Role</option>
                            <option value="PENGAWAS_GIZI" {{ $roleFilter === 'PENGAWAS_GIZI' ? 'selected' : '' }}>Pengawas Gizi</option>
                            <option value="ASISTEN_LAPANGAN" {{ $roleFilter === 'ASISTEN_LAPANGAN' ? 'selected' : '' }}>Asisten Lapangan</option>
                            <option value="KURIR" {{ $roleFilter === 'KURIR' ? 'selected' : '' }}>Kurir</option>
                            <option value="KEPALA_SPPG" {{ $roleFilter === 'KEPALA_SPPG' ? 'selected' : '' }}>Kepala SPPG</option>
                        </select>
                    </div>

                    <div>
                        <label for="status" class="sr-only">Filter Status</label>
                        <select id="status" 
                                name="status" 
                                onchange="this.form.submit()"
                                class="px-3 py-2.5 text-xs sm:text-sm text-[#172923] bg-[#F5F7F3] border border-[#DCE5DD] rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] smooth-transition touch-target">
                            <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Semua Status</option>
                            <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Hanya Aktif</option>
                            <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Hanya Nonaktif</option>
                        </select>
                    </div>

                    <button type="submit" 
                            class="px-4 py-2.5 text-xs sm:text-sm font-semibold text-[#172923] bg-[#EDF2ED] hover:bg-[#DCE5DD] rounded-xl smooth-transition touch-target focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                        Terapkan
                    </button>

                    @if ($search || $roleFilter !== 'all' || $statusFilter !== 'all')
                        <a href="{{ route('head.employees.index') }}" 
                           class="px-3 py-2.5 text-xs text-[#53645B] hover:text-[#9B3232] smooth-transition flex items-center touch-target"
                           title="Reset filter">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Employees List Section -->
        @if ($employees->isEmpty())
            <!-- Empty State (R-27) -->
            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-8 sm:p-12 text-center subtle-shadow">
                <div class="w-12 h-12 rounded-2xl bg-[#EDF2ED] text-[#1C6654] flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h2 class="text-base sm:text-lg font-bold text-[#172923]">Tidak Ada Data Pegawai</h2>
                <p class="text-xs sm:text-sm text-[#53645B] mt-1.5 max-w-md mx-auto leading-relaxed">
                    @if ($search || $roleFilter !== 'all' || $statusFilter !== 'all')
                        Tidak ditemukan pegawai yang cocok dengan filter pencarian aktif. Silakan sesuaikan kata kunci atau reset filter.
                    @else
                        Belum ada pegawai internal yang terdaftar pada SPPG ini. Tambahkan pegawai pertama untuk memulai pembagian tugas.
                    @endif
                </p>
                <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                    @if ($search || $roleFilter !== 'all' || $statusFilter !== 'all')
                        <a href="{{ route('head.employees.index') }}" 
                           class="inline-flex items-center justify-center px-4 py-2.5 text-xs sm:text-sm font-semibold text-[#172923] bg-[#EDF2ED] hover:bg-[#DCE5DD] rounded-xl smooth-transition touch-target focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                            Bersihkan Filter
                        </a>
                    @else
                        <a href="{{ route('head.employees.create') }}" 
                           class="inline-flex items-center justify-center px-4 py-2.5 text-xs sm:text-sm font-semibold text-white bg-[#1C6654] hover:bg-[#164D42] rounded-xl smooth-transition touch-target focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                            Tambah Pegawai Baru
                        </a>
                    @endif
                </div>
            </div>
        @else
            <!-- Mobile Card Stack (Viewport < 768px) -->
            <div class="md:hidden space-y-3.5">
                @foreach ($employees as $employee)
                    <div class="bg-white p-4 rounded-xl border border-[#DCE5DD] subtle-shadow space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-[#EDF2ED] text-[#1C6654] font-bold text-sm flex items-center justify-center shrink-0">
                                    {{ strtoupper(substr($employee->name, 0, 2)) }}
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-[#172923] leading-snug">
                                        <a href="{{ route('head.employees.show', $employee) }}" class="hover:text-[#1C6654] smooth-transition">
                                            {{ $employee->name }}
                                        </a>
                                    </h3>
                                    <div class="text-xs text-[#53645B] mt-0.5 break-all">{{ $employee->email }}</div>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $employee->is_active ? 'bg-[#EDF7F2] text-[#1B573E] border border-[#BCE2D0]' : 'bg-[#FDF2F2] text-[#9B3232] border border-[#F5C2C2]' }}">
                                {{ $employee->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>

                        <!-- Role & Contact Details -->
                        <div class="pt-2 border-t border-[#F0F4F1] flex flex-wrap items-center justify-between gap-2 text-xs">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-[#53645B]">Role:</span>
                                @if ($employee->role === \App\Enums\UserRole::PENGAWAS_GIZI)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-[#EBF3F8] text-[#285F80] border border-[#C5DCE8]">
                                        {{ $employee->role->label() }}
                                    </span>
                                @elseif ($employee->role === \App\Enums\UserRole::ASISTEN_LAPANGAN)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-[#FEF8ED] text-[#885D1C] border border-[#F6E1B9]">
                                        {{ $employee->role->label() }}
                                    </span>
                                @elseif ($employee->role === \App\Enums\UserRole::KURIR)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-[#F5F0FB] text-[#55327A] border border-[#DFD1F0]">
                                        {{ $employee->role->label() }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-[#EDF2ED] text-[#1C6654] border border-[#C8D9CB]">
                                        {{ $employee->role?->label() ?? 'Internal' }}
                                    </span>
                                @endif
                            </div>

                            <div class="text-[11px] text-[#53645B]">
                                {{ $employee->phone ?? 'Tidak ada nomor telepon' }}
                            </div>
                        </div>

                        <!-- Card Action Buttons -->
                        <div class="pt-2 border-t border-[#F0F4F1] flex items-center justify-end gap-2">
                            <a href="{{ route('head.employees.show', $employee) }}" 
                               class="px-3 py-2 text-xs font-semibold text-[#1C6654] bg-[#EDF2ED] hover:bg-[#DCE5DD] rounded-lg smooth-transition touch-target flex items-center">
                                Detail &amp; Izin
                            </a>
                            <a href="{{ route('head.employees.edit', $employee) }}" 
                               class="px-3 py-2 text-xs font-semibold text-[#172923] bg-[#F5F7F3] hover:bg-[#E5EAE6] rounded-lg smooth-transition touch-target flex items-center">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('head.employees.toggle-active', $employee) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" 
                                        onclick="return confirm('Apakah Anda yakin ingin mengubah status aktif pegawai ini?')"
                                        class="px-3 py-2 text-xs font-semibold {{ $employee->is_active ? 'text-[#9B3232] hover:bg-[#FDF2F2]' : 'text-[#1B573E] hover:bg-[#EDF7F2]' }} rounded-lg smooth-transition touch-target cursor-pointer flex items-center">
                                    {{ $employee->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Desktop Table (Viewport >= 768px) -->
            <div class="hidden md:block bg-white rounded-xl border border-[#DCE5DD] subtle-shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#F5F7F3] border-b border-[#DCE5DD] text-[11px] font-bold text-[#53645B] uppercase tracking-wider">
                                <th scope="col" class="py-3.5 px-4">Nama &amp; Kontak Pegawai</th>
                                <th scope="col" class="py-3.5 px-4">Role &amp; Tanggung Jawab</th>
                                <th scope="col" class="py-3.5 px-4">Status Akun</th>
                                <th scope="col" class="py-3.5 px-4">Terakhir Login</th>
                                <th scope="col" class="py-3.5 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#DCE5DD] text-xs sm:text-sm">
                            @foreach ($employees as $employee)
                                <tr class="hover:bg-[#FAFBF9] smooth-transition">
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-[#EDF2ED] text-[#1C6654] font-bold text-xs flex items-center justify-center shrink-0">
                                                {{ strtoupper(substr($employee->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <a href="{{ route('head.employees.show', $employee) }}" class="font-bold text-[#172923] hover:text-[#1C6654] smooth-transition">
                                                    {{ $employee->name }}
                                                </a>
                                                <div class="text-xs text-[#53645B] mt-0.5">{{ $employee->email }}</div>
                                                @if ($employee->phone)
                                                    <div class="text-[11px] text-[#53645B]">{{ $employee->phone }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if ($employee->role === \App\Enums\UserRole::PENGAWAS_GIZI)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-[#EBF3F8] text-[#285F80] border border-[#C5DCE8]">
                                                {{ $employee->role->label() }}
                                            </span>
                                        @elseif ($employee->role === \App\Enums\UserRole::ASISTEN_LAPANGAN)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-[#FEF8ED] text-[#885D1C] border border-[#F6E1B9]">
                                                {{ $employee->role->label() }}
                                            </span>
                                        @elseif ($employee->role === \App\Enums\UserRole::KURIR)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-[#F5F0FB] text-[#55327A] border border-[#DFD1F0]">
                                                {{ $employee->role->label() }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-[#EDF2ED] text-[#1C6654] border border-[#C8D9CB]">
                                                {{ $employee->role?->label() ?? 'Internal' }}
                                            </span>
                                        @endif
                                        <div class="text-[11px] text-[#53645B] mt-1">{{ $employee->role?->description() }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $employee->is_active ? 'bg-[#EDF7F2] text-[#1B573E] border border-[#BCE2D0]' : 'bg-[#FDF2F2] text-[#9B3232] border border-[#F5C2C2]' }}">
                                            {{ $employee->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-[#53645B] text-xs">
                                        @if ($employee->last_login_at)
                                            <div class="font-medium text-[#172923]">{{ $employee->last_login_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</div>
                                            <div class="text-[11px] text-[#53645B]">{{ $employee->last_login_at->diffForHumans() }}</div>
                                        @else
                                            <span class="text-[#8A9A91]">Belum pernah login</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="inline-flex items-center gap-1.5 justify-end">
                                            <a href="{{ route('head.employees.show', $employee) }}" 
                                               class="p-2 text-[#1C6654] hover:bg-[#EDF2ED] rounded-lg smooth-transition touch-target"
                                               title="Lihat detail dan audit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                <span class="sr-only">Detail {{ $employee->name }}</span>
                                            </a>
                                            <a href="{{ route('head.employees.edit', $employee) }}" 
                                               class="p-2 text-[#53645B] hover:text-[#172923] hover:bg-[#EDF2ED] rounded-lg smooth-transition touch-target"
                                               title="Ubah data dan role">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                <span class="sr-only">Edit {{ $employee->name }}</span>
                                            </a>
                                            <form method="POST" action="{{ route('head.employees.toggle-active', $employee) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" 
                                                        onclick="return confirm('Apakah Anda yakin ingin mengubah status aktif pegawai ini?')"
                                                        class="p-2 {{ $employee->is_active ? 'text-[#9B3232] hover:bg-[#FDF2F2]' : 'text-[#1B573E] hover:bg-[#EDF7F2]' }} rounded-lg smooth-transition touch-target cursor-pointer"
                                                        title="{{ $employee->is_active ? 'Nonaktifkan akun' : 'Aktifkan akun' }}">
                                                    @if ($employee->is_active)
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                        </svg>
                                                        <span class="sr-only">Nonaktifkan {{ $employee->name }}</span>
                                                    @else
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                        <span class="sr-only">Aktifkan {{ $employee->name }}</span>
                                                    @endif
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>
</x-app-shell>
