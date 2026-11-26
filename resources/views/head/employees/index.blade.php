<x-app-shell title="Direktori Pegawai & Role">
    <div class="space-y-5">
        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <span class="eyebrow-label text-[#1C6654] block mb-1">Kepala SPPG • {{ $sppg->code ?? 'SPPG' }}</span>
                <h1 class="page-title">Direktori Pegawai &amp; Pembagian Role</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-1">Kelola kredensial serta tanggung jawab Pengawas Gizi, Asisten Lapangan, dan Kurir di {{ $sppg->name ?? 'SPPG' }}.</p>
            </div>

            <a href="{{ route('head.employees.create') }}"
               class="min-h-[44px] px-4 py-2.5 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs sm:text-sm font-semibold inline-flex items-center justify-center gap-2 shrink-0 smooth-transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Pegawai</span>
            </a>
        </header>

        <!-- Compact Role Summary Strip -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            <div class="luxury-surface p-3.5 sm:p-4">
                <span class="eyebrow-label block">Total Staf</span>
                <span class="text-xl sm:text-2xl font-bold text-[#172923] tabular-nums mt-1 block">{{ $totalStaff }}</span>
            </div>
            <div class="luxury-surface p-3.5 sm:p-4">
                <span class="eyebrow-label block">Akun Aktif</span>
                <span class="text-xl sm:text-2xl font-bold text-[#1C6654] tabular-nums mt-1 block">{{ $totalActive }}</span>
            </div>
            <div class="luxury-surface p-3.5 sm:p-4">
                <span class="eyebrow-label block">Pengawas Gizi</span>
                <span class="text-xl sm:text-2xl font-bold text-[#285F80] tabular-nums mt-1 block">{{ $totalNutrition }}</span>
            </div>
            <div class="luxury-surface p-3.5 sm:p-4">
                <span class="eyebrow-label block">Asisten Dapur</span>
                <span class="text-xl sm:text-2xl font-bold text-[#885D1C] tabular-nums mt-1 block">{{ $totalProduction }}</span>
            </div>
            <div class="luxury-surface p-3.5 sm:p-4 col-span-2 sm:col-span-1">
                <span class="eyebrow-label block">Kurir Rute</span>
                <span class="text-xl sm:text-2xl font-bold text-[#55327A] tabular-nums mt-1 block">{{ $totalDrivers }}</span>
            </div>
        </div>

        <!-- Clean Filter Bar -->
        <form method="GET" action="{{ route('head.employees.index') }}" class="luxury-surface p-3.5 sm:p-4 flex flex-col lg:flex-row items-stretch lg:items-center gap-2.5">
            <div class="relative flex-1">
                <label for="q" class="sr-only">Cari Pegawai</label>
                <svg class="w-4 h-4 text-[#53645B] absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text"
                       id="q"
                       name="q"
                       value="{{ $search }}"
                       placeholder="Cari nama, email, atau nomor telepon..."
                       class="w-full min-h-[44px] pl-10 pr-4 py-2 text-xs sm:text-sm text-[#172923] bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl" />
            </div>

            <div class="grid grid-cols-2 sm:flex sm:items-center gap-2">
                <select id="role"
                        name="role"
                        onchange="this.form.submit()"
                        aria-label="Filter Role"
                        class="min-h-[44px] px-3 py-2 text-xs sm:text-sm text-[#172923] bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl">
                    <option value="all" {{ $roleFilter === 'all' ? 'selected' : '' }}>Semua Role</option>
                    <option value="PENGAWAS_GIZI" {{ $roleFilter === 'PENGAWAS_GIZI' ? 'selected' : '' }}>Pengawas Gizi</option>
                    <option value="ASISTEN_LAPANGAN" {{ $roleFilter === 'ASISTEN_LAPANGAN' ? 'selected' : '' }}>Asisten Lapangan</option>
                    <option value="KURIR" {{ $roleFilter === 'KURIR' ? 'selected' : '' }}>Kurir</option>
                    <option value="KEPALA_SPPG" {{ $roleFilter === 'KEPALA_SPPG' ? 'selected' : '' }}>Kepala SPPG</option>
                </select>

                <select id="status"
                        name="status"
                        onchange="this.form.submit()"
                        aria-label="Filter Status"
                        class="min-h-[44px] px-3 py-2 text-xs sm:text-sm text-[#172923] bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl">
                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Hanya Aktif</option>
                    <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Hanya Nonaktif</option>
                </select>

                <button type="submit"
                        class="min-h-[44px] px-4 py-2 text-xs sm:text-sm font-semibold text-white bg-[#1C6654] hover:bg-[#164D42] rounded-xl cursor-pointer smooth-transition">
                    Terapkan
                </button>

                @if ($search || $roleFilter !== 'all' || $statusFilter !== 'all')
                    <a href="{{ route('head.employees.index') }}"
                       class="min-h-[44px] px-3 py-2 text-xs font-semibold text-[#53645B] hover:text-[#172923] bg-[#EDF2ED] rounded-xl inline-flex items-center justify-center">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        <!-- Employee Listing -->
        <div class="luxury-surface overflow-hidden">
            @if ($employees->isEmpty())
                <div class="p-10 text-center">
                    <h2 class="text-sm font-bold text-[#172923]">Tidak Ada Data Pegawai</h2>
                    <p class="text-xs text-[#53645B] mt-1 max-w-md mx-auto leading-relaxed">
                        @if ($search || $roleFilter !== 'all' || $statusFilter !== 'all')
                            Tidak ditemukan pegawai yang cocok dengan filter aktif.
                        @else
                            Belum ada pegawai internal terdaftar pada SPPG ini.
                        @endif
                    </p>
                    <div class="mt-4">
                        @if ($search || $roleFilter !== 'all' || $statusFilter !== 'all')
                            <a href="{{ route('head.employees.index') }}" class="min-h-[44px] inline-flex items-center px-4 py-2 text-xs font-semibold text-[#172923] bg-[#EDF2ED] rounded-xl">
                                Bersihkan Filter
                            </a>
                        @else
                            <a href="{{ route('head.employees.create') }}" class="min-h-[44px] inline-flex items-center px-4 py-2 text-xs font-semibold text-white bg-[#1C6654] rounded-xl">
                                Tambah Pegawai Baru
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
                                <th>Nama &amp; Kontak</th>
                                <th>Role Operasional</th>
                                <th>Status</th>
                                <th>Login Terakhir</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($employees as $employee)
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-[#EDF7F2] text-[#1C6654] font-bold text-xs flex items-center justify-center shrink-0">
                                                {{ strtoupper(substr($employee->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <a href="{{ route('head.employees.show', $employee) }}" class="font-semibold text-[#172923] hover:text-[#1C6654] block">
                                                    {{ $employee->name }}
                                                </a>
                                                <span class="text-xs text-[#53645B] font-mono block">{{ $employee->email }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <x-role-badge :role="$employee->role" />
                                    </td>
                                    <td>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold border {{ $employee->is_active ? 'bg-[#EDF7F2] text-[#21684B] border-[#BCE2D0]' : 'bg-[#FDF2F2] text-[#9B3232] border-[#F5C2C2]' }}">
                                            {{ $employee->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="text-xs text-[#53645B]">
                                        @if ($employee->last_login_at)
                                            <span class="font-medium text-[#172923] tabular-nums block">{{ $employee->last_login_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</span>
                                        @else
                                            <span>Belum pernah login</span>
                                        @endif
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5 justify-end">
                                            <a href="{{ route('head.employees.show', $employee) }}"
                                               class="min-h-[44px] px-3 py-2 text-xs font-semibold text-[#172923] bg-white hover:bg-[#F5F7F3] border border-[#DCE5DD] rounded-xl inline-flex items-center">
                                                Detail
                                            </a>
                                            <a href="{{ route('head.employees.edit', $employee) }}"
                                               class="min-h-[44px] px-3 py-2 text-xs font-semibold text-[#1C6654] bg-[#EDF7F2] hover:bg-[#DCF0E6] border border-[#BCE2D0] rounded-xl inline-flex items-center">
                                                Edit
                                            </a>
                                            <form method="POST" action="{{ route('head.employees.toggle-active', $employee) }}" class="inline" onsubmit="return confirm('Ubah status aktif pegawai ini?')">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                        class="min-h-[44px] px-3 py-2 text-xs font-semibold {{ $employee->is_active ? 'text-[#9B3232] bg-[#FDF2F2] border border-[#F5C2C2]' : 'text-[#21684B] bg-[#EDF7F2] border border-[#BCE2D0]' }} rounded-xl cursor-pointer">
                                                    {{ $employee->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
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
                    @foreach ($employees as $employee)
                        <div class="p-4 space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('head.employees.show', $employee) }}" class="text-sm font-bold text-[#172923] hover:text-[#1C6654] block leading-snug">
                                        {{ $employee->name }}
                                    </a>
                                    <span class="text-xs font-mono text-[#53645B] block break-all mt-0.5">{{ $employee->email }}</span>
                                </div>
                                <span class="shrink-0 px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $employee->is_active ? 'bg-[#EDF7F2] text-[#21684B] border-[#BCE2D0]' : 'bg-[#FDF2F2] text-[#9B3232] border-[#F5C2C2]' }}">
                                    {{ $employee->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-2 text-xs">
                                <x-role-badge :role="$employee->role" />
                                <span class="text-[11px] text-[#53645B] font-mono">{{ $employee->phone ?? '-' }}</span>
                            </div>

                            <div class="flex items-center gap-2 pt-1">
                                <a href="{{ route('head.employees.show', $employee) }}"
                                   class="flex-1 min-h-[44px] text-xs font-semibold text-[#172923] bg-white border border-[#DCE5DD] rounded-xl inline-flex items-center justify-center">
                                    Detail &amp; Audit
                                </a>
                                <a href="{{ route('head.employees.edit', $employee) }}"
                                   class="flex-1 min-h-[44px] text-xs font-semibold text-[#1C6654] bg-[#EDF7F2] border border-[#BCE2D0] rounded-xl inline-flex items-center justify-center">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('head.employees.toggle-active', $employee) }}" class="inline" onsubmit="return confirm('Ubah status aktif pegawai ini?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="min-h-[44px] px-3.5 text-xs font-semibold {{ $employee->is_active ? 'text-[#9B3232] bg-[#FDF2F2] border border-[#F5C2C2]' : 'text-[#21684B] bg-[#EDF7F2] border border-[#BCE2D0]' }} rounded-xl cursor-pointer">
                                        {{ $employee->is_active ? 'Nonaktif' : 'Aktifkan' }}
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
