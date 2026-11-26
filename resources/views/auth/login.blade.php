<x-app-shell title="Masuk Portal Operasional">
    <div class="py-2 sm:py-6 lg:py-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-10 items-start max-w-5xl mx-auto">

            <!-- Left Editorial Context Column (Clean Luxury Desktop, Compact Header on Mobile) -->
            <div class="lg:col-span-6 lg:pr-4 space-y-5">
                <div>
                    <span class="eyebrow-label text-[#1C6654] block mb-2">Sistem Operasional Terpadu</span>
                    <h1 class="text-2xl sm:text-3xl lg:text-[2rem] font-bold text-[#172923] tracking-tight leading-tight">
                        Portal MBG-Track
                    </h1>
                    <p class="text-xs sm:text-sm text-[#53645B] mt-2 leading-relaxed">
                        Koordinasi satu dapur SPPG bersama seluruh sekolah binaan, mulai dari perencanaan porsi harian, verifikasi mutu gizi, pengantaran kurir, hingga pengembalian wadah ompreng.
                    </p>
                </div>

                <!-- Operational Chain Overview (Visible on Desktop to create Editorial Luxury Balance) -->
                <div class="hidden lg:block luxury-surface p-5 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-[#E7ECE8]">
                        <span class="text-xs font-bold text-[#172923]">Rantai Akuntabilitas Harian</span>
                        <span class="text-[11px] font-medium text-[#1C6654]">5 Peran Terintegrasi</span>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="flex items-start gap-3">
                            <span class="w-6 h-6 rounded-lg bg-[#EDF7F2] text-[#1C6654] font-bold flex items-center justify-center shrink-0 tabular-nums">1</span>
                            <div>
                                <span class="font-semibold text-[#172923] block">Kepala SPPG &amp; Pengawas Gizi</span>
                                <span class="text-[#53645B]">Menetapkan menu terstandar AKG, target porsi sah, dan kelayakan QC batch.</span>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="w-6 h-6 rounded-lg bg-[#FEF7EC] text-[#885D1C] font-bold flex items-center justify-center shrink-0 tabular-nums">2</span>
                            <div>
                                <span class="font-semibold text-[#172923] block">Asisten Lapangan &amp; Kurir</span>
                                <span class="text-[#53645B]">Menjalankan pemorsian dapur, pengantaran bertelemetri berizin, dan jemput wadah.</span>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="w-6 h-6 rounded-lg bg-[#F0F6FA] text-[#285F80] font-bold flex items-center justify-center shrink-0 tabular-nums">3</span>
                            <div>
                                <span class="font-semibold text-[#172923] block">PIC per Sekolah Binaan</span>
                                <span class="text-[#53645B]">Setiap sekolah memiliki akun terisolasi untuk konfirmasi porsi dan berita acara terima.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Public Transparency Links -->
                @if (isset($publicSchools) && $publicSchools->isNotEmpty())
                    <div class="luxury-surface p-4 sm:p-5">
                        <div class="flex items-center justify-between gap-2 mb-2.5">
                            <span class="text-xs font-bold text-[#172923]">Portal Transparansi Publik (Tanpa Login)</span>
                            <span class="text-[10px] font-semibold text-[#21684B] bg-[#EDF7F2] px-2 py-0.5 rounded-md border border-[#BCE2D0]">Wali Murid</span>
                        </div>
                        <p class="text-xs text-[#53645B] mb-3 leading-relaxed">
                            Masyarakat dapat memantau menu dan tahapan pengantaran sekolah secara terbuka tanpa kredensial internal:
                        </p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($publicSchools as $pubSchool)
                                <a href="{{ route('public.schools.show', $pubSchool->public_slug) }}"
                                   class="min-h-[44px] px-3 py-2 rounded-xl bg-[#FAFBF9] hover:bg-[#EDF7F2] border border-[#DCE5DD] hover:border-[#BCE2D0] text-xs font-semibold text-[#1C6654] inline-flex items-center gap-1.5 smooth-transition">
                                    <span class="truncate max-w-[200px]">{{ $pubSchool->name }}</span>
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @elseif (! empty($sampleSchoolSlug))
                    <div class="text-xs text-[#53645B]">
                        Pantau layanan sekolah tanpa login melalui
                        <a href="{{ route('public.schools.show', $sampleSchoolSlug) }}" class="font-semibold text-[#1C6654] hover:underline ml-1">
                            Portal Transparansi Publik
                        </a>
                    </div>
                @endif
            </div>

            <!-- Right Sign-In & Multi-Role / Multi-School Switcher Card -->
            <div class="lg:col-span-6">
                <div class="luxury-surface p-5 sm:p-7">
                    <div class="mb-5 pb-4 border-b border-[#E7ECE8]">
                        <h2 class="text-base sm:text-lg font-bold text-[#172923]">Masuk ke Ruang Kerja</h2>
                        <p class="text-xs text-[#53645B] mt-0.5">Gunakan email terdaftar sesuai peran SPPG atau unit sekolah Anda.</p>
                    </div>

                    <form method="POST" action="{{ route('login.post') }}" class="space-y-4" id="loginForm">
                        @csrf

                        <div>
                            <label for="email" class="block text-xs font-semibold text-[#172923] mb-1.5">
                                Alamat Email
                            </label>
                            <input type="email"
                                   name="email"
                                   id="email"
                                   value="{{ old('email', ! ($isProduction ?? app()->isProduction()) ? 'kepala@mbgtrack.test' : '') }}"
                                   required
                                   autocomplete="email"
                                   class="w-full min-h-[44px] px-3.5 rounded-xl border {{ $errors->has('email') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} text-[#172923] text-sm focus:bg-white smooth-transition placeholder:text-[#53645B]/60"
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

                        <div>
                            <label for="password" class="block text-xs font-semibold text-[#172923] mb-1.5">
                                Kata Sandi
                            </label>
                            <div class="relative">
                                <input type="password"
                                       name="password"
                                       id="password"
                                       value="{{ ! ($isProduction ?? app()->isProduction()) ? 'password' : '' }}"
                                       required
                                       autocomplete="current-password"
                                       class="w-full min-h-[44px] pl-3.5 pr-12 rounded-xl border {{ $errors->has('password') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} text-[#172923] text-sm focus:bg-white smooth-transition placeholder:text-[#53645B]/60"
                                       placeholder="Masukkan kata sandi">
                                <button type="button"
                                        id="togglePasswordBtn"
                                        onclick="togglePasswordVisibility()"
                                        class="absolute inset-y-0 right-0 w-11 flex items-center justify-center text-[#53645B] hover:text-[#172923] rounded-r-xl cursor-pointer"
                                        aria-label="Tampilkan atau sembunyikan kata sandi">
                                    <svg id="eyeIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1.5 text-xs text-[#9B3232] font-medium" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between text-xs pt-0.5">
                            <label class="inline-flex items-center gap-2 cursor-pointer text-[#53645B] hover:text-[#172923] min-h-[36px]">
                                <input type="checkbox" name="remember" class="w-4 h-4 rounded border-[#DCE5DD] text-[#1C6654] cursor-pointer">
                                <span>Ingat sesi perangkat ini</span>
                            </label>
                            <span class="text-[11px] text-[#53645B]">Otorisasi Berbasis Peran</span>
                        </div>

                        <div class="pt-1">
                            <button type="submit"
                                    class="w-full min-h-[46px] inline-flex items-center justify-center rounded-xl bg-[#1C6654] hover:bg-[#164D42] active:bg-[#123B33] text-white text-sm font-semibold smooth-transition cursor-pointer">
                                Masuk ke MBG-Track
                            </button>
                        </div>
                    </form>

                    @if (! $isProduction)
                        <!-- Dynamic Multi-Role & Multi-School PIC Simulation Switcher (Hidden in Production) -->
                        <div class="mt-6 pt-5 border-t border-[#E7ECE8] space-y-4">
                            <div class="flex items-center justify-between gap-2">
                                <span class="eyebrow-label">Akses Cepat Pengujian (Non-Produksi)</span>
                                <span class="text-[10px] bg-[#FEF7EC] text-[#885D1C] px-2 py-0.5 rounded-md font-bold border border-[#F7D7A3]">1-Klik Masuk</span>
                            </div>

                            <!-- 4 Internal SPPG Roles -->
                            <div>
                                <span class="text-[11px] font-semibold text-[#53645B] block mb-2">Staf Inti Dapur &amp; Distribusi SPPG</span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    @forelse ($staffPresets ?? [] as $staff)
                                        <button type="button"
                                                onclick="fillPreset('{{ $staff->email }}')"
                                                class="min-h-[48px] px-3 py-2 rounded-xl text-left bg-[#FAFBF9] hover:bg-[#EDF7F2] border border-[#DCE5DD] hover:border-[#1C6654] smooth-transition cursor-pointer flex flex-col justify-center">
                                            <span class="font-semibold text-xs text-[#172923] block leading-tight">{{ $staff->role?->label() ?? $staff->name }}</span>
                                            <span class="text-[11px] text-[#53645B] font-mono block truncate mt-0.5">{{ $staff->email }}</span>
                                        </button>
                                    @empty
                                        <button type="button" onclick="fillPreset('kepala@mbgtrack.test')" class="min-h-[48px] px-3 py-2 rounded-xl text-left bg-[#FAFBF9] hover:bg-[#EDF7F2] border border-[#DCE5DD] hover:border-[#1C6654] smooth-transition cursor-pointer">
                                            <span class="font-semibold text-xs text-[#172923] block">Kepala SPPG</span>
                                            <span class="text-[11px] text-[#53645B] font-mono block truncate">kepala@mbgtrack.test</span>
                                        </button>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Multi-School PIC Accounts -->
                            @if (isset($picPresets) && $picPresets->isNotEmpty())
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-[11px] font-semibold text-[#53645B]">PIC Sekolah (Berdasarkan Cakupan Sekolah)</span>
                                        <span class="text-[11px] font-mono text-[#1C6654] font-semibold">{{ $picPresets->count() }} akun</span>
                                    </div>
                                    <div class="space-y-2">
                                        @foreach ($picPresets as $pic)
                                            <button type="button"
                                                    onclick="fillPreset('{{ $pic->email }}')"
                                                    class="w-full min-h-[50px] px-3.5 py-2.5 rounded-xl text-left bg-[#FAFBF9] hover:bg-[#EDF7F2] border border-[#DCE5DD] hover:border-[#1C6654] smooth-transition cursor-pointer flex items-center justify-between gap-3">
                                                <div class="min-w-0">
                                                    <span class="font-semibold text-xs text-[#172923] block truncate">
                                                        {{ $pic->school?->name ?? $pic->name }}
                                                    </span>
                                                    <span class="text-[11px] text-[#53645B] font-mono block truncate mt-0.5">
                                                        {{ $pic->email }}
                                                    </span>
                                                </div>
                                                @if ($pic->school)
                                                    <span class="shrink-0 text-[10px] font-bold text-[#21684B] bg-[#EDF7F2] px-2 py-1 rounded-md border border-[#BCE2D0]">
                                                        {{ $pic->school->education_level }}
                                                    </span>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

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
            if (!passInput || !eyeIcon) return;
            if (passInput.type === 'password') {
                passInput.type = 'text';
                eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />';
            } else {
                passInput.type = 'password';
                eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
            }
        }
    </script>
</x-app-shell>
