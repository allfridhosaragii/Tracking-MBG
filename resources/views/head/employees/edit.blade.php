<x-app-shell title="Ubah Pegawai : {{ $employee->name }} : MBG-Track">
    <div class="max-w-3xl mx-auto space-y-6">

        <!-- Top Header & Breadcrumb -->
        <div>
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-[#53645B] mb-1.5">
                <a href="{{ route('head.dashboard') }}" class="hover:text-[#1C6654] smooth-transition">Beranda</a>
                <span aria-hidden="true">•</span>
                <a href="{{ route('head.employees.index') }}" class="hover:text-[#1C6654] smooth-transition">Manajemen Pegawai</a>
                <span aria-hidden="true">•</span>
                <a href="{{ route('head.employees.show', $employee) }}" class="hover:text-[#1C6654] smooth-transition">{{ $employee->name }}</a>
                <span aria-hidden="true">•</span>
                <span class="text-[#172923] font-medium" aria-current="page">Ubah Data</span>
            </nav>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-[#172923]">
                Ubah Data &amp; Hak Akses Pegawai
            </h1>
            <p class="text-xs sm:text-sm text-[#53645B] mt-1 leading-relaxed">
                Perbarui profil kontak, status aktif akun, dan penugasan role operasional pegawai.
            </p>
        </div>

        <!-- Main Edit Form Card -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 sm:p-7 subtle-shadow">
            <form method="POST" action="{{ route('head.employees.update', $employee) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Section: Identitas & Kontak -->
                <div class="space-y-4">
                    <h2 class="text-sm font-bold text-[#172923] uppercase tracking-wider pb-2 border-b border-[#DCE5DD]">
                        Identitas &amp; Kontak Pegawai
                    </h2>

                    <div>
                        <label for="name" class="block text-xs sm:text-sm font-semibold text-[#172923] mb-1.5">
                            Nama Lengkap <span class="text-[#9B3232]">*</span>
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $employee->name) }}" 
                               required 
                               class="w-full px-3.5 py-2.5 text-xs sm:text-sm text-[#172923] bg-[#F5F7F3] border @error('name') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] smooth-transition" />
                        @error('name')
                            <p class="mt-1 text-xs text-[#9B3232]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="block text-xs sm:text-sm font-semibold text-[#172923] mb-1.5">
                                Alamat Email Masuk <span class="text-[#9B3232]">*</span>
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $employee->email) }}" 
                                   required 
                                   class="w-full px-3.5 py-2.5 text-xs sm:text-sm text-[#172923] bg-[#F5F7F3] border @error('email') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] smooth-transition" />
                            @error('email')
                                <p class="mt-1 text-xs text-[#9B3232]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phone" class="block text-xs sm:text-sm font-semibold text-[#172923] mb-1.5">
                                Nomor Telepon / WhatsApp
                            </label>
                            <input type="tel" 
                                   id="phone" 
                                   name="phone" 
                                   value="{{ old('phone', $employee->phone) }}" 
                                   class="w-full px-3.5 py-2.5 text-xs sm:text-sm text-[#172923] bg-[#F5F7F3] border @error('phone') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] smooth-transition" />
                            @error('phone')
                                <p class="mt-1 text-xs text-[#9B3232]">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Section: Pemilihan Role Operasional -->
                <div class="space-y-4 pt-2">
                    <div class="flex items-center justify-between pb-2 border-b border-[#DCE5DD]">
                        <h2 class="text-sm font-bold text-[#172923] uppercase tracking-wider">
                            Role Operasional Internal <span class="text-[#9B3232]">*</span>
                        </h2>
                        @if ($employee->id === auth()->id())
                            <span class="text-xs font-semibold text-[#885D1C] bg-[#FEF8ED] px-2.5 py-0.5 rounded-full border border-[#F6E1B9]">
                                Akun Anda Sendiri
                            </span>
                        @endif
                    </div>

                    @if ($employee->id === auth()->id())
                        <div class="p-3.5 rounded-xl bg-[#FEF8ED] border border-[#F6E1B9] text-xs text-[#885D1C]">
                            <strong>Catatan Kebijakan:</strong> Anda sedang menyunting akun Anda sendiri sebagai Kepala SPPG. Sesuai aturan bisnis, Kepala SPPG tidak dapat mengubah rolenya sendiri menjadi role lain.
                            <input type="hidden" name="role" value="{{ $employee->role->value }}" />
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <!-- Role: Pengawas Gizi -->
                            <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer smooth-transition hover:border-[#285F80] hover:bg-[#F8FAFC] has-checked:border-[#285F80] has-checked:bg-[#EBF3F8] has-checked:ring-2 has-checked:ring-[#285F80]">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold text-[#285F80]">Pengawas Gizi</span>
                                    <input type="radio" 
                                           name="role" 
                                           value="{{ \App\Enums\UserRole::PENGAWAS_GIZI->value }}" 
                                           {{ old('role', $employee->role?->value) === \App\Enums\UserRole::PENGAWAS_GIZI->value ? 'checked' : '' }}
                                           class="w-4 h-4 text-[#285F80] focus:ring-[#285F80] border-[#DCE5DD]" />
                                </div>
                                <p class="text-[11px] text-[#53645B] leading-relaxed">
                                    Review desk gizi, validasi standar gramasi menu, dan verifikasi mutu.
                                </p>
                            </label>

                            <!-- Role: Asisten Lapangan -->
                            <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer smooth-transition hover:border-[#885D1C] hover:bg-[#FFFDF7] has-checked:border-[#885D1C] has-checked:bg-[#FEF8ED] has-checked:ring-2 has-checked:ring-[#885D1C]">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold text-[#885D1C]">Asisten Lapangan</span>
                                    <input type="radio" 
                                           name="role" 
                                           value="{{ \App\Enums\UserRole::ASISTEN_LAPANGAN->value }}" 
                                           {{ old('role', $employee->role?->value) === \App\Enums\UserRole::ASISTEN_LAPANGAN->value ? 'checked' : '' }}
                                           class="w-4 h-4 text-[#885D1C] focus:ring-[#885D1C] border-[#DCE5DD]" />
                                </div>
                                <p class="text-[11px] text-[#53645B] leading-relaxed">
                                    Eksekusi runbook produksi harian, pencatatan batch masak, dan pengepakan.
                                </p>
                            </label>

                            <!-- Role: Kurir -->
                            <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer smooth-transition hover:border-[#55327A] hover:bg-[#FAF7FD] has-checked:border-[#55327A] has-checked:bg-[#F5F0FB] has-checked:ring-2 has-checked:ring-[#55327A]">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold text-[#55327A]">Kurir</span>
                                    <input type="radio" 
                                           name="role" 
                                           value="{{ \App\Enums\UserRole::KURIR->value }}" 
                                           {{ old('role', $employee->role?->value) === \App\Enums\UserRole::KURIR->value ? 'checked' : '' }}
                                           class="w-4 h-4 text-[#55327A] focus:ring-[#55327A] border-[#DCE5DD]" />
                                </div>
                                <p class="text-[11px] text-[#53645B] leading-relaxed">
                                    Distribusi makanan rute multi-stop dan dokumentasi serah terima sekolah.
                                </p>
                            </label>
                        </div>
                    @endif

                    @error('role')
                        <p class="mt-1 text-xs text-[#9B3232]">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Section: Status Keaktifan Akun -->
                <div class="space-y-3 pt-2">
                    <h2 class="text-sm font-bold text-[#172923] uppercase tracking-wider pb-2 border-b border-[#DCE5DD]">
                        Status Keaktifan Akun
                    </h2>

                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="is_active" value="0" />
                            <input type="checkbox" 
                                   name="is_active" 
                                   value="1" 
                                   id="is_active" 
                                   {{ old('is_active', $employee->is_active) ? 'checked' : '' }}
                                   class="sr-only peer" />
                            <div class="w-11 h-6 bg-[#DCE5DD] peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[#1C6654] rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-[#DCE5DD] after:border after:rounded-full after:h-5 after:width-5 after:transition-all peer-checked:bg-[#1C6654]"></div>
                        </label>
                        <div>
                            <span class="text-xs sm:text-sm font-semibold text-[#172923]">Akun Aktif (Dapat Masuk &amp; Bertugas)</span>
                            <p class="text-[11px] text-[#53645B]">
                                Bila dinonaktifkan, pegawai tidak dapat login ke sistem. Riwayat audit dan transaksi tetap tersimpan.
                            </p>
                        </div>
                    </div>
                    @error('is_active')
                        <p class="mt-1 text-xs text-[#9B3232]">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="pt-4 border-t border-[#DCE5DD] flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-3">
                    <a href="{{ route('head.employees.show', $employee) }}" 
                       class="inline-flex items-center justify-center px-4 py-2.5 text-xs sm:text-sm font-semibold text-[#53645B] hover:text-[#172923] bg-[#F5F7F3] hover:bg-[#EDF2ED] rounded-xl smooth-transition touch-target">
                        Batal
                    </a>
                    <button type="submit" 
                            class="inline-flex items-center justify-center px-5 py-2.5 text-xs sm:text-sm font-semibold text-white bg-[#1C6654] hover:bg-[#164D42] rounded-xl shadow-xs smooth-transition touch-target focus:outline-none focus:ring-2 focus:ring-[#1C6654] focus:ring-offset-2">
                        Simpan Perubahan
                    </button>
                </div>

            </form>
        </div>

        <!-- Separate Password Reset Card -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 sm:p-7 subtle-shadow space-y-4">
            <div class="pb-3 border-b border-[#DCE5DD]">
                <h2 class="text-sm font-bold text-[#172923] uppercase tracking-wider">
                    Reset Kata Sandi Pegawai
                </h2>
                <p class="text-xs text-[#53645B] mt-1 leading-relaxed">
                    Tetapkan kata sandi baru jika pegawai lupa kata sandi atau terjadi insiden keamanan kredensial.
                </p>
            </div>

            <form method="POST" action="{{ route('head.employees.reset-password', $employee) }}" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="reset_password" class="block text-xs sm:text-sm font-semibold text-[#172923] mb-1.5">
                            Kata Sandi Baru <span class="text-[#9B3232]">*</span>
                        </label>
                        <input type="password" 
                               id="reset_password" 
                               name="password" 
                               required 
                               placeholder="Minimal 8 karakter" 
                               class="w-full px-3.5 py-2.5 text-xs sm:text-sm text-[#172923] bg-[#F5F7F3] border @error('password') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] smooth-transition" />
                        @error('password')
                            <p class="mt-1 text-xs text-[#9B3232]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="reset_password_confirmation" class="block text-xs sm:text-sm font-semibold text-[#172923] mb-1.5">
                            Konfirmasi Kata Sandi Baru <span class="text-[#9B3232]">*</span>
                        </label>
                        <input type="password" 
                               id="reset_password_confirmation" 
                               name="password_confirmation" 
                               required 
                               placeholder="Ketik ulang kata sandi baru" 
                               class="w-full px-3.5 py-2.5 text-xs sm:text-sm text-[#172923] bg-[#F5F7F3] border border-[#DCE5DD] rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] smooth-transition" />
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" 
                            onclick="return confirm('Apakah Anda yakin ingin mereset kata sandi pegawai ini?')"
                            class="inline-flex items-center justify-center px-4 py-2.5 text-xs sm:text-sm font-semibold text-[#885D1C] bg-[#FEF8ED] hover:bg-[#FCEFD6] border border-[#F6E1B9] rounded-xl smooth-transition touch-target cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#885D1C]">
                        Perbarui Kata Sandi
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-shell>
