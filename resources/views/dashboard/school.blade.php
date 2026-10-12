<x-app-shell title="Receipt Workflow — PIC Sekolah">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <x-role-badge :role="$user->role" />
                <span class="text-xs text-[#53645B]">Unit: {{ $school->school_code }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">Receipt Workflow — {{ $school->name }}</h1>
            <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">Penanggung Jawab Sekolah: {{ $user->name }}</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('public.schools.show', $school->public_slug) }}" 
               target="_blank"
               class="px-3 py-1.5 rounded-lg bg-white border border-[#DCE5DD] text-xs font-semibold text-[#1C6654] hover:bg-[#EDF5F2] transition-colors inline-flex items-center gap-1.5 shadow-xs">
                <span>Portal Publik Sekolah</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
        </div>
    </div>

    <!-- Tenant Isolation Notice -->
    <div class="mb-5 p-3.5 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-[#1B573E] text-xs flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-[#21684B] shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>Scope Terisolasi: Anda hanya memiliki akses terhadap data operasional <strong>{{ $school->name }}</strong>.</span>
        </div>
        <span class="text-[11px] font-mono bg-white px-2 py-0.5 rounded border border-[#BCE2D0]">TENANT SECURE</span>
    </div>

    <!-- School Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-card title="Target Porsi Sekolah">
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-[#1C6654] tabular-nums">{{ number_format($school->registered_beneficiaries) }}</span>
                <span class="text-xs text-[#53645B]">porsi</span>
            </div>
            <p class="text-xs text-[#53645B] mt-1">Siswa penerima terdaftar</p>
        </x-card>

        <x-card title="Mitra SPPG Penyedia">
            <span class="text-sm font-bold text-[#172923] block">{{ $sppg->name }}</span>
            <span class="text-xs text-[#53645B] block mt-1">Kode: {{ $sppg->code }}</span>
            <span class="text-[11px] text-[#21684B] font-medium block mt-1">Terhubung Resmi</span>
        </x-card>

        <x-card title="Alamat Titik Serah">
            <p class="text-xs text-[#172923] leading-relaxed">{{ $school->address }}</p>
            <span class="text-[11px] text-[#53645B] block mt-1">Jenjang: {{ $school->education_level }}</span>
        </x-card>
    </div>

    <!-- Receipt Workflow Steps Checklist -->
    <x-card title="Tahapan Serah Terima & Verifikasi Ompreng" subtitle="Alur kerja PIC saat armada pengantar tiba di sekolah">
        <div class="space-y-3">
            <div class="p-3.5 rounded-xl border border-[#DCE5DD] bg-white flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-[#1C6654] text-white flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">1</div>
                <div>
                    <span class="font-bold text-sm text-[#172923] block">Pemeriksaan Fisik & Suhu Makanan</span>
                    <p class="text-xs text-[#53645B] mt-0.5">Pastikan wadah ompreng dalam kondisi tersegel, bersih, dan suhu makanan layak konsumsi.</p>
                </div>
            </div>

            <div class="p-3.5 rounded-xl border border-[#DCE5DD] bg-white flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-[#1C6654] text-white flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">2</div>
                <div>
                    <span class="font-bold text-sm text-[#172923] block">Uji Organoleptik Sederhana (3 Orang Guru)</span>
                    <p class="text-xs text-[#53645B] mt-0.5">Verifikasi rasa, aroma, dan tekstur sebelum pembagian ke ruang kelas.</p>
                </div>
            </div>

            <div class="p-3.5 rounded-xl border border-[#DCE5DD] bg-white flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-[#1C6654] text-white flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">3</div>
                <div>
                    <span class="font-bold text-sm text-[#172923] block">Konfirmasi Jumlah Porsi Terima & Pengembalian Ompreng</span>
                    <p class="text-xs text-[#53645B] mt-0.5">Catat jumlah diterima, porsi rusak/kurang (bila ada), dan siapkan wadah bersih untuk pickup kurir.</p>
                </div>
            </div>
        </div>
    </x-card>
</x-app-shell>
