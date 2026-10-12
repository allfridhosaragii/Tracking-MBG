<x-app-shell title="Racik Draft Menu Baru : MBG-Track">
    <div class="max-w-3xl mx-auto space-y-6">
        <!-- Breadcrumb & Header -->
        <div class="pb-4 border-b border-[#DCE5DD]">
            <div class="flex items-center gap-2 text-xs font-medium text-[#53645B] mb-1">
                <a href="{{ route('nutrition.dashboard') }}" class="hover:text-[#172923] smooth-transition">Desk Pengawas Gizi</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('nutrition.menus.index') }}" class="hover:text-[#172923] smooth-transition">Katalog Menu</a>
                <span aria-hidden="true">/</span>
                <span class="text-[#285F80] font-semibold">Racik Draft Baru</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-[#172923]">
                Inisiasi Draft Formulasi Menu Makanan
            </h1>
            <p class="text-xs sm:text-sm text-[#53645B] mt-1 leading-relaxed">
                Tentukan kode identitas dan nama racikan menu. Setelah draft tersimpan, Anda dapat melengkapi rincian komponen bahan, gramasi porsi, dan kandungan gizi terverifikasi.
            </p>
        </div>

        <!-- Form Card -->
        <div class="bg-white border border-[#DCE5DD] rounded-xl shadow-2xs p-5 sm:p-6">
            <form method="POST" action="{{ route('nutrition.menus.store') }}" class="space-y-5">
                @csrf

                <!-- Kode Menu & Versi Awal -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2 space-y-1.5">
                        <label for="menu_code" class="block text-xs sm:text-sm font-semibold text-[#172923]">
                            Kode Menu <span class="text-[#9B3232]">*</span>
                        </label>
                        <input type="text" 
                               id="menu_code" 
                               name="menu_code" 
                               value="{{ old('menu_code') }}" 
                               required 
                               placeholder="Contoh: MN-AYAM-01" 
                               class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white border @error('menu_code') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl text-[#172923] placeholder-[#708177] focus:outline-none focus:ring-2 focus:ring-[#285F80] font-mono smooth-transition touch-target">
                        <p class="text-[11px] text-[#53645B]">
                            Gunakan kode unik per menu. Jika menu yang sama pernah ada, sistem akan otomatis mengaitkannya sebagai versi lanjutan.
                        </p>
                        @error('menu_code')
                            <p class="text-xs text-[#9B3232] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs sm:text-sm font-semibold text-[#53645B]">
                            Versi Inisial
                        </label>
                        <div class="px-3.5 py-2.5 text-xs sm:text-sm bg-[#F5F7F3] border border-[#DCE5DD] rounded-xl text-[#53645B] font-mono font-bold flex items-center justify-between">
                            <span>Versi 1 (v1)</span>
                            <span class="text-[10px] uppercase font-sans font-semibold px-2 py-0.5 rounded bg-[#FEF6EE] text-[#B54708] border border-[#F9DBAF]">Draft</span>
                        </div>
                        <p class="text-[11px] text-[#53645B]">Otomatis diatur sistem</p>
                    </div>
                </div>

                <!-- Nama Racikan Menu -->
                <div class="space-y-1.5">
                    <label for="name" class="block text-xs sm:text-sm font-semibold text-[#172923]">
                        Nama Racikan Menu <span class="text-[#9B3232]">*</span>
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name') }}" 
                           required 
                           placeholder="Contoh: Paket Nasi Pulen Ayam Semur, Sup Sayur Bening & Pisang Barangan" 
                           class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white border @error('name') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl text-[#172923] placeholder-[#708177] focus:outline-none focus:ring-2 focus:ring-[#285F80] smooth-transition touch-target">
                    @error('name')
                        <p class="text-xs text-[#9B3232] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tanggal Mulai Berlaku & Foto/Dokumentasi -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label for="effective_from" class="block text-xs sm:text-sm font-semibold text-[#172923]">
                            Tanggal Rencana Mulai Berlaku
                        </label>
                        <input type="date" 
                               id="effective_from" 
                               name="effective_from" 
                               value="{{ old('effective_from') }}" 
                               class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white border @error('effective_from') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#285F80] smooth-transition touch-target">
                        <p class="text-[11px] text-[#53645B]">Opsional: tanggal target menu ini mulai disajikan di sekolah.</p>
                        @error('effective_from')
                            <p class="text-xs text-[#9B3232] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label for="image_path" class="block text-xs sm:text-sm font-semibold text-[#172923]">
                            Tautan Foto Sampel Menu (Opsional)
                        </label>
                        <input type="text" 
                               id="image_path" 
                               name="image_path" 
                               value="{{ old('image_path') }}" 
                               placeholder="Contoh: /storage/menus/sampel-ayam-semur.jpg" 
                               class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white border @error('image_path') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl text-[#172923] placeholder-[#708177] focus:outline-none focus:ring-2 focus:ring-[#285F80] smooth-transition touch-target">
                        <p class="text-[11px] text-[#53645B]">Dokumentasi visual porsi saji standar SPPG.</p>
                        @error('image_path')
                            <p class="text-xs text-[#9B3232] mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Guidance Box -->
                <div class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-xs text-[#1B573E] space-y-2">
                    <div class="font-bold flex items-center gap-2 text-sm text-[#1B573E]">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Prinsip Akurasi Nutrisi Program MBG</span>
                    </div>
                    <ul class="list-disc list-inside space-y-1 leading-relaxed text-[11px]">
                        <li>Setiap menu yang sah harus memenuhi angka kecukupan gizi (AKG) makan siang siswa sekolah sasaran.</li>
                        <li>Nilai nutrisi yang tidak diketahui <strong>tidak boleh direka atau diisi angka sembarang</strong>.</li>
                        <li>Aktivasi menu hanya diizinkan apabila seluruh komponen makanan memiliki gramasi terukur dan sumber rujukan gizi resmi.</li>
                    </ul>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#DCE5DD]">
                    <a href="{{ route('nutrition.menus.index') }}" 
                       class="px-4 py-2.5 text-xs sm:text-sm font-semibold text-[#53645B] hover:text-[#172923] bg-[#F5F7F3] hover:bg-[#EDF2ED] rounded-xl smooth-transition touch-target">
                        Batal
                    </a>
                    <button type="submit" 
                            class="px-5 py-2.5 text-xs sm:text-sm font-semibold text-white bg-[#285F80] hover:bg-[#1E4861] rounded-xl shadow-xs smooth-transition touch-target focus:outline-none focus:ring-2 focus:ring-[#285F80] focus:ring-offset-2">
                        Simpan Draft &amp; Lanjut Komposisi
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-shell>
