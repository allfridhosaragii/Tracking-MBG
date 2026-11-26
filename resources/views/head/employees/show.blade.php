<x-app-shell title="Detail Pegawai : {{ $employee->name }}">
    <div class="space-y-5">
        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <a href="{{ route('head.employees.index') }}" class="text-xs font-semibold text-[#1C6654] hover:underline inline-flex items-center gap-1 mb-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Direktori Pegawai</span>
                </a>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="page-title">{{ $employee->name }}</h1>
                    <span class="px-2.5 py-0.5 rounded-lg text-xs font-semibold border {{ $employee->is_active ? 'bg-[#EDF7F2] text-[#21684B] border-[#BCE2D0]' : 'bg-[#FDF2F2] text-[#9B3232] border-[#F5C2C2]' }}">
                        {{ $employee->is_active ? 'Akun Aktif' : 'Akun Nonaktif' }}
                    </span>
                </div>
                <p class="text-xs text-[#53645B] mt-1 font-mono">{{ $employee->email }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="{{ route('head.employees.edit', $employee) }}"
                   class="min-h-[44px] px-4 py-2 text-xs font-semibold text-[#1C6654] bg-[#EDF7F2] hover:bg-[#DCF0E6] border border-[#BCE2D0] rounded-xl inline-flex items-center smooth-transition">
                    Ubah Data &amp; Role
                </a>

                <form method="POST" action="{{ route('head.employees.toggle-active', $employee) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                            onclick="return confirm('Ubah status aktif akun ini?')"
                            class="min-h-[44px] px-4 py-2 text-xs font-semibold {{ $employee->is_active ? 'text-[#9B3232] bg-[#FDF2F2] border border-[#F5C2C2]' : 'text-[#21684B] bg-[#EDF7F2] border border-[#BCE2D0]' }} rounded-xl cursor-pointer smooth-transition">
                        {{ $employee->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}
                    </button>
                </form>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <!-- Profile Column -->
            <x-card title="Profil Pelaksana">
                <div class="space-y-4 text-xs sm:text-sm">
                    <div class="flex items-center gap-3 pb-3.5 border-b border-[#E7ECE8]">
                        <div class="w-12 h-12 rounded-xl bg-[#EDF7F2] text-[#1C6654] font-bold text-base flex items-center justify-center shrink-0">
                            {{ strtoupper(substr($employee->name, 0, 2)) }}
                        </div>
                        <div>
                            <x-role-badge :role="$employee->role" />
                        </div>
                    </div>

                    <div>
                        <span class="eyebrow-label block">Nomor Telepon</span>
                        <span class="text-[#172923] font-medium mt-0.5 block">{{ $employee->phone ?? 'Belum diisi' }}</span>
                    </div>

                    <div>
                        <span class="eyebrow-label block">Unit SPPG</span>
                        <span class="text-[#172923] font-medium mt-0.5 block">{{ $sppg->name ?? 'SPPG MBG-Track' }}</span>
                    </div>

                    <div>
                        <span class="eyebrow-label block">Terakhir Login</span>
                        <span class="text-[#172923] font-medium mt-0.5 block tabular-nums">
                            @if ($employee->last_login_at)
                                {{ $employee->last_login_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB
                            @else
                                Belum pernah login
                            @endif
                        </span>
                    </div>
                </div>
            </x-card>

            <!-- Scope & Audit Trail -->
            <div class="lg:col-span-2 space-y-5">
                <x-card title="Cakupan Tugas &amp; Batas Hak Akses">
                    <div class="space-y-2.5 text-xs sm:text-sm text-[#53645B] leading-relaxed">
                        @if ($employee->role === \App\Enums\UserRole::PENGAWAS_GIZI)
                            <p><strong class="text-[#172923]">Formulasi &amp; Standar Gizi:</strong> Menyusun resep aktif beserta gramasi dan nilai zat gizi terukur.</p>
                            <p><strong class="text-[#172923]">Otorisasi QC:</strong> Menguji kelayakan organoleptik dan suhu inti sebelum makanan dimuat kurir.</p>
                        @elseif ($employee->role === \App\Enums\UserRole::ASISTEN_LAPANGAN)
                            <p><strong class="text-[#172923]">Eksekusi Dapur:</strong> Menjalankan tahapan batch produksi sesuai alokasi porsi resmi.</p>
                            <p><strong class="text-[#172923]">Logistik Sirkular:</strong> Menerima dan merekonsiliasi wadah ompreng kembali di dapur SPPG.</p>
                        @elseif ($employee->role === \App\Enums\UserRole::KURIR)
                            <p><strong class="text-[#172923]">Pengantaran Multi-Stop:</strong> Memeriksa kelaikan armada, menjalankan rute pengantaran, dan melampirkan foto serah terima.</p>
                            <p><strong class="text-[#172923]">Penjemputan Wadah:</strong> Mengambil kembali ompreng bersih dari sekolah binaan.</p>
                        @else
                            <p><strong class="text-[#172923]">Kepala Operasional SPPG:</strong> Mengelola sekolah binaan, hak akses staf, finalisasi rencana produksi, dan kontrol keberangkatan armada.</p>
                        @endif
                    </div>
                </x-card>

                <x-card title="Histori Jejak Audit Akun">
                    @if ($activityLogs->isEmpty())
                        <p class="py-4 text-center text-xs text-[#53645B]">Belum ada riwayat perubahan tambahan pada akun ini.</p>
                    @else
                        <div class="relative pl-5 space-y-4 border-l border-[#DCE5DD] text-xs">
                            @foreach ($activityLogs as $log)
                                <div class="relative">
                                    <span class="absolute -left-[25px] top-1 w-2.5 h-2.5 rounded-full bg-[#1C6654] border-2 border-white" aria-hidden="true"></span>
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
                                        <span class="text-[11px] text-[#53645B] tabular-nums">
                                            {{ $log->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB
                                        </span>
                                    </div>
                                    <p class="text-[#53645B] mt-0.5">Oleh: <strong class="text-[#172923]">{{ $log->actor?->name ?? 'Sistem' }}</strong></p>

                                    @if ($log->old_values && $log->new_values)
                                        <div class="mt-1.5 inset-surface p-2.5 text-[11px] text-[#53645B] space-y-0.5">
                                            @if (isset($log->old_values['role']) && isset($log->new_values['role']) && $log->old_values['role'] !== $log->new_values['role'])
                                                <div>Role: <span class="line-through">{{ $log->old_values['role'] }}</span> &rarr; <strong class="text-[#172923]">{{ $log->new_values['role'] }}</strong></div>
                                            @endif
                                            @if (isset($log->old_values['is_active']) && isset($log->new_values['is_active']) && $log->old_values['is_active'] !== $log->new_values['is_active'])
                                                <div>Status: <span class="line-through">{{ $log->old_values['is_active'] ? 'Aktif' : 'Nonaktif' }}</span> &rarr; <strong class="text-[#172923]">{{ $log->new_values['is_active'] ? 'Aktif' : 'Nonaktif' }}</strong></div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            </div>
        </div>
    </div>
</x-app-shell>
