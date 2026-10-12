<x-app-shell title="Portal Resmi MBG-Track">
    <div class="max-w-4xl mx-auto py-8 sm:py-16 text-center space-y-6">
        <!-- Identity Pill -->
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-[#EDF7F2] text-[#1B573E] border border-[#BCE2D0]">
            <span class="w-2 h-2 rounded-full bg-[#21684B]" aria-hidden="true"></span>
            <span>PROGRAM MAKAN BERGIZI GRATIS (MBG) : BGN</span>
        </div>

        <!-- Hero Headline -->
        <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold text-[#172923] tracking-tight">
            Transparansi &amp; Integritas Operasional MBG
        </h1>
        <p class="text-xs sm:text-base text-[#53645B] max-w-2xl mx-auto leading-relaxed">
            Platform manajemen distribusi terpadu dari dapur SPPG hingga meja makan sekolah binaan. Menjamin kualitas nutrisi, kebersihan ompreng, dan akurasi porsi harian.
        </p>

        <!-- CTA & Actions -->
        <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('login') }}" 
               class="w-full sm:w-auto min-h-[44px] px-6 py-3 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs sm:text-sm font-bold shadow-xs transition-colors inline-flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                <span>Masuk ke Portal Operasional</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>

            @php
                $demoSchool = \App\Models\School::where('is_active', true)->first();
            @endphp
            @if ($demoSchool)
                <a href="{{ route('public.schools.show', $demoSchool->public_slug) }}" 
                   class="w-full sm:w-auto min-h-[44px] px-5 py-3 rounded-xl bg-white hover:bg-[#F5F7F3] text-[#1C6654] text-xs sm:text-sm font-bold border border-[#DCE5DD] transition-colors inline-flex items-center justify-center gap-2">
                    <span>Lihat Portal Transparansi Publik</span>
                    <svg class="w-4 h-4 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
            @endif
        </div>

        <!-- 3 Pillars of Calm Utility System -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-8 text-left">
            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 subtle-shadow">
                <span class="text-xs font-bold text-[#1C6654] uppercase tracking-wider block mb-1">01 • Keamanan Pangan</span>
                <span class="text-sm font-bold text-[#172923] block">Standar QC Gizi Terintegrasi</span>
                <p class="text-xs text-[#53645B] mt-1 leading-relaxed">Pemeriksaan organoleptik dan suhu saji di setiap tahapan dari dapur hingga sekolah.</p>
            </div>

            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 subtle-shadow">
                <span class="text-xs font-bold text-[#1C6654] uppercase tracking-wider block mb-1">02 • Logistik Berinsulasi</span>
                <span class="text-sm font-bold text-[#172923] block">Distribusi Ompreng Sirkular</span>
                <p class="text-xs text-[#53645B] mt-1 leading-relaxed">Pengiriman tepat waktu dengan armada khusus dan pembersihan wadah ramah lingkungan.</p>
            </div>

            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 subtle-shadow">
                <span class="text-xs font-bold text-[#1C6654] uppercase tracking-wider block mb-1">03 • Akuntabilitas Publik</span>
                <span class="text-sm font-bold text-[#172923] block">Transparansi Tanpa Kompromi</span>
                <p class="text-xs text-[#53645B] mt-1 leading-relaxed">Masyarakat dapat memverifikasi komposisi menu harian dengan proteksi privasi staf.</p>
            </div>
        </div>
    </div>
</x-app-shell>
