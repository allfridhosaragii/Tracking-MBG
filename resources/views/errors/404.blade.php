<x-app-shell title="Halaman Tidak Ditemukan (404)">
    <div class="max-w-xl mx-auto py-8 sm:py-16 text-center">
        <!-- Error Status Badge -->
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-[#FAF4EB] text-[#885D1C] border border-[#E8D7BE] mb-4">
            <span class="w-2 h-2 rounded-full bg-[#885D1C]" aria-hidden="true"></span>
            <span class="font-mono">KODE 404 : TIDAK DITEMUKAN</span>
        </div>

        <h1 class="text-2xl sm:text-3xl font-bold text-[#172923] tracking-tight mb-2">
            Halaman atau Data Tidak Ditemukan
        </h1>
        <p class="text-xs sm:text-sm text-[#53645B] max-w-md mx-auto leading-relaxed mb-6">
            Alamat URL yang Anda tuju tidak terdaftar, telah dipindahkan, atau status sekolah tidak aktif di lingkungan operasional MBG-Track.
        </p>

        <!-- Recovery Action -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('home') }}" 
               class="w-full sm:w-auto min-h-[44px] px-6 py-2.5 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs sm:text-sm font-bold shadow-xs transition-colors inline-flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Kembali ke Beranda</span>
            </a>

            <a href="{{ route('login') }}" 
               class="w-full sm:w-auto min-h-[44px] px-5 py-2.5 rounded-xl bg-white hover:bg-[#F5F7F3] text-[#53645B] text-xs font-bold border border-[#DCE5DD] transition-colors inline-flex items-center justify-center">
                <span>Halaman Masuk</span>
            </a>
        </div>
    </div>
</x-app-shell>
