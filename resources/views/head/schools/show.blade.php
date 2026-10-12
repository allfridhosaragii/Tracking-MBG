<x-app-shell title="Detail Sekolah : {{ $school->name }}">
    <!-- Breadcrumb & Top Bar -->
    <div class="mb-6 pb-4 border-b border-[#DCE5DD]">
        <div class="flex items-center gap-2 mb-2">
            <a href="{{ route('head.schools.index') }}" class="text-xs font-semibold text-[#1C6654] hover:underline inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                <span>Daftar Sekolah Binaan</span>
            </a>
            <span class="text-xs text-[#53645B]">•</span>
            <span class="text-xs text-[#53645B] font-mono">{{ $school->school_code }}</span>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-[#EDF2ED] text-[#172923] border border-[#DCE5DD]">
                        {{ $school->education_level }}
                    </span>
                    @if ($school->is_active)
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#EDF7F2] text-[#21684B] border border-[#BCE2D0] flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-[#21684B]"></span>
                            <span>AKTIF BEROPERASI</span>
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#FDF2F2] text-[#9B3232] border border-[#F5C2C2] flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-[#9B3232]"></span>
                            <span>NONAKTIF</span>
                        </span>
                    @endif
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">{{ $school->name }}</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-0.5 font-mono">Kode NPSN: {{ $school->school_code }}</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('head.schools.edit', $school) }}" 
                   class="px-4 py-2 text-xs sm:text-sm font-semibold text-[#1C6654] bg-[#EDF2ED] border border-[#DCE5DD] hover:bg-[#E2EBE2] rounded-xl transition-colors min-h-[44px] flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Edit Sekolah</span>
                </a>

                <form method="POST" action="{{ route('head.schools.toggle-active', $school) }}" class="inline" onsubmit="return confirm('Ubah status aktif sekolah ini?')">
                    @csrf
                    @method('PATCH')
                    <button type="submit" 
                            class="px-4 py-2 text-xs sm:text-sm font-medium {{ $school->is_active ? 'text-[#9B3232] bg-[#FDF2F2] hover:bg-[#FBE8E8] border border-[#F5C2C2]' : 'text-[#21684B] bg-[#EDF7F2] hover:bg-[#DCF0E6] border border-[#BCE2D0]' }} rounded-xl transition-colors min-h-[44px] cursor-pointer">
                        {{ $school->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Card 1: Identitas & Lokasi -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 sm:p-6 subtle-shadow">
            <h2 class="text-xs font-bold text-[#53645B] uppercase tracking-wider mb-4 pb-2 border-b border-[#EDF2ED] flex items-center gap-2">
                <svg class="w-4 h-4 text-[#1C6654]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Lokasi & Akses Logistik</span>
            </h2>

            <dl class="space-y-3.5 text-xs sm:text-sm">
                <div>
                    <dt class="text-[11px] font-semibold text-[#53645B] uppercase">Alamat Lengkap</dt>
                    <dd class="mt-1 text-[#172923] font-medium leading-relaxed">{{ $school->address }}</dd>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2 border-t border-[#EDF2ED]">
                    <div>
                        <dt class="text-[11px] font-semibold text-[#53645B] uppercase">Latitude</dt>
                        <dd class="mt-0.5 font-mono text-[#172923]">{{ $school->latitude ? number_format($school->latitude, 6) : '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold text-[#53645B] uppercase">Longitude</dt>
                        <dd class="mt-0.5 font-mono text-[#172923]">{{ $school->longitude ? number_format($school->longitude, 6) : '-' }}</dd>
                    </div>
                </div>

                @if ($school->latitude && $school->longitude)
                    <div class="pt-2">
                        <a href="https://www.google.com/maps?q={{ $school->latitude }},{{ $school->longitude }}" 
                           target="_blank" 
                           class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#1C6654] hover:underline">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            <span>Buka di Google Maps Eksternal</span>
                        </a>
                    </div>
                @endif

                <div class="pt-3 border-t border-[#EDF2ED]">
                    <dt class="text-[11px] font-semibold text-[#53645B] uppercase">Portal Transparansi Publik</dt>
                    <dd class="mt-1">
                        <a href="{{ route('public.schools.show', $school->public_slug) }}" 
                           target="_blank" 
                           class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#1C6654] hover:underline bg-[#EDF7F2] px-3 py-1.5 rounded-lg border border-[#BCE2D0]">
                            <span>{{ route('public.schools.show', $school->public_slug) }}</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Card 2: PIC Sekolah -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 sm:p-6 subtle-shadow flex flex-col justify-between">
            <div>
                <h2 class="text-xs font-bold text-[#53645B] uppercase tracking-wider mb-4 pb-2 border-b border-[#EDF2ED] flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#1C6654]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>Person in Charge (PIC Sekolah)</span>
                </h2>

                @if ($currentPic)
                    <div class="bg-[#FAFBF9] rounded-xl border border-[#DCE5DD] p-4 mb-4">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-[#1C6654] text-white flex items-center justify-center font-bold text-sm">
                                {{ strtoupper(substr($currentPic->name, 0, 2)) }}
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-[#172923]">{{ $currentPic->name }}</h3>
                                <p class="text-xs text-[#53645B]">{{ $currentPic->email }}</p>
                            </div>
                        </div>

                        <div class="text-xs space-y-1.5 pt-2 border-t border-[#EDF2ED]">
                            <div class="flex justify-between">
                                <span class="text-[#53645B]">Status Akun:</span>
                                <span class="font-semibold {{ $currentPic->is_active ? 'text-[#21684B]' : 'text-[#9B3232]' }}">
                                    {{ $currentPic->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#53645B]">Peran Sistem:</span>
                                <span class="font-semibold text-[#172923]">{{ $currentPic->role->label() }}</span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-[#FDF2F2] rounded-xl border border-[#F5C2C2] p-4 text-center mb-4">
                        <svg class="w-8 h-8 text-[#9B3232] mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <p class="text-xs font-semibold text-[#9B3232]">Belum ada PIC yang ditugaskan</p>
                        <p class="text-[11px] text-[#53645B] mt-1">Sekolah memerlukan PIC untuk konfirmasi porsi harian dan penerimaan distribusi makanan.</p>
                    </div>
                @endif
            </div>

            <!-- Card 3: Data Pokok Siswa -->
            <div class="pt-4 border-t border-[#EDF2ED]">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold text-[#53645B] uppercase block">Data Pokok Siswa Terdaftar</span>
                        <span class="text-2xl font-bold text-[#172923] tabular-nums">{{ number_format($school->registered_beneficiaries) }} <span class="text-xs font-normal text-[#53645B]">anak</span></span>
                    </div>
                    <span class="text-xs font-medium text-[#21684B] bg-[#EDF7F2] px-2.5 py-1 rounded-md border border-[#BCE2D0]">
                        Master Data
                    </span>
                </div>
            </div>
        </div>
    </div>
</x-app-shell>
