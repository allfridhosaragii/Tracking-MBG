<x-app-shell title="Detail Profil Sekolah — {{ $school->name }}">
    <!-- Breadcrumb & Header -->
    <header class="mb-6 pb-4 border-b border-[#DCE5DD]">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-[#EDF7F2] text-[#1B573E] border border-[#BCE2D0]">
                        Jenjang {{ $school->education_level }}
                    </span>
                    <span class="text-xs font-mono text-[#53645B]">NPSN/Kode: {{ $school->school_code }}</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">{{ $school->name }}</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">{{ $school->address }}</p>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto">
                <a href="{{ url()->previous() ?: route('head.dashboard') }}" 
                   class="min-h-[44px] px-4 py-2 rounded-xl bg-white border border-[#DCE5DD] text-xs font-bold text-[#172923] hover:bg-[#F5F7F3] hover:border-[#1C6654] transition-colors inline-flex items-center gap-2 subtle-shadow focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                    <svg class="w-4 h-4 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
            </div>
        </div>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
        <!-- School Information Card -->
        <x-card title="Informasi Unit Sekolah" subtitle="Data profil pokok sekolah penerima manfaat MBG">
            <dl class="space-y-3 text-xs sm:text-sm">
                <div class="flex items-center justify-between border-b border-[#EDF2ED] pb-2.5">
                    <dt class="text-[#53645B]">Kode Sekolah / NPSN:</dt>
                    <dd class="font-mono font-bold text-[#172923]">{{ $school->school_code }}</dd>
                </div>
                <div class="flex items-center justify-between border-b border-[#EDF2ED] pb-2.5">
                    <dt class="text-[#53645B]">Jenjang Pendidikan:</dt>
                    <dd class="font-bold text-[#172923]">{{ $school->education_level }}</dd>
                </div>
                <div class="flex items-center justify-between border-b border-[#EDF2ED] pb-2.5">
                    <dt class="text-[#53645B]">Alokasi Sasaran Siswa:</dt>
                    <dd class="font-bold text-[#1C6654] tabular-nums">{{ number_format($school->registered_beneficiaries) }} porsi/hari</dd>
                </div>
                <div class="flex items-center justify-between border-b border-[#EDF2ED] pb-2.5">
                    <dt class="text-[#53645B]">Slug Transparansi Publik:</dt>
                    <dd class="font-mono text-xs text-[#1C6654]">
                        <a href="{{ route('public.schools.show', $school->public_slug) }}" target="_blank" rel="noopener" class="hover:underline">
                            /public/schools/{{ $school->public_slug }} &nearr;
                        </a>
                    </dd>
                </div>
                <div class="flex items-start justify-between pt-1">
                    <dt class="text-[#53645B]">Alamat Pengiriman:</dt>
                    <dd class="text-[#172923] text-right max-w-xs leading-relaxed">{{ $school->address }}</dd>
                </div>
            </dl>
        </x-card>

        <!-- SPPG Partnership Card -->
        <x-card title="Kemitraan SPPG Penanggung Jawab" subtitle="Satuan Pelayanan Pemenuhan Gizi pelaksana">
            <dl class="space-y-3 text-xs sm:text-sm">
                <div class="flex items-center justify-between border-b border-[#EDF2ED] pb-2.5">
                    <dt class="text-[#53645B]">Nama SPPG:</dt>
                    <dd class="font-bold text-[#172923]">{{ $sppg->name }}</dd>
                </div>
                <div class="flex items-center justify-between border-b border-[#EDF2ED] pb-2.5">
                    <dt class="text-[#53645B]">Kode Registrasi SPPG:</dt>
                    <dd class="font-mono font-bold text-[#172923]">{{ $sppg->code }}</dd>
                </div>
                <div class="flex items-center justify-between border-b border-[#EDF2ED] pb-2.5">
                    <dt class="text-[#53645B]">Status Kemitraan:</dt>
                    <dd>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded text-xs font-bold bg-[#EDF7F2] text-[#1B573E] border border-[#BCE2D0]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#21684B]" aria-hidden="true"></span>
                            <span>Aktif Melayani</span>
                        </span>
                    </dd>
                </div>
                <div class="flex items-start justify-between pt-1">
                    <dt class="text-[#53645B]">Alamat Dapur SPPG:</dt>
                    <dd class="text-[#53645B] text-right max-w-xs leading-relaxed">{{ $sppg->address }}</dd>
                </div>
            </dl>
        </x-card>
    </div>
</x-app-shell>
