<x-app-shell title="Sunting Menu {{ $menu->menu_code }}">
    <div class="max-w-3xl mx-auto space-y-5">
        <header class="pb-4 border-b border-[#DCE5DD]">
            <a href="{{ route('nutrition.menus.show', $menu) }}" class="text-xs font-semibold text-[#285F80] hover:underline inline-flex items-center gap-1 mb-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>Kembali ke Dossier Menu</span>
            </a>
            <h1 class="page-title">Sunting Informasi Draft Menu</h1>
            <p class="text-xs sm:text-sm text-[#53645B] mt-1 font-mono">{{ $menu->menu_code }} • Versi {{ $menu->version }}</p>
        </header>

        <div class="luxury-surface p-5 sm:p-7">
            <form method="POST" action="{{ route('nutrition.menus.update', $menu) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="block text-xs font-semibold text-[#172923] mb-1.5">
                        Nama Racikan Menu <span class="text-[#9B3232]">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name', $menu->name) }}"
                           required
                           class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm bg-[#FAFBF9] focus:bg-white border @error('name') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl text-[#172923]">
                    @error('name')
                        <p class="text-xs text-[#9B3232] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="effective_from" class="block text-xs font-semibold text-[#172923] mb-1.5">Tanggal Mulai Berlaku</label>
                        <input type="date"
                               id="effective_from"
                               name="effective_from"
                               value="{{ old('effective_from', $menu->effective_from?->format('Y-m-d')) }}"
                               class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl text-[#172923]">
                    </div>

                    <div>
                        <label for="image_path" class="block text-xs font-semibold text-[#172923] mb-1.5">Tautan Foto Sampel (Opsional)</label>
                        <input type="text"
                               id="image_path"
                               name="image_path"
                               value="{{ old('image_path', $menu->image_path) }}"
                               class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl text-[#172923]">
                    </div>
                </div>

                <div class="pt-4 border-t border-[#E7ECE8] flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-3">
                    <a href="{{ route('nutrition.menus.show', $menu) }}"
                       class="min-h-[44px] px-4 py-2.5 text-xs sm:text-sm font-semibold text-[#53645B] bg-[#F5F7F3] rounded-xl inline-flex items-center justify-center">
                        Batal
                    </a>
                    <button type="submit"
                            class="min-h-[44px] px-5 py-2.5 text-xs sm:text-sm font-semibold text-white bg-[#285F80] hover:bg-[#1E4861] rounded-xl inline-flex items-center justify-center cursor-pointer smooth-transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-shell>
