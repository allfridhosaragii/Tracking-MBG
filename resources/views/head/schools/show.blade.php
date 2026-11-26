<x-app-shell title="Detail Sekolah : {{ $school->name }}">
    <div class="space-y-5">
        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <a href="{{ route('head.schools.index') }}" class="text-xs font-semibold text-[#1C6654] hover:underline inline-flex items-center gap-1 mb-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span>Daftar Sekolah Binaan</span>
                </a>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="page-title">{{ $school->name }}</h1>
                    <span class="px-2.5 py-0.5 rounded-lg text-xs font-semibold border {{ $school->is_active ? 'bg-[#EDF7F2] text-[#21684B] border-[#BCE2D0]' : 'bg-[#FDF2F2] text-[#9B3232] border-[#F5C2C2]' }}">
                        {{ $school->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
                <p class="text-xs text-[#53645B] mt-1 font-mono">{{ $school->school_code }} • Jenjang {{ $school->education_level }}</p>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('head.schools.edit', $school) }}"
                   class="min-h-[44px] px-4 py-2 text-xs font-semibold text-[#1C6654] bg-[#EDF7F2] hover:bg-[#DCF0E6] border border-[#BCE2D0] rounded-xl inline-flex items-center gap-1.5 smooth-transition">
                    <span>Edit Sekolah</span>
                </a>

                <form method="POST" action="{{ route('head.schools.toggle-active', $school) }}" class="inline" onsubmit="return confirm('Ubah status aktif sekolah ini?')">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                            class="min-h-[44px] px-4 py-2 text-xs font-semibold {{ $school->is_active ? 'text-[#9B3232] bg-[#FDF2F2] border border-[#F5C2C2]' : 'text-[#21684B] bg-[#EDF7F2] border border-[#BCE2D0]' }} rounded-xl cursor-pointer smooth-transition">
                        {{ $school->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </form>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Lokasi & Portal -->
            <x-card title="Lokasi &amp; Akses Transparansi">
                <dl class="space-y-4 text-xs sm:text-sm">
                    <div>
                        <dt class="eyebrow-label">Alamat Pengantaran</dt>
                        <dd class="mt-1 text-[#172923] font-medium leading-relaxed">{{ $school->address }}</dd>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-3 border-t border-[#E7ECE8]">
                        <div>
                            <dt class="eyebrow-label">Latitude</dt>
                            <dd class="mt-1 font-mono text-[#172923]">{{ $school->latitude ? number_format($school->latitude, 6) : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="eyebrow-label">Longitude</dt>
                            <dd class="mt-1 font-mono text-[#172923]">{{ $school->longitude ? number_format($school->longitude, 6) : '-' }}</dd>
                        </div>
                    </div>

                    @if ($school->latitude && $school->longitude)
                        <div>
                            <a href="https://www.google.com/maps?q={{ $school->latitude }},{{ $school->longitude }}"
                               target="_blank"
                               rel="noopener"
                               class="text-xs font-semibold text-[#1C6654] hover:underline inline-flex items-center gap-1.5">
                                <span>Buka Koordinat di Peta Eksternal</span>
                                <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    @endif

                    <div class="pt-3 border-t border-[#E7ECE8]">
                        <dt class="eyebrow-label">Portal Publik Wali Murid</dt>
                        <dd class="mt-1.5">
                            <a href="{{ route('public.schools.show', $school->public_slug) }}"
                               target="_blank"
                               rel="noopener"
                               class="min-h-[40px] px-3 py-2 rounded-xl bg-[#FAFBF9] hover:bg-[#EDF7F2] border border-[#DCE5DD] text-xs font-semibold text-[#1C6654] inline-flex items-center gap-2 break-all smooth-transition">
                                <span>{{ route('public.schools.show', $school->public_slug) }}</span>
                            </a>
                        </dd>
                    </div>
                </dl>
            </x-card>

            <!-- PIC & Kuota -->
            <x-card title="Person in Charge (PIC Sekolah)">
                <div class="space-y-5">
                    @if ($currentPic)
                        <div class="inset-surface p-4 space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-[#1C6654] text-white flex items-center justify-center font-bold text-sm shrink-0">
                                    {{ strtoupper(substr($currentPic->name, 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-bold text-sm text-[#172923] truncate">{{ $currentPic->name }}</h3>
                                    <p class="text-xs text-[#53645B] font-mono truncate">{{ $currentPic->email }}</p>
                                </div>
                            </div>

                            <div class="pt-2.5 border-t border-[#E7ECE8] flex items-center justify-between text-xs">
                                <span class="text-[#53645B]">Status Akun PIC</span>
                                <span class="font-semibold {{ $currentPic->is_active ? 'text-[#21684B]' : 'text-[#9B3232]' }}">
                                    {{ $currentPic->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="p-4 rounded-xl bg-[#FDF2F2] border border-[#F5C2C2] text-xs text-[#9B3232]">
                            <p class="font-bold">Belum Ada PIC Ditugaskan</p>
                            <p class="mt-1 text-[#53645B]">Tugaskan akun PIC melalui menu Edit Sekolah agar sekolah dapat mengonfirmasi porsi dan berita acara penerimaan.</p>
                        </div>
                    @endif

                    <div class="pt-4 border-t border-[#E7ECE8] flex items-center justify-between">
                        <div>
                            <span class="eyebrow-label block">Data Pokok Siswa Terdaftar</span>
                            <span class="text-2xl font-bold text-[#172923] tabular-nums mt-1 block">{{ number_format($school->registered_beneficiaries) }} <span class="text-xs font-normal text-[#53645B]">siswa</span></span>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-[#EDF7F2] text-[#21684B] border border-[#BCE2D0]">
                            Master Data
                        </span>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</x-app-shell>
