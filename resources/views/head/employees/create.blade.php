<x-app-shell title="Tambah Pegawai Internal">
    <div class="max-w-3xl mx-auto space-y-5">
        <header class="pb-4 border-b border-[#DCE5DD]">
            <a href="{{ route('head.employees.index') }}" class="text-xs font-semibold text-[#1C6654] hover:underline inline-flex items-center gap-1 mb-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>Kembali ke Direktori Pegawai</span>
            </a>
            <h1 class="page-title">Daftarkan Pegawai Internal SPPG</h1>
            <p class="text-xs sm:text-sm text-[#53645B] mt-1">Tetapkan profil kontak, tanggung jawab operasional, dan kata sandi awal staf.</p>
        </header>

        <div class="luxury-surface p-5 sm:p-7">
            <form method="POST" action="{{ route('head.employees.store') }}" class="space-y-6">
                @csrf

                <div class="space-y-4">
                    <h2 class="section-title pb-2 border-b border-[#E7ECE8]">1. Identitas &amp; Kontak</h2>

                    <div>
                        <label for="name" class="block text-xs font-semibold text-[#172923] mb-1.5">
                            Nama Lengkap <span class="text-[#9B3232]">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               value="{{ old('name') }}"
                               required
                               placeholder="Contoh: Rian Anggoro, S.Gz"
                               class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm text-[#172923] bg-[#FAFBF9] focus:bg-white border @error('name') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl" />
                        @error('name')
                            <p class="mt-1 text-xs text-[#9B3232]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="block text-xs font-semibold text-[#172923] mb-1.5">
                                Alamat Email Masuk <span class="text-[#9B3232]">*</span>
                            </label>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   required
                                   placeholder="nama@sppg.id"
                                   class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm text-[#172923] bg-[#FAFBF9] focus:bg-white border @error('email') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl" />
                            @error('email')
                                <p class="mt-1 text-xs text-[#9B3232]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phone" class="block text-xs font-semibold text-[#172923] mb-1.5">
                                Nomor Telepon
                            </label>
                            <input type="tel"
                                   id="phone"
                                   name="phone"
                                   value="{{ old('phone') }}"
                                   placeholder="081234567890"
                                   class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm text-[#172923] bg-[#FAFBF9] focus:bg-white border @error('phone') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl" />
                            @error('phone')
                                <p class="mt-1 text-xs text-[#9B3232]">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <h2 class="section-title pb-2 border-b border-[#E7ECE8]">2. Peran Operasional <span class="text-[#9B3232]">*</span></h2>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="p-4 rounded-xl border border-[#DCE5DD] bg-[#FAFBF9] cursor-pointer smooth-transition hover:border-[#285F80] has-checked:border-[#285F80] has-checked:bg-[#F0F6FA]">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-bold text-[#285F80]">Pengawas Gizi</span>
                                <input type="radio"
                                       name="role"
                                       value="{{ \App\Enums\UserRole::PENGAWAS_GIZI->value }}"
                                       {{ old('role') === \App\Enums\UserRole::PENGAWAS_GIZI->value ? 'checked' : '' }}
                                       class="w-4 h-4 text-[#285F80] border-[#DCE5DD]" />
                            </div>
                            <p class="text-[11px] text-[#53645B] leading-relaxed">Formulasi menu terstandar dan inspeksi mutu QC batch.</p>
                        </label>

                        <label class="p-4 rounded-xl border border-[#DCE5DD] bg-[#FAFBF9] cursor-pointer smooth-transition hover:border-[#885D1C] has-checked:border-[#885D1C] has-checked:bg-[#FEF7EC]">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-bold text-[#885D1C]">Asisten Lapangan</span>
                                <input type="radio"
                                       name="role"
                                       value="{{ \App\Enums\UserRole::ASISTEN_LAPANGAN->value }}"
                                       {{ old('role') === \App\Enums\UserRole::ASISTEN_LAPANGAN->value ? 'checked' : '' }}
                                       class="w-4 h-4 text-[#885D1C] border-[#DCE5DD]" />
                            </div>
                            <p class="text-[11px] text-[#53645B] leading-relaxed">Eksekusi produksi batch dapur dan rekonsiliasi wadah ompreng.</p>
                        </label>

                        <label class="p-4 rounded-xl border border-[#DCE5DD] bg-[#FAFBF9] cursor-pointer smooth-transition hover:border-[#55327A] has-checked:border-[#55327A] has-checked:bg-[#F4F0F7]">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-bold text-[#55327A]">Kurir</span>
                                <input type="radio"
                                       name="role"
                                       value="{{ \App\Enums\UserRole::KURIR->value }}"
                                       {{ old('role') === \App\Enums\UserRole::KURIR->value ? 'checked' : '' }}
                                       class="w-4 h-4 text-[#55327A] border-[#DCE5DD]" />
                            </div>
                            <p class="text-[11px] text-[#53645B] leading-relaxed">Pengantaran rute multi-sekolah dan penjemputan ompreng.</p>
                        </label>
                    </div>
                    @error('role')
                        <p class="mt-1 text-xs text-[#9B3232]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-4">
                    <h2 class="section-title pb-2 border-b border-[#E7ECE8]">3. Kata Sandi Awal</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-xs font-semibold text-[#172923] mb-1.5">
                                Kata Sandi <span class="text-[#9B3232]">*</span>
                            </label>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   required
                                   placeholder="Minimal 8 karakter"
                                   class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm text-[#172923] bg-[#FAFBF9] focus:bg-white border @error('password') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl" />
                            @error('password')
                                <p class="mt-1 text-xs text-[#9B3232]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-xs font-semibold text-[#172923] mb-1.5">
                                Ulangi Kata Sandi <span class="text-[#9B3232]">*</span>
                            </label>
                            <input type="password"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   required
                                   placeholder="Ketik ulang kata sandi"
                                   class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm text-[#172923] bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl" />
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-[#E7ECE8] flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-3">
                    <a href="{{ route('head.employees.index') }}"
                       class="min-h-[44px] px-4 py-2.5 text-xs sm:text-sm font-semibold text-[#53645B] hover:text-[#172923] bg-[#F5F7F3] hover:bg-[#EDF2ED] rounded-xl inline-flex items-center justify-center smooth-transition">
                        Batal
                    </a>
                    <button type="submit"
                            class="min-h-[44px] px-6 py-2.5 text-xs sm:text-sm font-semibold text-white bg-[#1C6654] hover:bg-[#164D42] rounded-xl inline-flex items-center justify-center cursor-pointer smooth-transition">
                        Simpan &amp; Daftarkan Pegawai
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-shell>
