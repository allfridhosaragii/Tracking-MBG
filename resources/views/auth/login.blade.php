<x-app-shell title="Masuk Portal Operasional">
    <div class="min-h-[calc(100vh-14rem)] flex flex-col justify-center items-center py-6 sm:py-10">
        <div class="w-full max-w-[420px] px-3 sm:px-0">

            <!-- Workspace Header: Clean Luxury Editorial -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-[#1C6654] text-white text-xl font-bold mb-3.5 shadow-xs">
                    M
                </div>
                <h1 class="text-2xl font-bold text-[#172923] tracking-tight">Portal MBG-Track</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-1">Koordinasi Operasional SPPG &amp; Sekolah Binaan</p>
            </div>

            <!-- Login Form Card -->
            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-6 sm:p-8 subtle-shadow">
                <form method="POST" action="{{ route('login.post') }}" class="space-y-4" id="loginForm">
                    @csrf

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-[#172923] mb-1.5">
                            Alamat Email Pengguna
                        </label>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               value="{{ old('email', ! ($isProduction ?? app()->isProduction()) ? 'kepala@mbgtrack.test' : '') }}" 
                               required 
                               autocomplete="email"
                               class="w-full h-11 px-3.5 rounded-xl border {{ $errors->has('email') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} text-[#172923] text-sm focus:outline-none focus:ring-2 focus:ring-[#1C6654] focus:bg-white transition-all placeholder:text-[#53645B]/60"
                               placeholder="nama@mbgtrack.test">
                        @error('email')
                            <p class="mt-1.5 text-xs text-[#9B3232] font-medium flex items-center gap-1.5" role="alert">
                                <svg class="w-4 h-4 shrink-0 text-[#9B3232]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>

                    <!-- Password Field with Accessible Visibility Toggle -->
                    <div>
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-[#172923] mb-1.5">
                            Kata Sandi
                        </label>
                        <div class="relative">
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   value="{{ ! ($isProduction ?? app()->isProduction()) ? 'password' : '' }}"
                                   required 
                                   autocomplete="current-password"
                                   class="w-full h-11 pl-3.5 pr-11 rounded-xl border {{ $errors->has('password') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} text-[#172923] text-sm focus:outline-none focus:ring-2 focus:ring-[#1C6654] focus:bg-white transition-all placeholder:text-[#53645B]/60"
                                   placeholder="••••••••">
                            <button type="button" 
                                    id="togglePasswordBtn"
                                    onclick="togglePasswordVisibility()" 
                                    class="absolute inset-y-0 right-0 px-3 flex items-center text-[#53645B] hover:text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654] rounded-r-xl"
                                    aria-label="Tampilkan atau sembunyikan kata sandi">
                                <svg id="eyeIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-xs text-[#9B3232] font-medium" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember & Security Note -->
                    <div class="flex items-center justify-between text-xs pt-1">
                        <label class="flex items-center gap-2 cursor-pointer text-[#53645B] hover:text-[#172923]">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded border-[#DCE5DD] text-[#1C6654] focus:ring-[#1C6654] cursor-pointer">
                            <span>Ingat sesi di perangkat ini</span>
                        </label>
                        <span class="text-[11px] text-[#53645B]">Sesi Terproteksi</span>
                    </div>

                    <!-- Primary Action Button (Target >= 44px) -->
                    <div class="pt-2">
                        <button type="submit" 
                                class="w-full h-11 inline-flex items-center justify-center rounded-xl bg-[#1C6654] hover:bg-[#164D42] active:bg-[#123E35] text-white text-sm font-semibold shadow-xs smooth-transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#B78C56]">
                            <span>Masuk ke MBG-Track</span>
                        </button>
                    </div>
                </form>

                @if (! $isProduction)
                    <!-- Demo Simulation Quick Preset Switcher (Disabled in Production) -->
                    <div class="mt-6 pt-5 border-t border-[#EDF2ED]">
                        <div class="flex items-center justify-between mb-2.5">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-[#53645B]">Preset Akun Pengujian (Dev Only)</span>
                            <span class="text-[10px] bg-[#FAF4EB] text-[#885D1C] px-2 py-0.5 rounded font-bold border border-[#E8D7BE]">SIMULASI</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <button type="button" 
                                    onclick="fillPreset('kepala@mbgtrack.test')" 
                                    class="p-2.5 rounded-xl text-left border border-[#DCE5DD] hover:border-[#1C6654] hover:bg-[#EDF5F2] smooth-transition cursor-pointer touch-target">
                                <span class="font-semibold block text-[#172923] text-xs">Kepala SPPG</span>
                                <span class="text-[10px] text-[#53645B] block truncate">kepala@mbgtrack.test</span>
                            </button>
                            <button type="button" 
                                    onclick="fillPreset('gizi@mbgtrack.test')" 
                                    class="p-2.5 rounded-xl text-left border border-[#DCE5DD] hover:border-[#1C6654] hover:bg-[#EDF5F2] smooth-transition cursor-pointer touch-target">
                                <span class="font-semibold block text-[#172923] text-xs">Pengawas Gizi</span>
                                <span class="text-[10px] text-[#53645B] block truncate">gizi@mbgtrack.test</span>
                            </button>
                            <button type="button" 
                                    onclick="fillPreset('asisten@mbgtrack.test')" 
                                    class="p-2.5 rounded-xl text-left border border-[#DCE5DD] hover:border-[#1C6654] hover:bg-[#EDF5F2] smooth-transition cursor-pointer touch-target">
                                <span class="font-semibold block text-[#172923] text-xs">Asisten Lapangan</span>
                                <span class="text-[10px] text-[#53645B] block truncate">asisten@mbgtrack.test</span>
                            </button>
                            <button type="button" 
                                    onclick="fillPreset('kurir@mbgtrack.test')" 
                                    class="p-2.5 rounded-xl text-left border border-[#DCE5DD] hover:border-[#1C6654] hover:bg-[#EDF5F2] smooth-transition cursor-pointer touch-target">
                                <span class="font-semibold block text-[#172923] text-xs">Kurir Pengantaran</span>
                                <span class="text-[10px] text-[#53645B] block truncate">kurir@mbgtrack.test</span>
                            </button>
                            <button type="button" 
                                    onclick="fillPreset('pic.sdn01@mbgtrack.test')" 
                                    class="col-span-2 p-2.5 rounded-xl text-left border border-[#DCE5DD] hover:border-[#1C6654] hover:bg-[#EDF5F2] smooth-transition cursor-pointer touch-target">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold block text-[#172923] text-xs">PIC SDN Percobaan 01</span>
                                    <span class="text-[10px] text-[#21684B] font-medium bg-[#EDF7F2] px-1.5 py-0.5 rounded border border-[#BCE2D0]">Sekolah A</span>
                                </div>
                                <span class="text-[10px] text-[#53645B] block truncate">pic.sdn01@mbgtrack.test</span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Transparency Link: Clean Luxury Context -->
            @if (! empty($sampleSchoolSlug))
                <div class="mt-6 text-center text-xs text-[#53645B]">
                    Masyarakat dan wali murid dapat memantau menu tanpa login via
                    <a href="{{ route('public.schools.show', $sampleSchoolSlug) }}" class="font-semibold text-[#1C6654] hover:underline ml-1">
                        Portal Transparansi Publik &rarr;
                    </a>
                </div>
            @endif

        </div>
    </div>

    @if (! $isProduction)
        <script>
            function fillPreset(email) {
                const emailInput = document.getElementById('email');
                const passInput = document.getElementById('password');
                if (emailInput && passInput) {
                    emailInput.value = email;
                    passInput.value = 'password';
                    emailInput.closest('form').submit();
                }
            }
        </script>
    @endif

    <script>
        function togglePasswordVisibility() {
            const passInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            if (passInput) {
                if (passInput.type === 'password') {
                    passInput.type = 'text';
                    eyeIcon.innerHTML = `
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                    `;
                } else {
                    passInput.type = 'password';
                    eyeIcon.innerHTML = `
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    `;
                }
            }
        }
    </script>
</x-app-shell>
