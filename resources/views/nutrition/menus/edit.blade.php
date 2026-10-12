<x-app-shell title="Sunting Draft Menu {{ $menu->menu_code }} : MBG-Track">
    <div class="max-w-3xl mx-auto space-y-6">
        <!-- Breadcrumb & Header -->
        <div class="pb-4 border-b border-[#DCE5DD]">
            <div class="flex items-center gap-2 text-xs font-medium text-[#53645B] mb-1">
                <a href="{{ route('nutrition.dashboard') }}" class="hover:text-[#172923] smooth-transition">Desk Pengawas Gizi</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('nutrition.menus.index') }}" class="hover:text-[#172923] smooth-transition">Katalog Menu</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('nutrition.menus.show', $menu) }}" class="font-mono hover:text-[#172923] smooth-transition">{{ $menu->menu_code }}</a>
                <span aria-hidden="true">/</span>
                <span class="text-[#285F80] font-semibold">Sunting Metadata</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-[#172923]">
                Sunting Informasi Draft Menu
            </h1>
            <p class="text-xs sm:text-sm text-[#53645B] mt-1 leading-relaxed">
                Perbarui nama racikan atau tanggal mulai berlaku pada draft versi {{ $menu->version }}. Kode menu terkunci untuk menjaga konsistensi silsilah versi.
            </p>
        </div>

        <!-- Form Card -->
        <div class="bg-white border border-[#DCE5DD] rounded-xl shadow-2xs p-5 sm:p-6">
            <form method="POST" action="{{ route('nutrition.menus.update', $menu) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <!-- Kode Menu & Versi (Readonly) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs sm:text-sm font-semibold text-[#53645B]">
                            Kode Menu (Terkunci)
                        </label>
                        <div class="px-3.5 py-2.5 text-xs sm:text-sm bg-[#F5F7F3] border border-[#DCE5DD] rounded-xl text-[#172923] font-mono font-bold">
                            {{ $menu->menu_code }}
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs sm:text-sm font-semibold text-[#53645B]">
                            Versi &amp; Status
                        </label>
                        <div class="px-3.5 py-2.5 text-xs sm:text-sm bg-[#F5F7F3] border border-[#DCE5DD] rounded-xl text-[#172923] flex items-center justify-between">
                            <span class="font-mono font-bold">Versi {{ $menu->version }}</span>
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded {{ $menu->status->badgeClasses() }} border">
                                {{ $menu->status->label() }}
                            </span>
                        </div>
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
                           value="{{ old('name', $menu->name) }}" 
                           required 
                           class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white border @error('name') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#285F80] smooth-transition touch-target">
                    @error('name')
                        <p class="text-xs text-[#9B3232] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tanggal Mulai Berlaku & Foto Sampel -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label for="effective_from" class="block text-xs sm:text-sm font-semibold text-[#172923]">
                            Tanggal Rencana Mulai Berlaku
                        </label>
                        <input type="date" 
                               id="effective_from" 
                               name="effective_from" 
                               value="{{ old('effective_from', $menu->effective_from?->format('Y-m-d')) }}" 
                               class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white border @error('effective_from') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#285F80] smooth-transition touch-target">
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
                               value="{{ old('image_path', $menu->image_path) }}" 
                               placeholder="Contoh: /storage/menus/sampel.jpg" 
                               class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white border @error('image_path') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#285F80] smooth-transition touch-target">
                        @error('image_path')
                            <p class="text-xs text-[#9B3232] mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#DCE5DD]">
                    <a href="{{ route('nutrition.menus.show', $menu) }}" 
                       class="px-4 py-2.5 text-xs sm:text-sm font-semibold text-[#53645B] hover:text-[#172923] bg-[#F5F7F3] hover:bg-[#EDF2ED] rounded-xl smooth-transition touch-target">
                        Batal
                    </a>
                    <button type="submit" 
                            class="px-5 py-2.5 text-xs sm:text-sm font-semibold text-white bg-[#285F80] hover:bg-[#1E4861] rounded-xl shadow-xs smooth-transition touch-target focus:outline-none focus:ring-2 focus:ring-[#285F80] focus:ring-offset-2">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-shell>
