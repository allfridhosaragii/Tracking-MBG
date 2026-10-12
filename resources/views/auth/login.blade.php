<x-app-shell title="Masuk Portal Operasional">
    <div class="min-h-[calc(100vh-14rem)] flex flex-col justify-center items-center py-4 sm:py-8">
        <div class="w-full max-w-[420px] px-2 sm:px-0">

            <!-- Header Card -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-[#1C6654] text-white text-xl font-bold mb-3 shadow-sm">
                    M
                </div>
                <h1 class="text-2xl font-bold text-[#172923] tracking-tight">Portal MBG-Track</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-1">Koordinasi Operasional SPPG dan Sekolah Penerima</p>
            </div>

            <!-- Login Form Card -->
            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-6 sm:p-8 floating-shadow">
                <form method="POST" action="{{ route('login.post') }}" class="space-y-4.5" id="loginForm">
                    @csrf

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-[#172923] mb-1.5">
                            Alamat Email
                        </label>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               value="{{ old('email', ! ($isProduction ?? app()->isProduction()) ? 'kepala@mbgtrack.test' : '') }}" 
                               required 
                               autocomplete="email"
                               class="w-full h-11 px-3.5 rounded-xl border {{ $errors->has('email') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-white' }} text-[#172923] text-sm focus:outline-none focus:ring-2 focus:ring-[#1C6654] focus:border-transparent transition-all placeholder:text-[#53645B]/60"
                               placeholder="nama@mbgtrack.test">
                        @error('email')
                            <p class="mt-1.5 text-xs text-[#9B3232] font-medium flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>

                    <!-- Password Field -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-[#172923]">
                                Kata Sandi
                            </label>
                        </div>
                        <input type="password" 
                               name="password" 
                               id="password" 
                               value="{{ ! ($isProduction ?? app()->isProduction()) ? 'password' : '' }}"
                               required 
                               autocomplete="current-password"
                               class="w-full h-11 px-3.5 rounded-xl border {{ $errors->has('password') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-white' }} text-[#172923] text-sm focus:outline-none focus:ring-2 focus:ring-[#1C6654] focus:border-transparent transition-all placeholder:text-[#53645B]/60"
                               placeholder="••••••••">
                        @error('password')
                            <p class="mt-1.5 text-xs text-[#9B3232] font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember & Security note -->
                    <div class="flex items-center justify-between text-xs pt-1">
                        <label class="flex items-center gap-2 cursor-pointer text-[#53645B]">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded border-[#DCE5DD] text-[#1C6654] focus:ring-[#1C6654]">
                            <span>Ingat saya di perangkat ini</span>
                        </label>
                        <span class="text-[11px] text-[#53645B]">Sesi Terenkripsi</span>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit" 
                                class="w-full h-11 inline-flex items-center justify-center rounded-xl bg-[#1C6654] hover:bg-[#164D42] active:bg-[#123E35] text-white text-sm font-semibold shadow-xs transition-colors cursor-pointer">
                            <span>Masuk ke Dashboard</span>
                        </button>
                    </div>
                </form>

                @if (! ($isProduction ?? app()->isProduction()))
                    <!-- Demo Simulation Quick Preset Switcher (Disabled in Production) -->
                    <div class="mt-6 pt-5 border-t border-[#EDF2ED]">
                        <div class="flex items-center justify-between mb-2.5">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-[#53645B]">Preset Akun Simulasi (Dev Only)</span>
                            <span class="text-[10px] bg-[#FAF4EB] text-[#885D1C] px-2 py-0.5 rounded font-medium border border-[#E8D7BE]">Demo Mode</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <button type="button" 
                                    onclick="fillPreset('kepala@mbgtrack.test')" 
                                    class="p-2 rounded-lg text-left border border-[#DCE5DD] hover:border-[#1C6654] hover:bg-[#EDF5F2] transition-colors cursor-pointer">
                                <span class="font-semibold block text-[#172923] text-[11px]">Kepala SPPG</span>
                                <span class="text-[10px] text-[#53645B] block truncate">kepala@mbgtrack.test</span>
                            </button>
                            <button type="button" 
                                    onclick="fillPreset('gizi@mbgtrack.test')" 
                                    class="p-2 rounded-lg text-left border border-[#DCE5DD] hover:border-[#1C6654] hover:bg-[#EDF5F2] transition-colors cursor-pointer">
                                <span class="font-semibold block text-[#172923] text-[11px]">Pengawas Gizi</span>
                                <span class="text-[10px] text-[#53645B] block truncate">gizi@mbgtrack.test</span>
                            </button>
                            <button type="button" 
                                    onclick="fillPreset('asisten@mbgtrack.test')" 
                                    class="p-2 rounded-lg text-left border border-[#DCE5DD] hover:border-[#1C6654] hover:bg-[#EDF5F2] transition-colors cursor-pointer">
                                <span class="font-semibold block text-[#172923] text-[11px]">Asisten Lapangan</span>
                                <span class="text-[10px] text-[#53645B] block truncate">asisten@mbgtrack.test</span>
                            </button>
                            <button type="button" 
                                    onclick="fillPreset('kurir@mbgtrack.test')" 
                                    class="p-2 rounded-lg text-left border border-[#DCE5DD] hover:border-[#1C6654] hover:bg-[#EDF5F2] transition-colors cursor-pointer">
                                <span class="font-semibold block text-[#172923] text-[11px]">Kurir Pengantaran</span>
                                <span class="text-[10px] text-[#53645B] block truncate">kurir@mbgtrack.test</span>
                            </button>
                            <button type="button" 
                                    onclick="fillPreset('pic.sdn01@mbgtrack.test')" 
                                    class="p-2 rounded-lg text-left border border-[#DCE5DD] hover:border-[#1C6654] hover:bg-[#EDF5F2] transition-colors cursor-pointer">
                                <span class="font-semibold block text-[#172923] text-[11px]">PIC SDN Percobaan</span>
                                <span class="text-[10px] text-[#53645B] block truncate">pic.sdn01@mbgtrack.test</span>
                            </button>
                            <button type="button" 
                                    onclick="fillPreset('inactive@mbgtrack.test')" 
                                    class="p-2 rounded-lg text-left border border-[#F5C2C2] hover:bg-[#FDF2F2] transition-colors cursor-pointer">
                                <span class="font-semibold block text-[#9B3232] text-[11px]">Uji Akun Nonaktif</span>
                                <span class="text-[10px] text-[#9B3232]/80 block truncate">inactive@mbgtrack.test</span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Public Portal Link -->
            <div class="text-center mt-6">
                <a href="{{ route('public.schools.show', 'sdn-percobaan-01-surabaya') }}" 
                   class="text-xs text-[#53645B] hover:text-[#1C6654] transition-colors inline-flex items-center gap-1.5 underline decoration-[#DCE5DD] underline-offset-4">
                    <span>Lihat Halaman Transparansi Publik Sekolah (Tanpa Login)</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            </div>

        </div>
    </div>

    @if (! ($isProduction ?? app()->isProduction()))
        <script>
            function fillPreset(email) {
                const emailInput = document.getElementById('email');
                const passInput = document.getElementById('password');
                emailInput.value = email;
                passInput.value = 'password';
                emailInput.focus();
            }
        </script>
    @endif
</x-app-shell>

