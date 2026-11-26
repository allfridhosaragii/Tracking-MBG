<x-app-shell title="Detail Profil Sekolah | {{ $school->name }}">
    <div class="space-y-6">
        <!-- Page Header -->
        <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-5 border-b border-[#DCE5DD]">
            <div class="space-y-1.5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-[#EDF7F2] text-[#21684B] border border-[#BCE2D0]">
                        Jenjang {{ $school->education_level }}
                    </span>
                    <span class="text-xs font-mono text-[#53645B]">Kode: {{ $school->school_code }}</span>
                </div>
                <h1 class="page-title">{{ $school->name }}</h1>
                <p class="text-xs sm:text-sm text-[#53645B]">{{ $school->address }}</p>
            </div>

            <a href="{{ url()->previous() ?: route('head.dashboard') }}"
               class="min-h-[44px] self-start sm:self-auto px-4 py-2 rounded-xl bg-white border border-[#DCE5DD] text-xs font-bold text-[#172923] hover:bg-[#FAFBF9] smooth-transition inline-flex items-center gap-2 subtle-shadow">
                <svg class="w-4 h-4 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Kembali</span>
            </a>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- School Information Card -->
            <x-card title="Informasi Unit Sekolah" subtitle="Data profil pokok sekolah penerima manfaat MBG">
                <dl class="space-y-3 text-xs sm:text-sm">
                    <div class="flex items-center justify-between border-b border-[#E7ECE8] pb-2.5">
                        <dt class="text-[#53645B]">Kode Sekolah / NPSN</dt>
                        <dd class="font-mono font-bold text-[#172923]">{{ $school->school_code }}</dd>
                    </div>
                    <div class="flex items-center justify-between border-b border-[#E7ECE8] pb-2.5">
                        <dt class="text-[#53645B]">Jenjang Pendidikan</dt>
                        <dd class="font-bold text-[#172923]">{{ $school->education_level }}</dd>
                    </div>
                    <div class="flex items-center justify-between border-b border-[#E7ECE8] pb-2.5">
                        <dt class="text-[#53645B]">Alokasi Sasaran Siswa</dt>
                        <dd class="font-bold text-[#1C6654] tabular-nums">{{ number_format($school->registered_beneficiaries) }} porsi/hari</dd>
                    </div>
                    <div class="flex items-center justify-between border-b border-[#E7ECE8] pb-2.5">
                        <dt class="text-[#53645B]">Portal Transparansi Publik</dt>
                        <dd class="font-mono text-xs text-[#1C6654]">
                            <a href="{{ route('public.schools.show', $school->public_slug) }}" target="_blank" rel="noopener" class="hover:underline">
                                /public/schools/{{ $school->public_slug }} ↗
                            </a>
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 pt-1">
                        <dt class="text-[#53645B] shrink-0">Alamat Pengiriman</dt>
                        <dd class="text-[#172923] text-right leading-relaxed">{{ $school->address }}</dd>
                    </div>
                </dl>
            </x-card>

            <!-- SPPG Partnership Card -->
            <x-card title="Kemitraan SPPG Penanggung Jawab" subtitle="Satuan Pelayanan Pemenuhan Gizi pelaksana">
                <dl class="space-y-3 text-xs sm:text-sm">
                    <div class="flex items-center justify-between border-b border-[#E7ECE8] pb-2.5">
                        <dt class="text-[#53645B]">Nama SPPG</dt>
                        <dd class="font-bold text-[#172923]">{{ $sppg->name }}</dd>
                    </div>
                    <div class="flex items-center justify-between border-b border-[#E7ECE8] pb-2.5">
                        <dt class="text-[#53645B]">Kode Registrasi SPPG</dt>
                        <dd class="font-mono font-bold text-[#172923]">{{ $sppg->code }}</dd>
                    </div>
                    <div class="flex items-center justify-between border-b border-[#E7ECE8] pb-2.5">
                        <dt class="text-[#53645B]">Status Kemitraan</dt>
                        <dd>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-bold bg-[#EDF7F2] text-[#21684B] border border-[#BCE2D0]">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#21684B]" aria-hidden="true"></span>
                                <span>Aktif Melayani</span>
                            </span>
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 pt-1">
                        <dt class="text-[#53645B] shrink-0">Alamat Dapur SPPG</dt>
                        <dd class="text-[#53645B] text-right leading-relaxed">{{ $sppg->address }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>
</x-app-shell>
