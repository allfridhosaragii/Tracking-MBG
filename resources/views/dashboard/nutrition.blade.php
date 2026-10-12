<x-app-shell title="Review Desk : Pengawas Gizi">
    <!-- Header Review Desk -->
    <header class="mb-6 pb-4 border-b border-[#DCE5DD]">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <x-role-badge :role="$user->role" />
                    <span class="text-xs font-mono text-[#53645B] bg-white border border-[#DCE5DD] px-2 py-0.5 rounded">
                        SPPG: {{ $sppg->code }}
                    </span>
                    <span class="text-xs text-[#285F80] font-medium bg-[#F0F5FA] border border-[#BCD4E6] px-2 py-0.5 rounded flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#285F80]" aria-hidden="true"></span>
                        <span>Desk Verifikasi Aktif</span>
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">Review Desk : Standar Mutu &amp; Gizi</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">Evaluasi komposisi nutrisi, pemeriksaan organoleptik batch, dan otorisasi keamanan pangan</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('nutrition.menus.index') }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#285F80] hover:bg-[#1E4861] text-white text-xs font-semibold shadow-xs smooth-transition touch-target">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <span>Katalog Menu &amp; Gizi</span>
                </a>
                <span class="px-3 py-2 rounded-xl bg-white border border-[#DCE5DD] text-xs font-medium text-[#172923] subtle-shadow">
                    Total Sasaran: <strong class="tabular-nums font-bold text-[#1C6654]">{{ number_format($totalBeneficiaries) }}</strong> siswa
                </span>
            </div>
        </div>
    </header>

    <!-- Focal Point: QC Verification Desk & Food Safety Gateways -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
        <!-- Panel 1: Primary Queue (Focal Point: Batch Verification Status) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-[#DCE5DD] p-4 sm:p-6 subtle-shadow space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-[#EDF2ED]">
                <div>
                    <h2 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-[#172923]">Antrean Evaluasi Mutu Batch Hari Ini</h2>
                    <p class="text-xs text-[#53645B] mt-0.5">Keputusan pangan aman/tidak aman bersifat mengikat dan tercatat audit</p>
                </div>
                <span class="px-2.5 py-1 text-xs font-semibold rounded-md bg-[#FAF4EB] text-[#885D1C] border border-[#E8D7BE]">
                    Tahap: Persiapan QC
                </span>
            </div>

            <!-- Active Quality Parameters Checklist -->
            <div class="bg-[#FAFBF9] rounded-xl border border-[#DCE5DD] p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-[#172923]">Checklist Mutu Organoleptik BGN</span>
                    <span class="text-[11px] text-[#53645B]">3 Titik Uji Wajib</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs">
                    <div class="bg-white p-3 rounded-lg border border-[#DCE5DD] space-y-1">
                        <span class="text-[10px] uppercase font-bold text-[#285F80] block">Titik 1: Dapur SPPG</span>
                        <p class="font-semibold text-[#172923]">Uji Suhu &amp; Rasa</p>
                        <p class="text-[11px] text-[#53645B]">Suhu minimal >60°C saat pengemasan wadah steril.</p>
                    </div>

                    <div class="bg-white p-3 rounded-lg border border-[#DCE5DD] space-y-1">
                        <span class="text-[10px] uppercase font-bold text-[#885D1C] block">Titik 2: Tiba Sekolah</span>
                        <p class="font-semibold text-[#172923]">Pemeriksaan Fisik</p>
                        <p class="text-[11px] text-[#53645B]">Wadah utuh tanpa kebocoran dan tepat waktu.</p>
                    </div>

                    <div class="bg-white p-3 rounded-lg border border-[#DCE5DD] space-y-1">
                        <span class="text-[10px] uppercase font-bold text-[#21684B] block">Titik 3: Pra-Konsumsi</span>
                        <p class="font-semibold text-[#172923]">Uji Organoleptik PIC</p>
                        <p class="text-[11px] text-[#53645B]">Pemeriksaan kelayakan aroma dan rasa sampel.</p>
                    </div>
                </div>
            </div>

            <!-- Non-Destructive Integrity Rule -->
            <div class="p-3 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-xs text-[#1B573E] flex items-start gap-2.5">
                <svg class="w-4 h-4 text-[#21684B] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <div class="leading-relaxed">
                    <strong>Protokol Keamanan Pangan:</strong> Status <span class="font-bold text-[#9B3232]">UNSAFE</span> langsung mengunci batch pada sistem untuk mencegah pemuatan logistik tanpa otorisasi tertulis Kepala SPPG.
                </div>
            </div>
        </div>

        <!-- Panel 2: Nutrition Standard per Education Level -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-4 sm:p-6 subtle-shadow flex flex-col justify-between">
            <div>
                <div class="pb-3 mb-3 border-b border-[#EDF2ED]">
                    <h2 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-[#172923]">Standar Gizi Binaan</h2>
                    <p class="text-xs text-[#53645B] mt-0.5">Kebutuhan Angka Kecukupan Gizi (AKG)</p>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="p-2.5 rounded-lg bg-[#FAFBF9] border border-[#DCE5DD]">
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-bold text-[#172923]">Jenjang SD (6-12 tahun)</span>
                            <span class="text-[10px] font-semibold text-[#1C6654] bg-[#EDF2ED] px-1.5 py-0.5 rounded">450 - 550 kkal</span>
                        </div>
                        <p class="text-[11px] text-[#53645B]">Fokus: Karbohidrat kompleks, protein hewani, serat sayur, dan vitamin.</p>
                    </div>

                    <div class="p-2.5 rounded-lg bg-[#FAFBF9] border border-[#DCE5DD]">
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-bold text-[#172923]">Jenjang SMP (13-15 tahun)</span>
                            <span class="text-[10px] font-semibold text-[#1C6654] bg-[#EDF2ED] px-1.5 py-0.5 rounded">600 - 700 kkal</span>
                        </div>
                        <p class="text-[11px] text-[#53645B]">Fokus: Makronutrien berimbang untuk percepatan tumbuh kembang remaja.</p>
                    </div>

                    <div class="p-2.5 rounded-lg bg-[#FAFBF9] border border-[#DCE5DD]">
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-bold text-[#172923]">Jenjang SMA (16-18 tahun)</span>
                            <span class="text-[10px] font-semibold text-[#1C6654] bg-[#EDF2ED] px-1.5 py-0.5 rounded">700 - 800 kkal</span>
                        </div>
                        <p class="text-[11px] text-[#53645B]">Fokus: Densitas energi dan zat besi untuk konsentrasi belajar optimal.</p>
                    </div>
                </div>
            </div>

            <div class="pt-3 mt-3 border-t border-[#EDF2ED] text-[11px] text-[#53645B]">
                Seluruh menu diverifikasi sesuai pedoman gizi seimbang nasional.
            </div>
        </div>
    </div>

    <!-- Menu & Nutrition Registry Module (PBI-03 Integration) -->
    <div class="mb-6 bg-white rounded-2xl border border-[#DCE5DD] p-4 sm:p-6 subtle-shadow space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#EDF2ED]">
            <div>
                <h2 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-[#172923]">Katalog Formulasi Menu &amp; Status Gizi</h2>
                <p class="text-xs text-[#53645B] mt-0.5">Daftar resep aktif untuk rencana produksi serta draft yang sedang disusun</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('nutrition.menus.create') }}" 
                   class="px-3 py-1.5 text-xs font-semibold text-[#285F80] hover:text-[#1E4861] bg-[#EDF2ED] hover:bg-[#E2EBE5] rounded-xl smooth-transition touch-target">
                    + Racik Draft Baru
                </a>
                <a href="{{ route('nutrition.menus.index') }}" 
                   class="px-3 py-1.5 text-xs font-semibold text-white bg-[#285F80] hover:bg-[#1E4861] rounded-xl shadow-xs smooth-transition touch-target">
                    Lihat Semua ({{ $activeMenus->count() }} Aktif, {{ $draftMenusCount }} Draft)
                </a>
            </div>
        </div>

        @if ($activeMenus->isEmpty())
            <div class="py-6 text-center text-xs text-[#53645B] bg-[#FAFBF9] rounded-xl border border-[#DCE5DD] p-4">
                <p class="font-medium text-[#172923]">Belum ada menu berstatus aktif di SPPG ini.</p>
                <p class="mt-1">Mulai racik draft menu baru dan lengkapi nilai gizi per komponen untuk mengaktifkan rujukan produksi.</p>
                <div class="mt-3">
                    <a href="{{ route('nutrition.menus.create') }}" class="text-xs font-bold text-[#285F80] hover:underline">
                        Mulai Racik Menu &rarr;
                    </a>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach ($activeMenus as $activeMenu)
                    <div class="p-3.5 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] hover:border-[#285F80] smooth-transition flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-1.5">
                                <span class="font-mono text-[11px] font-bold text-[#285F80] bg-[#EDF2ED] px-2 py-0.5 rounded">
                                    {{ $activeMenu->menu_code }} v{{ $activeMenu->version }}
                                </span>
                                <span class="text-[10px] font-semibold text-[#1B573E] bg-[#EDF7F2] px-2 py-0.5 rounded-full border border-[#BCE2D0]">
                                    Aktif
                                </span>
                            </div>
                            <h3 class="text-xs sm:text-sm font-bold text-[#172923] leading-snug">
                                {{ $activeMenu->name }}
                            </h3>
                            <div class="mt-2 text-xs text-[#53645B] space-y-0.5">
                                <p><strong class="text-[#172923]">{{ number_format($activeMenu->totalEnergy(), 0) }} kkal</strong> / porsi</p>
                                <p class="text-[11px]">P: {{ number_format($activeMenu->totalProtein(), 1) }}g • K: {{ number_format($activeMenu->totalCarbohydrates(), 1) }}g • L: {{ number_format($activeMenu->totalFat(), 1) }}g</p>
                            </div>
                        </div>
                        <div class="pt-3 mt-3 border-t border-[#EDF2ED] flex items-center justify-between">
                            <span class="text-[11px] text-[#53645B]">{{ $activeMenu->items->count() }} komponen</span>
                            <a href="{{ route('nutrition.menus.show', $activeMenu) }}" class="text-xs font-semibold text-[#285F80] hover:underline">
                                Dossier &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- School Distribution Ledger for Nutritionist -->
    <x-card title="Sekolah Binaan Penerima Nutrisi" subtitle="Data pokok siswa penerima manfaat di bawah supervisi gizi SPPG {{ $sppg->code }}">
        <div class="divide-y divide-[#EDF2ED]">
            @foreach ($schools as $school)
                <div class="py-3.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 first:pt-0 last:pb-0">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sm text-[#172923]">{{ $school->name }}</span>
                            <span class="px-2 py-0.5 text-[10px] font-bold bg-[#EDF2ED] text-[#172923] rounded border border-[#DCE5DD]">
                                {{ $school->education_level }}
                            </span>
                        </div>
                        <span class="text-xs text-[#53645B] block mt-0.5">{{ $school->address }}</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <span class="text-[10px] uppercase font-bold text-[#53645B] block">Data Siswa Pokok</span>
                            <span class="text-sm font-bold text-[#172923] tabular-nums">{{ number_format($school->registered_beneficiaries) }} anak</span>
                        </div>
                        <a href="{{ route('schools.show', $school) }}" 
                           class="px-3 py-1.5 text-xs font-semibold text-[#1C6654] bg-[#EDF2ED] hover:bg-[#E2EBE2] border border-[#DCE5DD] rounded-xl smooth-transition touch-target flex items-center gap-1">
                            <span>Detail</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </x-card>
</x-app-shell>
