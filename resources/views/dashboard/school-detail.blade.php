<x-app-shell title="Detail Sekolah — {{ $school->name }}">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-[#EDF2ED] text-[#172923] border border-[#DCE5DD]">
                    {{ $school->education_level }}
                </span>
                <span class="text-xs text-[#53645B]">Kode: {{ $school->school_code }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">{{ $school->name }}</h1>
            <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">{{ $school->address }}</p>
        </div>

        <a href="{{ route('head.dashboard') }}" 
           class="px-3 py-1.5 rounded-lg bg-white border border-[#DCE5DD] text-xs font-semibold text-[#172923] hover:bg-[#F5F7F3] transition-colors inline-flex items-center gap-1.5 self-start sm:self-auto">
            <span>&larr; Kembali ke Dashboard</span>
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <x-card title="Informasi Unit Sekolah">
            <dl class="space-y-2.5 text-xs sm:text-sm">
                <div class="flex justify-between border-b border-[#EDF2ED] pb-2">
                    <dt class="text-[#53645B]">Kode Resmi:</dt>
                    <dd class="font-mono font-semibold text-[#172923]">{{ $school->school_code }}</dd>
                </div>
                <div class="flex justify-between border-b border-[#EDF2ED] pb-2">
                    <dt class="text-[#53645B]">Jenjang Pendidikan:</dt>
                    <dd class="font-semibold text-[#172923]">{{ $school->education_level }}</dd>
                </div>
                <div class="flex justify-between border-b border-[#EDF2ED] pb-2">
                    <dt class="text-[#53645B]">Target Porsi Harian:</dt>
                    <dd class="font-bold text-[#1C6654] tabular-nums">{{ number_format($school->registered_beneficiaries) }} porsi</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-[#53645B]">Slug Halaman Publik:</dt>
                    <dd class="font-mono text-[#53645B]">{{ $school->public_slug }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card title="Kemitraan SPPG Penanggung Jawab">
            <dl class="space-y-2.5 text-xs sm:text-sm">
                <div class="flex justify-between border-b border-[#EDF2ED] pb-2">
                    <dt class="text-[#53645B]">Nama SPPG:</dt>
                    <dd class="font-semibold text-[#172923]">{{ $sppg->name }}</dd>
                </div>
                <div class="flex justify-between border-b border-[#EDF2ED] pb-2">
                    <dt class="text-[#53645B]">Kode SPPG:</dt>
                    <dd class="font-mono font-semibold text-[#172923]">{{ $sppg->code }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-[#53645B]">Alamat Dapur:</dt>
                    <dd class="text-[#53645B] text-right max-w-xs">{{ $sppg->address }}</dd>
                </div>
            </dl>
        </x-card>
    </div>
</x-app-shell>
