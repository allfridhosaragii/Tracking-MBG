<x-app-shell title="Portal Transparansi — {{ $school->name }}">
    <div class="max-w-3xl mx-auto py-4 sm:py-8">
        <!-- Public Trust Header -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-6 sm:p-8 floating-shadow mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-5 border-b border-[#EDF2ED]">
                <div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-[#EDF7F2] text-[#21684B] border border-[#BCE2D0] mb-2">
                        Portal Transparansi Publik MBG
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-bold text-[#172923] tracking-tight">{{ $data['name'] }}</h1>
                    <p class="text-xs sm:text-sm text-[#53645B] mt-1">Kode Sekolah: {{ $data['school_code'] }} • Jenjang: {{ $data['education_level'] }}</p>
                </div>

                <div class="text-left sm:text-right bg-[#F5F7F3] p-3 sm:p-4 rounded-xl border border-[#DCE5DD]">
                    <span class="text-xs text-[#53645B] block">Alokasi Sasaran Siswa</span>
                    <span class="text-2xl sm:text-3xl font-bold text-[#1C6654] tabular-nums">{{ number_format($data['beneficiaries_count']) }}</span>
                    <span class="text-xs text-[#53645B] block">porsi / hari layanan</span>
                </div>
            </div>

            <!-- Transparency Details -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-5">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-[#53645B] block mb-1">Mitra Dapur Layanan</span>
                    <span class="text-sm font-semibold text-[#172923] block">{{ $data['sppg_partner'] }}</span>
                    <p class="text-xs text-[#53645B] mt-1">Dapur penyedia pangan higienis terakreditasi standar BGN.</p>
                </div>

                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-[#53645B] block mb-1">Status Penyelenggaraan</span>
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded bg-[#FAF4EB] text-[#885D1C] border border-[#E8D7BE]">
                        <span class="w-2 h-2 rounded-full bg-[#885D1C]"></span>
                        <span>{{ $data['service_status'] }}</span>
                    </span>
                    <p class="text-xs text-[#53645B] mt-1">Jadwal pembagian menu terintegrasi dengan hari efektif belajar.</p>
                </div>
            </div>
        </div>

        <!-- Privacy & Protection Safeguard Notice -->
        <div class="bg-[#EDF2ED] rounded-xl border border-[#DCE5DD] p-4 text-xs text-[#53645B] flex items-start gap-3">
            <svg class="w-5 h-5 text-[#1C6654] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <div>
                <span class="font-bold text-[#172923] block mb-0.5">Komitmen Perlindungan Privasi Data</span>
                {{ $data['public_notice'] }} Koordinat GPS driver real-time dan nomor telepon staf internal dilindungi dan tidak disiarkan ke publik demi keamanan operasional armada.
            </div>
        </div>

        <!-- Back to Login -->
        <div class="text-center mt-6">
            <a href="{{ route('login') }}" class="text-xs text-[#1C6654] hover:underline font-semibold inline-flex items-center gap-1">
                <span>&larr; Masuk ke Portal Operasional Staf</span>
            </a>
        </div>
    </div>
</x-app-shell>
