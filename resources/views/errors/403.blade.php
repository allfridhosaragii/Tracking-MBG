<x-app-shell title="Akses Dibatasi (403)">
    <div class="max-w-xl mx-auto py-8 sm:py-16 text-center">
        <!-- Error Status Badge -->
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-[#FDF2F2] text-[#9B3232] border border-[#F5C2C2] mb-4">
            <span class="w-2 h-2 rounded-full bg-[#9B3232]" aria-hidden="true"></span>
            <span class="font-mono">KODE 403 : AKSES DITOLAK</span>
        </div>

        <h1 class="text-2xl sm:text-3xl font-bold text-[#172923] tracking-tight mb-2">
            Akses Dibatasi oleh Kebijakan Sistem
        </h1>
        <p class="text-xs sm:text-sm text-[#53645B] max-w-md mx-auto leading-relaxed mb-6">
            Anda tidak memiliki izin untuk membuka halaman atau data ini. Akses dibatasi secara ketat berdasarkan wewenang peran kerja dan isolasi unit kerja SPPG / Sekolah.
        </p>

        <!-- Context & Policy Notice -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 sm:p-6 text-left subtle-shadow mb-6 space-y-3">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-[#9B3232] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <div class="text-xs space-y-1">
                    <span class="font-bold text-[#172923] block">Perlindungan Multi-Tenant &amp; Hak Akses</span>
                    <p class="text-[#53645B] leading-relaxed">
                        Data antar-sekolah dan antar-SPPG terisolasi secara kriptografis dan logika bisnis. Jika Anda memerlukan akses ke data ini, hubungi administrator atau Kepala SPPG penanggung jawab.
                    </p>
                </div>
            </div>
        </div>

        <!-- Recovery Action -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('home') }}" 
               class="w-full sm:w-auto min-h-[44px] px-6 py-2.5 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs sm:text-sm font-bold shadow-xs transition-colors inline-flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Kembali ke Halaman Kerja</span>
            </a>

            <a href="{{ route('login') }}" 
               class="w-full sm:w-auto min-h-[44px] px-5 py-2.5 rounded-xl bg-white hover:bg-[#F5F7F3] text-[#53645B] text-xs font-bold border border-[#DCE5DD] transition-colors inline-flex items-center justify-center">
                <span>Ganti Akun Masuk</span>
            </a>
        </div>
    </div>
</x-app-shell>
