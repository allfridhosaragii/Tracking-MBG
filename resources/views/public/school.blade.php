<x-app-shell title="Portal Transparansi : {{ $school->name }}">
    <div class="max-w-4xl mx-auto py-4 sm:py-8 space-y-6">
        <!-- Public Trust Header / Editorial Presentation -->
        <header class="bg-white rounded-2xl border border-[#DCE5DD] p-6 sm:p-8 subtle-shadow">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 pb-6 border-b border-[#EDF2ED]">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#EDF7F2] text-[#1B573E] border border-[#BCE2D0] mb-2.5">
                        <span class="w-2 h-2 rounded-full bg-[#21684B]" aria-hidden="true"></span>
                        <span>Portal Transparansi Publik MBG</span>
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-bold text-[#172923] tracking-tight">{{ $data['name'] }}</h1>
                    <p class="text-xs sm:text-sm text-[#53645B] mt-1">
                        NPSN / Kode: <strong class="font-mono text-[#172923]">{{ $data['school_code'] }}</strong> • Jenjang: <strong>{{ $data['education_level'] }}</strong>
                    </p>
                </div>

                <div class="bg-[#FAFBF9] p-4 sm:p-5 rounded-2xl border border-[#DCE5DD] text-left sm:text-right shrink-0">
                    <span class="text-xs text-[#53645B] block font-medium">Alokasi Sasaran Siswa</span>
                    <div class="flex items-baseline gap-1.5 sm:justify-end mt-0.5">
                        <span class="text-3xl sm:text-4xl font-bold text-[#1C6654] tabular-nums">{{ number_format($data['beneficiaries_count']) }}</span>
                        <span class="text-xs text-[#53645B] font-semibold">porsi</span>
                    </div>
                    <span class="text-[11px] text-[#53645B] block mt-0.5">per hari efektif sekolah</span>
                </div>
            </div>

            <!-- Transparency Overview Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-6">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-[#53645B] block mb-1">Mitra Dapur Pelayanan SPPG</span>
                    <span class="text-sm sm:text-base font-bold text-[#172923] block">{{ $data['sppg_partner'] }}</span>
                    <p class="text-xs text-[#53645B] mt-1 leading-relaxed">
                        Dapur penyedia pangan higienis terakreditasi di bawah pengawasan Badan Gizi Nasional (BGN).
                    </p>
                </div>

                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-[#53645B] block mb-1">Status Penyelenggaraan</span>
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-md bg-[#FAF4EB] text-[#885D1C] border border-[#E8D7BE]">
                        <span class="w-2 h-2 rounded-full bg-[#885D1C]" aria-hidden="true"></span>
                        <span>{{ $data['service_status'] }}</span>
                    </span>
                    <p class="text-xs text-[#53645B] mt-1 leading-relaxed">
                        Layanan makan bergizi beroperasi sesuai kalender akademik dan konfirmasi harian pihak sekolah.
                    </p>
                </div>
            </div>
        </header>

        <!-- Editorial Food Transparency & Standards -->
        <section aria-labelledby="nutrition-standards-heading" class="bg-white rounded-2xl border border-[#DCE5DD] p-6 sm:p-8 subtle-shadow">
            <h2 id="nutrition-standards-heading" class="text-base sm:text-lg font-bold text-[#172923] mb-1">
                Komposisi & Standar Kualitas Makanan
            </h2>
            <p class="text-xs sm:text-sm text-[#53645B] mb-5">
                Setiap menu harian dirancang oleh Tenaga Gizi Terdaftar untuk memenuhi Angka Kecukupan Gizi (AKG) siswa.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-4 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD]">
                    <span class="text-xs font-bold text-[#1C6654] uppercase tracking-wider block mb-1">Karbohidrat</span>
                    <span class="text-sm font-bold text-[#172923] block">Nasi Pulen / Umbi Lokal</span>
                    <p class="text-xs text-[#53645B] mt-1 leading-relaxed">Sumber energi utama untuk konsentrasi belajar di kelas.</p>
                </div>

                <div class="p-4 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD]">
                    <span class="text-xs font-bold text-[#1C6654] uppercase tracking-wider block mb-1">Protein Berkualitas</span>
                    <span class="text-sm font-bold text-[#172923] block">Ayam, Ikan, Telur & Tahu</span>
                    <p class="text-xs text-[#53645B] mt-1 leading-relaxed">Kombinasi protein hewani dan nabati untuk tumbuh kembang optimal.</p>
                </div>

                <div class="p-4 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD]">
                    <span class="text-xs font-bold text-[#1C6654] uppercase tracking-wider block mb-1">Sayur & Serat</span>
                    <span class="text-sm font-bold text-[#172923] block">Sayur Segar Bening/Tumis</span>
                    <p class="text-xs text-[#53645B] mt-1 leading-relaxed">Kaya mikronutrien, vitamin, dan serat alami dari petani mitra.</p>
                </div>

                <div class="p-4 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD]">
                    <span class="text-xs font-bold text-[#1C6654] uppercase tracking-wider block mb-1">Buah Segar Pilihan</span>
                    <span class="text-sm font-bold text-[#172923] block">Pisang, Pepaya, Jeruk</span>
                    <p class="text-xs text-[#53645B] mt-1 leading-relaxed">Pelengkap vitamin C segar disajikan utuh higienis.</p>
                </div>
            </div>
        </section>

        <!-- Public Delivery Flow Timeline -->
        <section aria-labelledby="flow-timeline-heading" class="bg-white rounded-2xl border border-[#DCE5DD] p-6 sm:p-8 subtle-shadow">
            <h2 id="flow-timeline-heading" class="text-base sm:text-lg font-bold text-[#172923] mb-1">
                Alur Penjaminan Mutu & Distribusi Harian
            </h2>
            <p class="text-xs sm:text-sm text-[#53645B] mb-5">
                Protokol berjenjang dari dapur SPPG hingga meja makan peserta didik di sekolah.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-4 rounded-xl border border-[#DCE5DD] bg-[#FAFBF9]">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 rounded-full bg-[#1C6654] text-white flex items-center justify-center text-xs font-bold">1</span>
                        <span class="font-bold text-xs text-[#172923] uppercase tracking-wider">Dapur SPPG</span>
                    </div>
                    <span class="font-bold text-sm text-[#172923] block">QC Mutu & Pengemasan</span>
                    <p class="text-xs text-[#53645B] mt-1 leading-relaxed">
                        Pemeriksaan organoleptik oleh pengawas gizi, penyegelan ompreng food-grade, dan insulasi termal &gt;60°C.
                    </p>
                </div>

                <div class="p-4 rounded-xl border border-[#DCE5DD] bg-[#FAFBF9]">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 rounded-full bg-[#1C6654] text-white flex items-center justify-center text-xs font-bold">2</span>
                        <span class="font-bold text-xs text-[#172923] uppercase tracking-wider">Perjalanan Kurir</span>
                    </div>
                    <span class="font-bold text-sm text-[#172923] block">Distribusi Terjadwal</span>
                    <p class="text-xs text-[#53645B] mt-1 leading-relaxed">
                        Armada khusus membawa ompreng bersegel langsung menuju titik serah sekolah tanpa perantara.
                    </p>
                </div>

                <div class="p-4 rounded-xl border border-[#DCE5DD] bg-[#FAFBF9]">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 rounded-full bg-[#1C6654] text-white flex items-center justify-center text-xs font-bold">3</span>
                        <span class="font-bold text-xs text-[#172923] uppercase tracking-wider">Sekolah</span>
                    </div>
                    <span class="font-bold text-sm text-[#172923] block">Uji Guru & Santap</span>
                    <p class="text-xs text-[#53645B] mt-1 leading-relaxed">
                        Uji organoleptik oleh tim 3 guru sekolah sebelum pembagian porsi ke ruang kelas masing-masing.
                    </p>
                </div>
            </div>
        </section>

        <!-- Privacy & Protection Safeguard Notice (Explicit Trust Contract) -->
        <section aria-label="Kebijakan Privasi Data" class="bg-[#EDF2ED] rounded-2xl border border-[#DCE5DD] p-5 text-xs text-[#53645B]">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-[#1C6654] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <div class="space-y-1">
                    <span class="font-bold text-[#172923] block">Komitmen Perlindungan Privasi Data Operasional</span>
                    <p class="leading-relaxed">
                        {{ $data['public_notice'] }} Koordinat GPS driver real-time dan nomor telepon staf internal dilindungi dan tidak disiarkan ke publik demi keamanan operasional armada.
                    </p>
                </div>
            </div>
        </section>

        <!-- Navigation Footer -->
        <nav aria-label="Navigasi Halaman" class="text-center pt-2">
            <a href="{{ route('login') }}" class="min-h-[44px] px-4 py-2 text-xs font-bold text-[#1C6654] hover:underline inline-flex items-center gap-1.5 focus:outline-none focus:ring-2 focus:ring-[#1C6654] rounded-lg">
                <span>&larr; Masuk ke Portal Operasional Staf</span>
            </a>
        </nav>
    </div>
</x-app-shell>
