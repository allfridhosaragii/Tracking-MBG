<x-app-shell title="Detail Pegawai : {{ $employee->name }} : MBG-Track">
    <div class="space-y-6">

        <!-- Top Header & Breadcrumb -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-[#53645B] mb-1.5">
                    <a href="{{ route('head.dashboard') }}" class="hover:text-[#1C6654] smooth-transition">Beranda</a>
                    <span aria-hidden="true">•</span>
                    <a href="{{ route('head.employees.index') }}" class="hover:text-[#1C6654] smooth-transition">Manajemen Pegawai</a>
                    <span aria-hidden="true">•</span>
                    <span class="text-[#172923] font-medium" aria-current="page">{{ $employee->name }}</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-[#172923]">
                        {{ $employee->name }}
                    </h1>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $employee->is_active ? 'bg-[#EDF7F2] text-[#1B573E] border border-[#BCE2D0]' : 'bg-[#FDF2F2] text-[#9B3232] border border-[#F5C2C2]' }}">
                        {{ $employee->is_active ? 'Akun Aktif' : 'Akun Nonaktif' }}
                    </span>
                </div>
                <p class="text-xs sm:text-sm text-[#53645B] mt-1 leading-relaxed">
                    Profil pelaksana internal, batas kewenangan operasional, dan riwayat audit perubahan.
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <a href="{{ route('head.employees.edit', $employee) }}" 
                   class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs sm:text-sm font-semibold text-[#172923] bg-white hover:bg-[#F5F7F3] border border-[#DCE5DD] rounded-xl subtle-shadow smooth-transition touch-target focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                    <svg class="w-4 h-4 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Ubah Data &amp; Role</span>
                </a>

                <form method="POST" action="{{ route('head.employees.toggle-active', $employee) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" 
                            onclick="return confirm('Apakah Anda yakin ingin mengubah status keaktifan akun ini?')"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs sm:text-sm font-semibold {{ $employee->is_active ? 'text-[#9B3232] bg-[#FDF2F2] hover:bg-[#FCE8E8] border border-[#F5C2C2]' : 'text-[#1B573E] bg-[#EDF7F2] hover:bg-[#DFEFE7] border border-[#BCE2D0]' }} rounded-xl smooth-transition touch-target cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                        @if ($employee->is_active)
                            <span>Nonaktifkan Akun</span>
                        @else
                            <span>Aktifkan Akun</span>
                        @endif
                    </button>
                </form>
            </div>
        </div>

        <!-- Grid Layout: Left Details + Right Permissions & Audit -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left Column: Profile Card -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 sm:p-6 subtle-shadow space-y-5">
                    <div class="flex items-center gap-3.5 pb-4 border-b border-[#DCE5DD]">
                        <div class="w-14 h-14 rounded-2xl bg-[#EDF2ED] text-[#1C6654] font-bold text-lg flex items-center justify-center shrink-0">
                            {{ strtoupper(substr($employee->name, 0, 2)) }}
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-[#172923]">{{ $employee->name }}</h2>
                            <div class="mt-1">
                                @if ($employee->role === \App\Enums\UserRole::PENGAWAS_GIZI)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-[#EBF3F8] text-[#285F80] border border-[#C5DCE8]">
                                        {{ $employee->role->label() }}
                                    </span>
                                @elseif ($employee->role === \App\Enums\UserRole::ASISTEN_LAPANGAN)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-[#FEF8ED] text-[#885D1C] border border-[#F6E1B9]">
                                        {{ $employee->role->label() }}
                                    </span>
                                @elseif ($employee->role === \App\Enums\UserRole::KURIR)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-[#F5F0FB] text-[#55327A] border border-[#DFD1F0]">
                                        {{ $employee->role->label() }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-[#EDF2ED] text-[#1C6654] border border-[#C8D9CB]">
                                        {{ $employee->role?->label() ?? 'Internal' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Contact Details -->
                    <div class="space-y-3 text-xs sm:text-sm">
                        <div>
                            <span class="text-[11px] font-semibold text-[#53645B] uppercase tracking-wider block">Alamat Email</span>
                            <span class="text-[#172923] font-medium break-all mt-0.5 block">{{ $employee->email }}</span>
                        </div>

                        <div>
                            <span class="text-[11px] font-semibold text-[#53645B] uppercase tracking-wider block">Nomor Telepon</span>
                            <span class="text-[#172923] font-medium mt-0.5 block">{{ $employee->phone ?? 'Belum diisi' }}</span>
                        </div>

                        <div>
                            <span class="text-[11px] font-semibold text-[#53645B] uppercase tracking-wider block">Unit Kerja SPPG</span>
                            <span class="text-[#172923] font-medium mt-0.5 block">{{ $sppg->name ?? 'SPPG MBG-Track' }}</span>
                            <span class="text-[11px] text-[#53645B] block">{{ $sppg->address ?? '' }}</span>
                        </div>

                        <div>
                            <span class="text-[11px] font-semibold text-[#53645B] uppercase tracking-wider block">Terakhir Masuk Sistem</span>
                            <span class="text-[#172923] font-medium mt-0.5 block">
                                @if ($employee->last_login_at)
                                    {{ $employee->last_login_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB
                                    <span class="text-[11px] text-[#53645B]">({{ $employee->last_login_at->diffForHumans() }})</span>
                                @else
                                    <span class="text-[#8A9A91]">Belum pernah login</span>
                                @endif
                            </span>
                        </div>

                        <div>
                            <span class="text-[11px] font-semibold text-[#53645B] uppercase tracking-wider block">Tanggal Registrasi</span>
                            <span class="text-[#172923] font-medium mt-0.5 block">
                                {{ $employee->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Role Permissions Matrix & Audit Log -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Role Operational Scope Card -->
                <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 sm:p-6 subtle-shadow space-y-4">
                    <h2 class="text-sm font-bold text-[#172923] uppercase tracking-wider pb-2 border-b border-[#DCE5DD]">
                        Cakupan Tugas &amp; Batas Hak Akses (Role: {{ $employee->role?->label() }})
                    </h2>

                    @if ($employee->role === \App\Enums\UserRole::PENGAWAS_GIZI)
                        <div class="space-y-3 text-xs sm:text-sm">
                            <div class="flex items-start gap-2.5">
                                <span class="w-2 h-2 rounded-full bg-[#285F80] mt-1.5 shrink-0" aria-hidden="true"></span>
                                <div>
                                    <strong class="text-[#172923]">Penyusunan &amp; Review Menu:</strong>
                                    <span class="text-[#53645B]"> Memeriksa standar gramasi bahan makanan dan kelayakan nutrisi harian.</span>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <span class="w-2 h-2 rounded-full bg-[#285F80] mt-1.5 shrink-0" aria-hidden="true"></span>
                                <div>
                                    <strong class="text-[#172923]">Verifikasi Mutu Pangan:</strong>
                                    <span class="text-[#53645B]"> Menguji sampel masakan dan memberikan verifikasi kelayakan konsumsi sebelum didistribusikan.</span>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <span class="w-2 h-2 rounded-full bg-[#9B3232] mt-1.5 shrink-0" aria-hidden="true"></span>
                                <div>
                                    <strong class="text-[#172923]">Batas Kewenangan:</strong>
                                    <span class="text-[#53645B]"> Tidak berwenang mengubah jadwal penugasan rute kurir atau memodifikasi akun staf.</span>
                                </div>
                            </div>
                        </div>
                    @elseif ($employee->role === \App\Enums\UserRole::ASISTEN_LAPANGAN)
                        <div class="space-y-3 text-xs sm:text-sm">
                            <div class="flex items-start gap-2.5">
                                <span class="w-2 h-2 rounded-full bg-[#885D1C] mt-1.5 shrink-0" aria-hidden="true"></span>
                                <div>
                                    <strong class="text-[#172923]">Runbook Produksi Dapur:</strong>
                                    <span class="text-[#53645B]"> Melaksanakan checklist persiapan bahan, waktu masak, dan pengemasan porsi.</span>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <span class="w-2 h-2 rounded-full bg-[#885D1C] mt-1.5 shrink-0" aria-hidden="true"></span>
                                <div>
                                    <strong class="text-[#172923]">Penimbangan &amp; Pengepakan:</strong>
                                    <span class="text-[#53645B]"> Memastikan jumlah porsi kemasan sesuai dengan permintaan terkonfirmasi sekolah.</span>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <span class="w-2 h-2 rounded-full bg-[#9B3232] mt-1.5 shrink-0" aria-hidden="true"></span>
                                <div>
                                    <strong class="text-[#172923]">Batas Kewenangan:</strong>
                                    <span class="text-[#53645B]"> Tidak berwenang menyetujui sertifikasi gizi atau mengubah kontrak kemitraan sekolah.</span>
                                </div>
                            </div>
                        </div>
                    @elseif ($employee->role === \App\Enums\UserRole::KURIR)
                        <div class="space-y-3 text-xs sm:text-sm">
                            <div class="flex items-start gap-2.5">
                                <span class="w-2 h-2 rounded-full bg-[#55327A] mt-1.5 shrink-0" aria-hidden="true"></span>
                                <div>
                                    <strong class="text-[#172923]">Eksekusi Misi Pengiriman:</strong>
                                    <span class="text-[#53645B]"> Membawa boks makanan sesuai urutan stop rute sekolah yang ditugaskan.</span>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <span class="w-2 h-2 rounded-full bg-[#55327A] mt-1.5 shrink-0" aria-hidden="true"></span>
                                <div>
                                    <strong class="text-[#172923]">Dokumentasi &amp; Serah Terima:</strong>
                                    <span class="text-[#53645B]"> Mengunggah bukti foto serah terima di sekolah dan mengembalikan tray kotor ke SPPG.</span>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <span class="w-2 h-2 rounded-full bg-[#9B3232] mt-1.5 shrink-0" aria-hidden="true"></span>
                                <div>
                                    <strong class="text-[#172923]">Batas Kewenangan:</strong>
                                    <span class="text-[#53645B]"> Tidak berwenang membatalkan alokasi porsi atau mengubah status menu gizi.</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="space-y-3 text-xs sm:text-sm">
                            <div class="flex items-start gap-2.5">
                                <span class="w-2 h-2 rounded-full bg-[#1C6654] mt-1.5 shrink-0" aria-hidden="true"></span>
                                <div>
                                    <strong class="text-[#172923]">Kepala Operasional SPPG:</strong>
                                    <span class="text-[#53645B]"> Mengendalikan seluruh rantai operasional internal SPPG, alokasi porsi, dan hak akses pegawai.</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Audit Trail History Card (Section 4 & 10) -->
                <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 sm:p-6 subtle-shadow space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-[#DCE5DD]">
                        <h2 class="text-sm font-bold text-[#172923] uppercase tracking-wider">
                            Histori Jejak Audit Akun &amp; Izin
                        </h2>
                        <span class="text-xs text-[#53645B]">Tercatat di sistem</span>
                    </div>

                    @if ($activityLogs->isEmpty())
                        <div class="py-6 text-center text-xs text-[#53645B]">
                            Belum ada riwayat audit perubahan tambahan pada akun ini.
                        </div>
                    @else
                        <div class="relative pl-6 space-y-4 border-l border-[#DCE5DD] text-xs">
                            @foreach ($activityLogs as $log)
                                <div class="relative">
                                    <span class="absolute -left-[31px] top-1 w-3 h-3 rounded-full bg-[#1C6654] border-2 border-white shadow-xs" aria-hidden="true"></span>
                                    <div>
                                        <div class="flex flex-wrap items-center justify-between gap-1">
                                            <span class="font-bold text-[#172923]">
                                                @if ($log->event === 'created')
                                                    Registrasi Akun Pegawai
                                                @elseif ($log->event === 'role_changed')
                                                    Perubahan Role / Hak Akses
                                                @elseif ($log->event === 'status_toggled')
                                                    Perubahan Status Aktif
                                                @elseif ($log->event === 'password_reset')
                                                    Reset Kata Sandi
                                                @else
                                                    Pembaruan Data Akun
                                                @endif
                                            </span>
                                            <span class="text-[11px] text-[#53645B]">
                                                {{ $log->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB
                                            </span>
                                        </div>

                                        <p class="text-[#53645B] mt-0.5">
                                            Oleh: <strong class="text-[#172923]">{{ $log->actor?->name ?? 'Sistem' }}</strong>
                                            @if ($log->metadata && isset($log->metadata['action']))
                                                • {{ $log->metadata['action'] }}
                                            @endif
                                        </p>

                                        @if ($log->old_values && $log->new_values)
                                            <div class="mt-1.5 p-2 rounded-lg bg-[#F5F7F3] border border-[#DCE5DD] text-[11px] text-[#53645B] space-y-0.5">
                                                @if (isset($log->old_values['role']) && isset($log->new_values['role']) && $log->old_values['role'] !== $log->new_values['role'])
                                                    <div>Role: <span class="line-through">{{ $log->old_values['role'] }}</span> → <strong class="text-[#172923]">{{ $log->new_values['role'] }}</strong></div>
                                                @endif
                                                @if (isset($log->old_values['is_active']) && isset($log->new_values['is_active']) && $log->old_values['is_active'] !== $log->new_values['is_active'])
                                                    <div>Status: <span class="line-through">{{ $log->old_values['is_active'] ? 'Aktif' : 'Nonaktif' }}</span> → <strong class="text-[#172923]">{{ $log->new_values['is_active'] ? 'Aktif' : 'Nonaktif' }}</strong></div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>

        </div>

    </div>
</x-app-shell>
