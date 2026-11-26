<x-app-shell title="Beranda Operasional | {{ $school->name }}">
    <div class="space-y-6">
        <!-- School Operational Header -->
        <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-5 border-b border-[#DCE5DD]">
            <div class="space-y-1.5">
                <div class="flex flex-wrap items-center gap-2">
                    <x-role-badge :role="$user->role" />
                    <span class="text-xs font-mono text-[#53645B] bg-white border border-[#DCE5DD] px-2.5 py-0.5 rounded-lg">
                        Unit: {{ $school->school_code }}
                    </span>
                    <span class="px-2.5 py-0.5 rounded-lg text-[11px] font-bold bg-[#FAFBF9] text-[#172923] border border-[#DCE5DD]">
                        Jenjang {{ $school->education_level }}
                    </span>
                </div>
                <h1 class="page-title">{{ $school->name }}</h1>
                <p class="text-xs sm:text-sm text-[#53645B]">
                    PIC Bertugas: <strong class="text-[#172923]">{{ $user->name }}</strong>
                    <span class="font-mono text-xs text-[#53645B]">({{ $user->email }})</span>
                </p>
            </div>

            <a href="{{ route('public.schools.show', $school->public_slug) }}"
               target="_blank"
               rel="noopener"
               class="min-h-[44px] self-start sm:self-auto px-4 py-2 rounded-xl bg-white border border-[#DCE5DD] text-xs font-bold text-[#1C6654] hover:bg-[#EDF7F2] hover:border-[#1C6654] smooth-transition inline-flex items-center gap-2 subtle-shadow">
                <span>Portal Publik Sekolah</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        </header>

        <!-- Primary First-Fold Daily Actions (All 4 PIC Modules Accessible in 1 Tap) -->
        <section aria-labelledby="daily-actions-heading" class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 id="daily-actions-heading" class="eyebrow-label">Tugas Harian PIC Sekolah</h2>
                <span class="text-[11px] font-mono font-bold bg-[#EDF7F2] text-[#21684B] px-2.5 py-0.5 rounded-md border border-[#BCE2D0]">
                    TENANT SECURE
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- 1. Portion Confirmation (P02) -->
                <article class="luxury-surface p-5 flex flex-col justify-between gap-4">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="eyebrow-label text-[#1C6654]">1. Kebutuhan Harian (P02)</span>
                            @if ($todayPortionRequest)
                                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-md border {{ $todayPortionRequest->status->badgeClasses() }}">
                                    {{ $todayPortionRequest->status->label() }}
                                </span>
                            @else
                                <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-md border bg-[#FEF7EC] text-[#885D1C] border-[#F7D7A3]">
                                    Belum Dikonfirmasi
                                </span>
                            @endif
                        </div>
                        <h3 class="section-title">Konfirmasi Kebutuhan Porsi</h3>
                        <p class="text-xs text-[#53645B] leading-relaxed">
                            Kirim jumlah siswa hadir dan kebutuhan ompreng H-1 sebelum pukul 16:00 WIB sebagai rujukan masak SPPG.
                        </p>
                    </div>
                    <a href="{{ route('school.portions.index') }}"
                       class="min-h-[44px] w-full px-4 py-2.5 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs font-bold smooth-transition inline-flex items-center justify-between subtle-shadow">
                        <span>Buka Konfirmasi Porsi</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </article>

                <!-- 2. Receipt & Organoleptic Arrival (P04) -->
                <article class="luxury-surface p-5 flex flex-col justify-between gap-4 {{ $awaitingReceiptsCount > 0 ? 'border-[#885D1C] ring-1 ring-[#F7D7A3]' : '' }}">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="eyebrow-label text-[#1C6654]">2. Kedatangan Armada (P04)</span>
                            <span class="text-[11px] font-bold tabular-nums px-2.5 py-0.5 rounded-md border {{ $awaitingReceiptsCount > 0 ? 'bg-[#FEF7EC] text-[#885D1C] border-[#F7D7A3]' : 'bg-[#FAFBF9] text-[#53645B] border-[#DCE5DD]' }}">
                                {{ $awaitingReceiptsCount }} Menunggu Terima
                            </span>
                        </div>
                        <h3 class="section-title">Penerimaan &amp; Uji Organoleptik</h3>
                        <p class="text-xs text-[#53645B] leading-relaxed">
                            Hitung porsi fisik ompreng saat kurir tiba, periksa suhu dan kelayakan pangan, lalu sahkan berita acara.
                        </p>
                    </div>
                    <a href="{{ route('school.receipts.index') }}"
                       class="min-h-[44px] w-full px-4 py-2.5 rounded-xl {{ $awaitingReceiptsCount > 0 ? 'bg-[#885D1C] hover:bg-[#6E4A15] text-white' : 'bg-[#1C6654] hover:bg-[#164D42] text-white' }} text-xs font-bold smooth-transition inline-flex items-center justify-between subtle-shadow">
                        <span>Buka Penerimaan Makanan</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </article>

                <!-- 3. Pre-Consumption Check & Distribution (P05) -->
                <article class="luxury-surface p-5 flex flex-col justify-between gap-4">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="eyebrow-label text-[#285F80]">3. Jam Makan Siswa (P05)</span>
                            <span class="text-[11px] font-bold tabular-nums px-2.5 py-0.5 rounded-md border {{ $activeDistributionsCount > 0 ? 'bg-[#F0F6FA] text-[#285F80] border-[#C2DBE8]' : 'bg-[#FAFBF9] text-[#53645B] border-[#DCE5DD]' }}">
                                {{ $activeDistributionsCount }} Layanan Aktif
                            </span>
                        </div>
                        <h3 class="section-title">Pembagian Makanan &amp; Uji ke-2</h3>
                        <p class="text-xs text-[#53645B] leading-relaxed">
                            Lakukan pemeriksaan organoleptik sesaat sebelum makan, mulai pembagian, dan catat porsi terbagi serta sisa.
                        </p>
                    </div>
                    <a href="{{ route('school.meal-distribution.index') }}"
                       class="min-h-[44px] w-full px-4 py-2.5 rounded-xl bg-white hover:bg-[#FAFBF9] text-[#172923] border border-[#DCE5DD] text-xs font-bold smooth-transition inline-flex items-center justify-between">
                        <span>Buka Pembagian Makanan</span>
                        <svg class="w-4 h-4 text-[#1C6654]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </article>

                <!-- 4. Reverse Logistics Trays (P06) -->
                <article class="luxury-surface p-5 flex flex-col justify-between gap-4">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="eyebrow-label text-[#885D1C]">4. Logistik Balik (P06)</span>
                            <span class="text-[11px] font-bold tabular-nums px-2.5 py-0.5 rounded-md border {{ $inUseTraysCount > 0 ? 'bg-[#FEF7EC] text-[#885D1C] border-[#F7D7A3]' : 'bg-[#FAFBF9] text-[#53645B] border-[#DCE5DD]' }}">
                                {{ $inUseTraysCount }} Siklus di Sekolah
                            </span>
                        </div>
                        <h3 class="section-title">Kesiapan &amp; Retur Ompreng</h3>
                        <p class="text-xs text-[#53645B] leading-relaxed">
                            Hitung wadah kosong pasca makan dan tandai siap dijemput agar kurir SPPG dapat mengambil ompreng balik.
                        </p>
                    </div>
                    <a href="{{ route('school.trays.index') }}"
                       class="min-h-[44px] w-full px-4 py-2.5 rounded-xl bg-white hover:bg-[#FAFBF9] text-[#172923] border border-[#DCE5DD] text-xs font-bold smooth-transition inline-flex items-center justify-between">
                        <span>Kelola Wadah Ompreng</span>
                        <svg class="w-4 h-4 text-[#1C6654]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </article>
            </div>
        </section>

        <!-- School & SPPG Partnership Summary -->
        <section aria-label="Metrik Sekolah dan Rekanan" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="luxury-surface p-4 sm:p-5">
                <span class="eyebrow-label block mb-1">Siswa Terdaftar (Data Pokok)</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-[#1C6654] tabular-nums">{{ number_format($school->registered_beneficiaries) }}</span>
                    <span class="text-xs text-[#53645B]">siswa</span>
                </div>
                <p class="text-[11px] text-[#53645B] mt-1.5 leading-relaxed">
                    Basis alokasi sasaran. Angka kebutuhan aktual dikonfirmasi harian pada modul P02.
                </p>
            </div>

            <div class="luxury-surface p-4 sm:p-5">
                <span class="eyebrow-label block mb-1">Mitra SPPG Penyedia</span>
                <span class="text-sm font-bold text-[#172923] block">{{ $sppg->name }}</span>
                <span class="text-xs font-mono text-[#53645B] block mt-0.5">Kode: {{ $sppg->code }}</span>
                <span class="inline-flex items-center gap-1.5 text-[11px] text-[#21684B] font-bold mt-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#21684B]" aria-hidden="true"></span>
                    <span>Terhubung Resmi</span>
                </span>
            </div>

            <div class="luxury-surface p-4 sm:p-5">
                <span class="eyebrow-label block mb-1">Titik Drop-Off &amp; Isolasi Scope</span>
                <p class="text-xs text-[#172923] leading-relaxed">{{ $school->address }}</p>
                <p class="text-[11px] text-[#21684B] font-medium mt-1.5">
                    Scope terisolasi khusus untuk data <strong>{{ $school->name }}</strong>.
                </p>
            </div>
        </section>

        <!-- Progressive Disclosure for 4-Step Inspection SOP -->
        <details class="sop-disclosure">
            <summary>
                <span class="flex items-center gap-2 text-xs font-bold text-[#172923]">
                    <svg class="w-4 h-4 text-[#1C6654] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>Tahapan Serah Terima &amp; Verifikasi Ompreng (SOP Sekolah)</span>
                </span>
                <span class="text-[11px] font-semibold text-[#1C6654]">4 Tahap SOP</span>
            </summary>
            <div class="sop-disclosure-body grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                <div class="inset-surface p-3.5 space-y-1">
                    <span class="font-bold text-[#172923] block">1. Pemeriksaan Fisik &amp; Segel Suhu (Kedatangan)</span>
                    <p class="text-[#53645B] leading-relaxed">Pastikan wadah ompreng tersegel utuh, tutup rapat, dan makanan tiba dalam kondisi hangat (&gt;60°C).</p>
                </div>
                <div class="inset-surface p-3.5 space-y-1">
                    <span class="font-bold text-[#172923] block">2. Uji Organoleptik Cepat (Sebelum Makan)</span>
                    <p class="text-[#53645B] leading-relaxed">Periksa rupa, aroma, tekstur, dan cicipi sampel bila aman sebelum dibagikan ke ruang kelas.</p>
                </div>
                <div class="inset-surface p-3.5 space-y-1">
                    <span class="font-bold text-[#172923] block">3. Rekonsiliasi Porsi &amp; Catatan Deviasi</span>
                    <p class="text-[#53645B] leading-relaxed">Bandingkan fisik ompreng diterima dengan target porsi dan dokumentasikan jika ada kerusakan.</p>
                </div>
                <div class="inset-surface p-3.5 space-y-1">
                    <span class="font-bold text-[#172923] block">4. Konsolidasi Wadah Kosong untuk Pickup</span>
                    <p class="text-[#53645B] leading-relaxed">Kumpulkan wadah ompreng yang telah dibilas bersih ke titik serah sekolah untuk diambil kurir SPPG.</p>
                </div>
            </div>
        </details>
    </div>
</x-app-shell>
