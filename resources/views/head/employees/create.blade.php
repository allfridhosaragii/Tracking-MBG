<x-app-shell title="Tambah Pegawai Internal : MBG-Track">
    <div class="max-w-3xl mx-auto space-y-6">

        <!-- Top Header & Breadcrumb -->
        <div>
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-[#53645B] mb-1.5">
                <a href="{{ route('head.dashboard') }}" class="hover:text-[#1C6654] smooth-transition">Beranda</a>
                <span aria-hidden="true">•</span>
                <a href="{{ route('head.employees.index') }}" class="hover:text-[#1C6654] smooth-transition">Manajemen Pegawai</a>
                <span aria-hidden="true">•</span>
                <span class="text-[#172923] font-medium" aria-current="page">Tambah Pegawai</span>
            </nav>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-[#172923]">
                Daftarkan Pegawai Internal SPPG
            </h1>
            <p class="text-xs sm:text-sm text-[#53645B] mt-1 leading-relaxed">
                Tetapkan identitas, kontak, role operasional, serta kredensial masuk bagi staf pelaksana SPPG.
            </p>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 sm:p-7 subtle-shadow">
            <form method="POST" action="{{ route('head.employees.store') }}" class="space-y-6">
                @csrf

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
                               value="{{ old('name') }}" 
                               required 
                               placeholder="Contoh: Rian Anggoro, S.Gz" 
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
                                   value="{{ old('email') }}" 
                                   required 
                                   placeholder="nama@sppg.id" 
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
                                   value="{{ old('phone') }}" 
                                   placeholder="081234567890" 
                                   class="w-full px-3.5 py-2.5 text-xs sm:text-sm text-[#172923] bg-[#F5F7F3] border @error('phone') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] smooth-transition" />
                            @error('phone')
                                <p class="mt-1 text-xs text-[#9B3232]">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Section: Pemilihan Role Operasional -->
                <div class="space-y-4 pt-2">
                    <h2 class="text-sm font-bold text-[#172923] uppercase tracking-wider pb-2 border-b border-[#DCE5DD]">
                        Role Operasional Internal <span class="text-[#9B3232]">*</span>
                    </h2>
                    <p class="text-xs text-[#53645B]">
                        Pilih satu tanggung jawab utama sesuai tugas fisik pegawai. Hak akses menu akan disesuaikan secara otomatis.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Role: Pengawas Gizi -->
                        <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer smooth-transition hover:border-[#285F80] hover:bg-[#F8FAFC] has-checked:border-[#285F80] has-checked:bg-[#EBF3F8] has-checked:ring-2 has-checked:ring-[#285F80]">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-[#285F80]">Pengawas Gizi</span>
                                <input type="radio" 
                                       name="role" 
                                       value="{{ \App\Enums\UserRole::PENGAWAS_GIZI->value }}" 
                                       {{ old('role') === \App\Enums\UserRole::PENGAWAS_GIZI->value ? 'checked' : '' }}
                                       class="w-4 h-4 text-[#285F80] focus:ring-[#285F80] border-[#DCE5DD]" />
                            </div>
                            <p class="text-[11px] text-[#53645B] leading-relaxed">
                                Review desk gizi, validasi standar gramasi menu, dan verifikasi sertifikasi uji mutu pangan.
                            </p>
                        </label>

                        <!-- Role: Asisten Lapangan -->
                        <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer smooth-transition hover:border-[#885D1C] hover:bg-[#FFFDF7] has-checked:border-[#885D1C] has-checked:bg-[#FEF8ED] has-checked:ring-2 has-checked:ring-[#885D1C]">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-[#885D1C]">Asisten Lapangan</span>
                                <input type="radio" 
                                       name="role" 
                                       value="{{ \App\Enums\UserRole::ASISTEN_LAPANGAN->value }}" 
                                       {{ old('role') === \App\Enums\UserRole::ASISTEN_LAPANGAN->value ? 'checked' : '' }}
                                       class="w-4 h-4 text-[#885D1C] focus:ring-[#885D1C] border-[#DCE5DD]" />
                            </div>
                            <p class="text-[11px] text-[#53645B] leading-relaxed">
                                Eksekusi runbook produksi harian, pencatatan batch masak, dan pengepakan porsi di dapur.
                            </p>
                        </label>

                        <!-- Role: Kurir -->
                        <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer smooth-transition hover:border-[#55327A] hover:bg-[#FAF7FD] has-checked:border-[#55327A] has-checked:bg-[#F5F0FB] has-checked:ring-2 has-checked:ring-[#55327A]">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-[#55327A]">Kurir</span>
                                <input type="radio" 
                                       name="role" 
                                       value="{{ \App\Enums\UserRole::KURIR->value }}" 
                                       {{ old('role') === \App\Enums\UserRole::KURIR->value ? 'checked' : '' }}
                                       class="w-4 h-4 text-[#55327A] focus:ring-[#55327A] border-[#DCE5DD]" />
                            </div>
                            <p class="text-[11px] text-[#53645B] leading-relaxed">
                                Distribusi makanan rute multi-stop, dokumentasi serah terima di sekolah, dan pengembalian tray.
                            </p>
                        </label>
                    </div>

                    @error('role')
                        <p class="mt-1 text-xs text-[#9B3232]">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Section: Kredensial Kata Sandi -->
                <div class="space-y-4 pt-2">
                    <h2 class="text-sm font-bold text-[#172923] uppercase tracking-wider pb-2 border-b border-[#DCE5DD]">
                        Kredensial Kata Sandi
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-xs sm:text-sm font-semibold text-[#172923] mb-1.5">
                                Kata Sandi Awal <span class="text-[#9B3232]">*</span>
                            </label>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   required 
                                   placeholder="Minimal 8 karakter" 
                                   class="w-full px-3.5 py-2.5 text-xs sm:text-sm text-[#172923] bg-[#F5F7F3] border @error('password') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] smooth-transition" />
                            @error('password')
                                <p class="mt-1 text-xs text-[#9B3232]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-xs sm:text-sm font-semibold text-[#172923] mb-1.5">
                                Ulangi Kata Sandi <span class="text-[#9B3232]">*</span>
                            </label>
                            <input type="password" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   required 
                                   placeholder="Ketik ulang kata sandi" 
                                   class="w-full px-3.5 py-2.5 text-xs sm:text-sm text-[#172923] bg-[#F5F7F3] border border-[#DCE5DD] rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] smooth-transition" />
                        </div>
                    </div>

                    <!-- Security Notice Box -->
                    <div class="p-3.5 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD] text-xs text-[#53645B] space-y-1">
                        <div class="font-semibold text-[#172923]">Pemberitahuan Keamanan:</div>
                        <ul class="list-disc pl-4 space-y-0.5">
                            <li>Kata sandi ini digunakan pegawai untuk login pertama kali.</li>
                            <li>Semua aktivitas penetapan izin dicatat pada jejak audit sistem.</li>
                        </ul>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="pt-4 border-t border-[#DCE5DD] flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-3">
                    <a href="{{ route('head.employees.index') }}" 
                       class="inline-flex items-center justify-center px-4 py-2.5 text-xs sm:text-sm font-semibold text-[#53645B] hover:text-[#172923] bg-[#F5F7F3] hover:bg-[#EDF2ED] rounded-xl smooth-transition touch-target">
                        Batal
                    </a>
                    <button type="submit" 
                            class="inline-flex items-center justify-center px-5 py-2.5 text-xs sm:text-sm font-semibold text-white bg-[#1C6654] hover:bg-[#164D42] rounded-xl shadow-xs smooth-transition touch-target focus:outline-none focus:ring-2 focus:ring-[#1C6654] focus:ring-offset-2">
                        Simpan &amp; Daftarkan Pegawai
                    </button>
                </div>

            </form>
        </div>

    </div>
</x-app-shell>
