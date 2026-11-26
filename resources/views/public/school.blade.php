<x-app-shell title="Portal Transparansi | {{ $school->name }}">
    <div class="max-w-4xl mx-auto py-2 sm:py-6 space-y-6">
        <!-- Public Trust Editorial Hero -->
        <header class="luxury-surface p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 pb-6 border-b border-[#E7ECE8]">
                <div class="space-y-2">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-[#EDF7F2] text-[#21684B] border border-[#BCE2D0]">
                        <span class="w-2 h-2 rounded-full bg-[#21684B]" aria-hidden="true"></span>
                        <span>Portal Transparansi Publik MBG</span>
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-bold text-[#172923] tracking-tight">{{ $data['name'] }}</h1>
                    <p class="text-xs sm:text-sm text-[#53645B]">
                        Kode Sekolah: <strong class="font-mono text-[#172923]">{{ $data['school_code'] }}</strong> • Jenjang: <strong>{{ $data['education_level'] }}</strong>
                    </p>
                </div>

                <div class="inset-surface p-4 sm:p-5 text-left sm:text-right shrink-0">
                    <span class="eyebrow-label block mb-1">Alokasi Sasaran Siswa</span>
                    <div class="flex items-baseline gap-1.5 sm:justify-end">
                        <span class="text-3xl sm:text-4xl font-bold text-[#1C6654] tabular-nums">{{ number_format($data['beneficiaries_count']) }}</span>
                        <span class="text-xs text-[#53645B] font-semibold">porsi</span>
                    </div>
                    <span class="text-[11px] text-[#53645B] block mt-0.5">per hari efektif sekolah</span>
                </div>
            </div>

            <!-- Transparency Overview Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-6">
                <div>
                    <span class="eyebrow-label block mb-1.5">Mitra Dapur Pelayanan SPPG</span>
                    <span class="text-sm sm:text-base font-bold text-[#172923] block">{{ $data['sppg_partner'] }}</span>
                    <p class="text-xs text-[#53645B] mt-1 leading-relaxed">
                        Dapur penyedia pangan higienis terakreditasi di bawah pengawasan Badan Gizi Nasional (BGN).
                    </p>
                </div>

                <div>
                    <span class="eyebrow-label block mb-1.5">Status Penyelenggaraan</span>
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-lg bg-[#FEF7EC] text-[#885D1C] border border-[#F7D7A3]">
                        <span class="w-2 h-2 rounded-full bg-[#885D1C]" aria-hidden="true"></span>
                        <span>{{ $data['service_status'] }}</span>
                    </span>
                    <p class="text-xs text-[#53645B] mt-1.5 leading-relaxed">
                        Layanan makan bergizi beroperasi sesuai kalender akademik dan konfirmasi harian pihak sekolah.
                    </p>
                </div>
            </div>

            <div class="mt-6 pt-5 border-t border-[#E7ECE8] flex flex-wrap items-center justify-between gap-3">
                <span class="text-xs text-[#53645B]">Pantau status pengantaran hari ini secara terbuka tanpa akun:</span>
                <a href="{{ route('public.schools.delivery-status', $school->public_slug ?: $school->school_code) }}"
                   class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs font-bold subtle-shadow smooth-transition">
                    <span>Lihat Status Pengantaran Hari Ini</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            </div>
        </header>

        <!-- Food Composition & Quality Standards -->
        <section aria-labelledby="nutrition-standards-heading" class="luxury-surface p-6 sm:p-8">
            <h2 id="nutrition-standards-heading" class="section-title mb-1">
                Komposisi &amp; Standar Kualitas Makanan
            </h2>
            <p class="text-xs sm:text-sm text-[#53645B] mb-5">
                Setiap menu harian dirancang oleh Tenaga Gizi Terdaftar untuk memenuhi Angka Kecukupan Gizi (AKG) peserta didik.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                <div class="inset-surface p-4">
                    <span class="eyebrow-label text-[#1C6654] block mb-1">Karbohidrat</span>
                    <span class="text-sm font-bold text-[#172923] block">Nasi Pulen / Umbi Lokal</span>
                    <p class="text-xs text-[#53645B] mt-1 leading-relaxed">Sumber energi utama untuk konsentrasi belajar di kelas.</p>
                </div>

                <div class="inset-surface p-4">
                    <span class="eyebrow-label text-[#1C6654] block mb-1">Protein Berkualitas</span>
                    <span class="text-sm font-bold text-[#172923] block">Ayam, Ikan, Telur &amp; Tahu</span>
                    <p class="text-xs text-[#53645B] mt-1 leading-relaxed">Kombinasi protein hewani dan nabati untuk tumbuh kembang optimal.</p>
                </div>

                <div class="inset-surface p-4">
                    <span class="eyebrow-label text-[#1C6654] block mb-1">Sayur &amp; Serat</span>
                    <span class="text-sm font-bold text-[#172923] block">Sayur Segar Bening / Tumis</span>
                    <p class="text-xs text-[#53645B] mt-1 leading-relaxed">Kaya mikronutrien, vitamin, dan serat alami dari petani mitra.</p>
                </div>

                <div class="inset-surface p-4">
                    <span class="eyebrow-label text-[#1C6654] block mb-1">Buah Segar Pilihan</span>
                    <span class="text-sm font-bold text-[#172923] block">Pisang, Pepaya, Jeruk</span>
                    <p class="text-xs text-[#53645B] mt-1 leading-relaxed">Pelengkap vitamin C segar disajikan utuh higienis.</p>
                </div>
            </div>
        </section>

        <!-- Daily Quality & Delivery Flow -->
        <section aria-labelledby="flow-timeline-heading" class="luxury-surface p-6 sm:p-8">
            <h2 id="flow-timeline-heading" class="section-title mb-1">
                Alur Penjaminan Mutu &amp; Distribusi Harian
            </h2>
            <p class="text-xs sm:text-sm text-[#53645B] mb-5">
                Protokol berjenjang dari dapur SPPG hingga meja makan peserta didik di sekolah.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="inset-surface p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 rounded-full bg-[#1C6654] text-white flex items-center justify-center text-xs font-bold">1</span>
                        <span class="eyebrow-label text-[#172923]">Dapur SPPG</span>
                    </div>
                    <span class="font-bold text-sm text-[#172923] block">QC Mutu &amp; Pengemasan</span>
                    <p class="text-xs text-[#53645B] mt-1 leading-relaxed">
                        Pemeriksaan organoleptik oleh pengawas gizi, penyegelan ompreng food-grade, dan insulasi termal &gt;60°C.
                    </p>
                </div>

                <div class="inset-surface p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 rounded-full bg-[#1C6654] text-white flex items-center justify-center text-xs font-bold">2</span>
                        <span class="eyebrow-label text-[#172923]">Perjalanan Kurir</span>
                    </div>
                    <span class="font-bold text-sm text-[#172923] block">Distribusi Terjadwal</span>
                    <p class="text-xs text-[#53645B] mt-1 leading-relaxed">
                        Armada khusus membawa ompreng bersegel langsung menuju titik serah sekolah tanpa perantara.
                    </p>
                </div>

                <div class="inset-surface p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 rounded-full bg-[#1C6654] text-white flex items-center justify-center text-xs font-bold">3</span>
                        <span class="eyebrow-label text-[#172923]">Sekolah</span>
                    </div>
                    <span class="font-bold text-sm text-[#172923] block">Uji Guru &amp; Santap</span>
                    <p class="text-xs text-[#53645B] mt-1 leading-relaxed">
                        Uji organoleptik oleh PIC dan guru sekolah sebelum pembagian porsi ke ruang kelas.
                    </p>
                </div>
            </div>
        </section>

        <!-- Privacy & Data Minimization Safeguard -->
        <section aria-label="Kebijakan Privasi Data" class="p-5 rounded-2xl bg-[#EDF2ED] border border-[#DCE5DD] text-xs text-[#53645B]">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-[#1C6654] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <div class="space-y-1">
                    <span class="font-bold text-[#172923] block">Komitmen Perlindungan Privasi Data Operasional</span>
                    <p class="leading-relaxed">
                        {{ $data['public_notice'] }} Koordinat GPS kurir real-time dan nomor telepon staf internal dilindungi dan tidak disiarkan ke publik demi keamanan operasional armada.
                    </p>
                </div>
            </div>
        </section>

        <nav aria-label="Navigasi Halaman" class="text-center pt-1">
            <a href="{{ route('login') }}" class="min-h-[44px] px-4 py-2 text-xs font-bold text-[#1C6654] hover:underline inline-flex items-center gap-1.5 rounded-xl">
                <span>← Masuk ke Portal Operasional Staf</span>
            </a>
        </nav>
    </div>
</x-app-shell>
