<x-app-shell title="Racik Draft Menu Baru">
    <div class="max-w-3xl mx-auto space-y-5">
        <header class="pb-4 border-b border-[#DCE5DD]">
            <a href="{{ route('nutrition.menus.index') }}" class="text-xs font-semibold text-[#285F80] hover:underline inline-flex items-center gap-1 mb-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>Kembali ke Katalog Menu</span>
            </a>
            <h1 class="page-title">Inisiasi Draft Formulasi Menu</h1>
            <p class="text-xs sm:text-sm text-[#53645B] mt-1">Tentukan kode dan nama racikan menu. Komposisi bahan dan nilai gizi ditambahkan pada langkah berikutnya.</p>
        </header>

        <div class="luxury-surface p-5 sm:p-7">
            <form method="POST" action="{{ route('nutrition.menus.store') }}" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label for="menu_code" class="block text-xs font-semibold text-[#172923] mb-1.5">
                            Kode Menu <span class="text-[#9B3232]">*</span>
                        </label>
                        <input type="text"
                               id="menu_code"
                               name="menu_code"
                               value="{{ old('menu_code') }}"
                               required
                               placeholder="Contoh: MN-AYAM-01"
                               class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm bg-[#FAFBF9] focus:bg-white border @error('menu_code') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl text-[#172923] font-mono">
                        @error('menu_code')
                            <p class="text-xs text-[#9B3232] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <span class="block text-xs font-semibold text-[#53645B] mb-1.5">Versi Awal</span>
                        <div class="min-h-[44px] px-3.5 py-2 text-xs bg-[#FAFBF9] border border-[#DCE5DD] rounded-xl text-[#172923] font-mono font-bold flex items-center justify-between">
                            <span>v1</span>
                            <span class="text-[10px] font-sans font-semibold px-2 py-0.5 rounded-md bg-[#FEF7EC] text-[#885D1C] border border-[#F7D7A3]">Draft</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="name" class="block text-xs font-semibold text-[#172923] mb-1.5">
                        Nama Racikan Menu <span class="text-[#9B3232]">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                           required
                           placeholder="Contoh: Nasi Putih Pulen, Ayam Semur, Sayur Bening & Pisang"
                           class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm bg-[#FAFBF9] focus:bg-white border @error('name') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl text-[#172923]">
                    @error('name')
                        <p class="text-xs text-[#9B3232] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="effective_from" class="block text-xs font-semibold text-[#172923] mb-1.5">Tanggal Mulai Berlaku (Opsional)</label>
                        <input type="date"
                               id="effective_from"
                               name="effective_from"
                               value="{{ old('effective_from') }}"
                               class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl text-[#172923]">
                    </div>

                    <div>
                        <label for="image_path" class="block text-xs font-semibold text-[#172923] mb-1.5">Tautan Foto Sampel (Opsional)</label>
                        <input type="text"
                               id="image_path"
                               name="image_path"
                               value="{{ old('image_path') }}"
                               placeholder="/storage/menus/sampel.jpg"
                               class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl text-[#172923]">
                    </div>
                </div>

                <details class="sop-disclosure">
                    <summary>
                        <span>Prinsip Kelengkapan Nutrisi Menu MBG</span>
                        <svg class="w-4 h-4 text-[#53645B] disclosure-chevron shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <div class="p-4 text-xs text-[#53645B] space-y-1.5 leading-relaxed">
                        <p>Aktivasi menu hanya diizinkan apabila seluruh komponen makanan memiliki gramasi terukur, nilai makronutrien lengkap, dan rujukan sumber gizi resmi (misalnya TKPI).</p>
                    </div>
                </details>

                <div class="pt-4 border-t border-[#E7ECE8] flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-3">
                    <a href="{{ route('nutrition.menus.index') }}"
                       class="min-h-[44px] px-4 py-2.5 text-xs sm:text-sm font-semibold text-[#53645B] bg-[#F5F7F3] hover:bg-[#EDF2ED] rounded-xl inline-flex items-center justify-center">
                        Batal
                    </a>
                    <button type="submit"
                            class="min-h-[44px] px-5 py-2.5 text-xs sm:text-sm font-semibold text-white bg-[#285F80] hover:bg-[#1E4861] rounded-xl inline-flex items-center justify-center cursor-pointer smooth-transition">
                        Simpan Draft &amp; Lanjut Komposisi
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-shell>
