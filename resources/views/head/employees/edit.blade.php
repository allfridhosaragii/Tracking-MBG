<x-app-shell title="Ubah Pegawai : {{ $employee->name }}">
    <div class="max-w-3xl mx-auto space-y-5">
        <header class="pb-4 border-b border-[#DCE5DD]">
            <a href="{{ route('head.employees.show', $employee) }}" class="text-xs font-semibold text-[#1C6654] hover:underline inline-flex items-center gap-1 mb-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>Kembali ke Profil Pegawai</span>
            </a>
            <h1 class="page-title">Ubah Data &amp; Hak Akses Pegawai</h1>
            <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">{{ $employee->name }} • <span class="font-mono">{{ $employee->email }}</span></p>
        </header>

        <div class="luxury-surface p-5 sm:p-7">
            <form method="POST" action="{{ route('head.employees.update', $employee) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <h2 class="section-title pb-2 border-b border-[#E7ECE8]">1. Identitas &amp; Kontak</h2>

                    <div>
                        <label for="name" class="block text-xs font-semibold text-[#172923] mb-1.5">
                            Nama Lengkap <span class="text-[#9B3232]">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               value="{{ old('name', $employee->name) }}"
                               required
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
                                   value="{{ old('email', $employee->email) }}"
                                   required
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
                                   value="{{ old('phone', $employee->phone) }}"
                                   class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm text-[#172923] bg-[#FAFBF9] focus:bg-white border @error('phone') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl" />
                            @error('phone')
                                <p class="mt-1 text-xs text-[#9B3232]">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-[#E7ECE8]">
                        <h2 class="section-title">2. Peran Operasional <span class="text-[#9B3232]">*</span></h2>
                        @if ($employee->id === auth()->id())
                            <span class="text-[11px] font-semibold text-[#885D1C] bg-[#FEF7EC] px-2 py-0.5 rounded-md border border-[#F7D7A3]">Akun Anda</span>
                        @endif
                    </div>

                    @if ($employee->id === auth()->id())
                        <div class="p-3.5 rounded-xl bg-[#FEF7EC] border border-[#F7D7A3] text-xs text-[#885D1C]">
                            Kepala SPPG tidak dapat mengubah peran akunnya sendiri.
                            <input type="hidden" name="role" value="{{ $employee->role->value }}" />
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <label class="p-4 rounded-xl border border-[#DCE5DD] bg-[#FAFBF9] cursor-pointer smooth-transition hover:border-[#285F80] has-checked:border-[#285F80] has-checked:bg-[#F0F6FA]">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-xs font-bold text-[#285F80]">Pengawas Gizi</span>
                                    <input type="radio"
                                           name="role"
                                           value="{{ \App\Enums\UserRole::PENGAWAS_GIZI->value }}"
                                           {{ old('role', $employee->role?->value) === \App\Enums\UserRole::PENGAWAS_GIZI->value ? 'checked' : '' }}
                                           class="w-4 h-4 text-[#285F80] border-[#DCE5DD]" />
                                </div>
                                <p class="text-[11px] text-[#53645B] leading-relaxed">Formulasi menu dan inspeksi mutu pangan.</p>
                            </label>

                            <label class="p-4 rounded-xl border border-[#DCE5DD] bg-[#FAFBF9] cursor-pointer smooth-transition hover:border-[#885D1C] has-checked:border-[#885D1C] has-checked:bg-[#FEF7EC]">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-xs font-bold text-[#885D1C]">Asisten Lapangan</span>
                                    <input type="radio"
                                           name="role"
                                           value="{{ \App\Enums\UserRole::ASISTEN_LAPANGAN->value }}"
                                           {{ old('role', $employee->role?->value) === \App\Enums\UserRole::ASISTEN_LAPANGAN->value ? 'checked' : '' }}
                                           class="w-4 h-4 text-[#885D1C] border-[#DCE5DD]" />
                                </div>
                                <p class="text-[11px] text-[#53645B] leading-relaxed">Eksekusi produksi dapur dan rekonsiliasi wadah.</p>
                            </label>

                            <label class="p-4 rounded-xl border border-[#DCE5DD] bg-[#FAFBF9] cursor-pointer smooth-transition hover:border-[#55327A] has-checked:border-[#55327A] has-checked:bg-[#F4F0F7]">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-xs font-bold text-[#55327A]">Kurir</span>
                                    <input type="radio"
                                           name="role"
                                           value="{{ \App\Enums\UserRole::KURIR->value }}"
                                           {{ old('role', $employee->role?->value) === \App\Enums\UserRole::KURIR->value ? 'checked' : '' }}
                                           class="w-4 h-4 text-[#55327A] border-[#DCE5DD]" />
                                </div>
                                <p class="text-[11px] text-[#53645B] leading-relaxed">Pengantaran rute multi-stop dan pickup wadah.</p>
                            </label>
                        </div>
                    @endif

                    @error('role')
                        <p class="mt-1 text-xs text-[#9B3232]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <input type="hidden" name="is_active" value="0" />
                    <label class="inset-surface p-3.5 flex items-start gap-3 cursor-pointer">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               id="is_active"
                               {{ old('is_active', $employee->is_active) ? 'checked' : '' }}
                               class="w-4 h-4 mt-0.5 text-[#1C6654] border-[#DCE5DD] rounded cursor-pointer" />
                        <span class="text-xs leading-relaxed">
                            <strong class="text-[#172923] block">Akun Aktif</strong>
                            <span class="text-[#53645B]">Bila dinonaktifkan, staf tidak dapat masuk ke sistem namun jejak audit tetap utuh.</span>
                        </span>
                    </label>
                    @error('is_active')
                        <p class="mt-1 text-xs text-[#9B3232]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-4 border-t border-[#E7ECE8] flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-3">
                    <a href="{{ route('head.employees.show', $employee) }}"
                       class="min-h-[44px] px-4 py-2.5 text-xs sm:text-sm font-semibold text-[#53645B] hover:text-[#172923] bg-[#F5F7F3] hover:bg-[#EDF2ED] rounded-xl inline-flex items-center justify-center smooth-transition">
                        Batal
                    </a>
                    <button type="submit"
                            class="min-h-[44px] px-6 py-2.5 text-xs sm:text-sm font-semibold text-white bg-[#1C6654] hover:bg-[#164D42] rounded-xl inline-flex items-center justify-center cursor-pointer smooth-transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

        <!-- Password Reset -->
        <x-card title="Reset Kata Sandi Pegawai" subtitle="Tetapkan kredensial baru jika pegawai kehilangan akses.">
            <form method="POST" action="{{ route('head.employees.reset-password', $employee) }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="reset_password" class="block text-xs font-semibold text-[#172923] mb-1.5">
                            Kata Sandi Baru <span class="text-[#9B3232]">*</span>
                        </label>
                        <input type="password"
                               id="reset_password"
                               name="password"
                               required
                               placeholder="Minimal 8 karakter"
                               class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm text-[#172923] bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl" />
                    </div>

                    <div>
                        <label for="reset_password_confirmation" class="block text-xs font-semibold text-[#172923] mb-1.5">
                            Konfirmasi Kata Sandi Baru <span class="text-[#9B3232]">*</span>
                        </label>
                        <input type="password"
                               id="reset_password_confirmation"
                               name="password_confirmation"
                               required
                               placeholder="Ketik ulang kata sandi baru"
                               class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm text-[#172923] bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl" />
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            onclick="return confirm('Reset kata sandi pegawai ini?')"
                            class="min-h-[44px] px-4 py-2 text-xs font-semibold text-[#885D1C] bg-[#FEF7EC] hover:bg-[#FCEFD6] border border-[#F7D7A3] rounded-xl cursor-pointer smooth-transition">
                        Perbarui Kata Sandi
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-shell>
