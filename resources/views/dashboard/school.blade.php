<x-app-shell title="Receipt Workflow : PIC Sekolah">
    <!-- Header & Unit Identity -->
    <header class="mb-5 pb-4 border-b border-[#DCE5DD]">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                    <x-role-badge :role="$user->role" />
                    <span class="text-xs font-mono text-[#53645B]">Unit: {{ $school->school_code }}</span>
                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-[#EDF2ED] text-[#172923] border border-[#DCE5DD]">
                        Jenjang {{ $school->education_level }}
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">Receipt Workflow : {{ $school->name }}</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">Penanggung Jawab Distribusi: <strong>{{ $user->name }}</strong></p>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto">
                <a href="{{ route('public.schools.show', $school->public_slug) }}" 
                   target="_blank"
                   rel="noopener"
                   class="min-h-[44px] px-3.5 py-2 rounded-xl bg-white border border-[#DCE5DD] text-xs font-bold text-[#1C6654] hover:bg-[#EDF5F2] hover:border-[#1C6654] transition-colors inline-flex items-center gap-2 subtle-shadow focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                    <span>Portal Publik Sekolah</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
            </div>
        </div>
    </header>

    <!-- Tenant Isolation Guard Banner -->
    <section aria-label="Status Isolasi Data" class="mb-5 p-3.5 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-[#1B573E] text-xs flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <svg class="w-4 h-4 text-[#21684B] shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>Scope Terisolasi: Anda hanya memiliki akses operasional terhadap data <strong>{{ $school->name }}</strong>.</span>
        </div>
        <span class="text-[11px] font-mono font-bold bg-white text-[#21684B] px-2.5 py-1 rounded-md border border-[#BCE2D0]">
            TENANT SECURE
        </span>
    </section>

    <!-- School Metrics (First Fold Truth) -->
    <section aria-label="Metrik Sekolah dan Rekanan" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
            <span class="text-xs text-[#53645B] block mb-1">Siswa Terdaftar (Data Pokok)</span>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-[#1C6654] tabular-nums">{{ number_format($school->registered_beneficiaries) }}</span>
                <span class="text-xs text-[#53645B]">siswa</span>
            </div>
            <p class="text-[11px] text-[#53645B] mt-1.5 leading-relaxed">
                Basis alokasi porsi. Konfirmasi kebutuhan harian aktual dikirim H-1 sebelum masak.
            </p>
        </div>

        <div class="bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
            <span class="text-xs text-[#53645B] block mb-1">Mitra SPPG Penyedia</span>
            <span class="text-sm font-bold text-[#172923] block">{{ $sppg->name }}</span>
            <span class="text-xs font-mono text-[#53645B] block mt-0.5">Kode: {{ $sppg->code }}</span>
            <span class="inline-flex items-center gap-1.5 text-[11px] text-[#21684B] font-bold mt-2">
                <span class="w-1.5 h-1.5 rounded-full bg-[#21684B]" aria-hidden="true"></span>
                <span>Terhubung Resmi</span>
            </span>
        </div>

        <div class="bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
            <span class="text-xs text-[#53645B] block mb-1">Titik Drop-Off & Alamat</span>
            <p class="text-xs text-[#172923] leading-relaxed">{{ $school->address }}</p>
            <span class="text-[11px] font-mono text-[#53645B] block mt-1.5">Jenjang Layanan: {{ $school->education_level }}</span>
        </div>
    </section>

    <!-- Receipt Workflow Stages (Structured Inspection Protocol) -->
    <x-card title="Tahapan Serah Terima & Verifikasi Ompreng" subtitle="Alur kerja terstruktur PIC Sekolah saat armada pengantar MBG tiba di lokasi">
        <div class="space-y-3.5">
            <!-- Step 1 -->
            <div class="p-4 rounded-xl border border-[#DCE5DD] bg-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-start gap-3.5">
                    <div class="w-8 h-8 rounded-full bg-[#1C6654] text-white flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">
                        1
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sm text-[#172923]">Pemeriksaan Fisik & Segel Suhu</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#EDF7F2] text-[#1B573E] border border-[#BCE2D0]">KEDATANGAN</span>
                        </div>
                        <p class="text-xs text-[#53645B] mt-1 leading-relaxed">
                            Pastikan wadah ompreng dalam kondisi tersegel utuh, tutup rapat, dan makanan tiba dalam kondisi hangat (&gt;60°C).
                        </p>
                    </div>
                </div>
                <div class="sm:text-right shrink-0">
                    <span class="text-xs font-medium text-[#1C6654] bg-[#F5F7F3] px-3 py-1.5 rounded-lg border border-[#DCE5DD] inline-block">
                        Standar Sanitasi BGN
                    </span>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="p-4 rounded-xl border border-[#DCE5DD] bg-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-start gap-3.5">
                    <div class="w-8 h-8 rounded-full bg-[#1C6654] text-white flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">
                        2
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sm text-[#172923]">Uji Organoleptik Cepat (Tim 3 Guru)</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#FAF4EB] text-[#885D1C] border border-[#E8D7BE]">SEBELUM MAKAN</span>
                        </div>
                        <p class="text-xs text-[#53645B] mt-1 leading-relaxed">
                            Cicipi sampel acak bersama 3 perwakilan guru/komite untuk memverifikasi aroma, rasa, dan kematangan sebelum dibagikan ke siswa.
                        </p>
                    </div>
                </div>
                <div class="sm:text-right shrink-0">
                    <span class="text-xs font-medium text-[#885D1C] bg-[#FAF4EB] px-3 py-1.5 rounded-lg border border-[#E8D7BE] inline-block">
                        Protokol Pencegahan
                    </span>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="p-4 rounded-xl border border-[#DCE5DD] bg-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-start gap-3.5">
                    <div class="w-8 h-8 rounded-full bg-[#1C6654] text-white flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">
                        3
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sm text-[#172923]">Rekonsiliasi Porsi & Catatan Deviasi</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#F2EDF7] text-[#5C3280] border border-[#DFCBEF]">DATA AKHIR</span>
                        </div>
                        <p class="text-xs text-[#53645B] mt-1 leading-relaxed">
                            Bandingkan jumlah fisik ompreng diterima dengan target porsi. Catat jika terdapat ompreng rusak atau kekurangan porsi.
                        </p>
                    </div>
                </div>
                <div class="sm:text-right shrink-0">
                    <span class="text-xs font-medium text-[#5C3280] bg-[#F2EDF7] px-3 py-1.5 rounded-lg border border-[#DFCBEF] inline-block">
                        Pencatatan Selisih
                    </span>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="p-4 rounded-xl border border-[#DCE5DD] bg-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-start gap-3.5">
                    <div class="w-8 h-8 rounded-full bg-[#E5ECE7] text-[#172923] flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">
                        4
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sm text-[#172923]">Konsolidasi Wadah Kosong untuk Pickup</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#F5F7F3] text-[#53645B] border border-[#DCE5DD]">PASCA MAKAN</span>
                        </div>
                        <p class="text-xs text-[#53645B] mt-1 leading-relaxed">
                            Kumpulkan wadah ompreng yang telah dibilas bersih ke titik drop-off sekolah untuk diambil kembali oleh armada kurir SPPG.
                        </p>
                    </div>
                </div>
                <div class="sm:text-right shrink-0">
                    <span class="text-xs font-medium text-[#53645B] bg-[#F5F7F3] px-3 py-1.5 rounded-lg border border-[#DCE5DD] inline-block">
                        Logistik Sirkular
                    </span>
                </div>
            </div>
        <div class="mt-5 p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-[#1B573E] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <span class="font-bold text-xs block text-[#172923]">Konfirmasi Kebutuhan Porsi Harian (P02) Aktif</span>
                <span class="text-xs text-[#53645B] mt-0.5 block">Kirim konfirmasi jumlah siswa hadir dan kebutuhan ompreng H-1 sebelum pukul 16:00 WIB.</span>
            </div>
            <a href="{{ route('school.portions.index') }}" 
               class="min-h-[44px] px-4 py-2 rounded-xl bg-[#1C6654] hover:bg-[#154D40] text-white text-xs font-bold transition-colors inline-flex items-center justify-center gap-2 subtle-shadow shrink-0 focus:outline-none focus:ring-2 focus:ring-[#1C6654] focus:ring-offset-2">
                <span>Buka Konfirmasi Porsi</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>
        </div>
    </x-card>
</x-app-shell>
