<x-app-shell title="Tambah Sekolah Binaan : Kepala SPPG">
    <!-- Breadcrumb & Title -->
    <div class="mb-6 pb-4 border-b border-[#DCE5DD]">
        <div class="flex items-center gap-2 mb-2">
            <a href="{{ route('head.schools.index') }}" class="text-xs font-semibold text-[#1C6654] hover:underline inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                <span>Daftar Sekolah</span>
            </a>
            <span class="text-xs text-[#53645B]">•</span>
            <span class="text-xs text-[#53645B]">Formulir PBI-01</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">Registrasi Sekolah Binaan Baru</h1>
        <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">Daftarkan sekolah penerima manfaat ke dalam wilayah pelayanan SPPG Anda</p>
    </div>

    <!-- Main Card Form -->
    <div class="max-w-3xl mx-auto bg-white rounded-2xl border border-[#DCE5DD] p-5 sm:p-8 subtle-shadow">
        <form method="POST" action="{{ route('head.schools.store') }}" class="space-y-6">
            @csrf

            <!-- Section 1: Identitas Pokok Sekolah -->
            <div>
                <h2 class="text-sm font-bold text-[#172923] uppercase tracking-wider mb-4 pb-2 border-b border-[#EDF2ED] flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-[#1C6654] text-white text-[11px] flex items-center justify-center font-bold">1</span>
                    <span>Identitas Pokok Sekolah</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="school_code" class="block text-xs font-bold text-[#172923] mb-1.5">
                            Kode Sekolah / NPSN <span class="text-[#9B3232]">*</span>
                        </label>
                        <input type="text" 
                               name="school_code" 
                               id="school_code" 
                               value="{{ old('school_code') }}" 
                               required 
                               placeholder="Contoh: 20532101"
                               class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border {{ $errors->has('school_code') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] min-h-[44px]">
                        @error('school_code')
                            <p class="text-xs text-[#9B3232] mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                        <p class="text-[11px] text-[#53645B] mt-1">Nomor Pokok Sekolah Nasional atau kode pengenal unik internal.</p>
                    </div>

                    <div>
                        <label for="education_level" class="block text-xs font-bold text-[#172923] mb-1.5">
                            Jenjang Pendidikan <span class="text-[#9B3232]">*</span>
                        </label>
                        <select name="education_level" 
                                id="education_level" 
                                required
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border {{ $errors->has('education_level') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] min-h-[44px]">
                            <option value="">-- Pilih Jenjang --</option>
                            @foreach (['SD', 'SMP', 'SMA', 'SMK', 'MI', 'MTs', 'MA', 'PAUD', 'Lainnya'] as $level)
                                <option value="{{ $level }}" {{ old('education_level') === $level ? 'selected' : '' }}>{{ $level }}</option>
                            @endforeach
                        </select>
                        @error('education_level')
                            <p class="text-xs text-[#9B3232] mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="name" class="block text-xs font-bold text-[#172923] mb-1.5">
                            Nama Resmi Sekolah <span class="text-[#9B3232]">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               value="{{ old('name') }}" 
                               required 
                               placeholder="Contoh: SDN Percobaan 01 Surabaya"
                               class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border {{ $errors->has('name') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] min-h-[44px]">
                        @error('name')
                            <p class="text-xs text-[#9B3232] mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="address" class="block text-xs font-bold text-[#172923] mb-1.5">
                            Alamat Lengkap <span class="text-[#9B3232]">*</span>
                        </label>
                        <textarea name="address" 
                                  id="address" 
                                  rows="3" 
                                  required 
                                  placeholder="Nama jalan, kelurahan, kecamatan, dan petunjuk akses pengantaran logistik..."
                                  class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border {{ $errors->has('address') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654]">{{ old('address') }}</textarea>
                        @error('address')
                            <p class="text-xs text-[#9B3232] mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 2: Koordinat Geografis & Master Data Siswa -->
            <div>
                <h2 class="text-sm font-bold text-[#172923] uppercase tracking-wider mb-4 pb-2 border-b border-[#EDF2ED] flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-[#1C6654] text-white text-[11px] flex items-center justify-center font-bold">2</span>
                    <span>Lokasi GPS & Data Pokok Siswa</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="latitude" class="block text-xs font-bold text-[#172923] mb-1.5">
                            Latitude (Lintang)
                        </label>
                        <input type="number" 
                               step="any" 
                               name="latitude" 
                               id="latitude" 
                               value="{{ old('latitude') }}" 
                               placeholder="-7.2575"
                               class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border {{ $errors->has('latitude') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] min-h-[44px]">
                        @error('latitude')
                            <p class="text-xs text-[#9B3232] mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                        <p class="text-[11px] text-[#53645B] mt-1">Rentang valid: -90.000000 s/d 90.000000.</p>
                    </div>

                    <div>
                        <label for="longitude" class="block text-xs font-bold text-[#172923] mb-1.5">
                            Longitude (Bujur)
                        </label>
                        <input type="number" 
                               step="any" 
                               name="longitude" 
                               id="longitude" 
                               value="{{ old('longitude') }}" 
                               placeholder="112.7521"
                               class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border {{ $errors->has('longitude') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] min-h-[44px]">
                        @error('longitude')
                            <p class="text-xs text-[#9B3232] mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                        <p class="text-[11px] text-[#53645B] mt-1">Rentang valid: -180.000000 s/d 180.000000.</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="registered_beneficiaries" class="block text-xs font-bold text-[#172923] mb-1.5">
                            Jumlah Siswa Penerima Terdaftar (Master Data Pokok)
                        </label>
                        <input type="number" 
                               name="registered_beneficiaries" 
                               id="registered_beneficiaries" 
                               value="{{ old('registered_beneficiaries', 0) }}" 
                               min="0"
                               placeholder="0"
                               class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border {{ $errors->has('registered_beneficiaries') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] min-h-[44px]">
                        @error('registered_beneficiaries')
                            <p class="text-xs text-[#9B3232] mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                        <p class="text-[11px] text-[#53645B] mt-1.5 bg-[#EDF7F2] p-2.5 rounded-lg border border-[#BCE2D0] text-[#1B573E]">
                            <strong>Catatan Arsitektur:</strong> Field ini adalah data pokok (master data) jumlah siswa aktif di sekolah binaan. Kebutuhan porsi riil harian dikonfirmasi terpisah oleh PIC Sekolah setiap hari operasional (PBI-04).
                        </p>
                    </div>
                </div>
            </div>

            <!-- Section 3: Penugasan PIC & Akses Publik -->
            <div>
                <h2 class="text-sm font-bold text-[#172923] uppercase tracking-wider mb-4 pb-2 border-b border-[#EDF2ED] flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-[#1C6654] text-white text-[11px] flex items-center justify-center font-bold">3</span>
                    <span>Penugasan PIC & Portal Publik</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="pic_user_id" class="block text-xs font-bold text-[#172923] mb-1.5">
                            Tugaskan Akun PIC Sekolah
                        </label>
                        <select name="pic_user_id" 
                                id="pic_user_id" 
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border {{ $errors->has('pic_user_id') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] min-h-[44px]">
                            <option value="">-- Belum Ditugaskan (Dapat diatur nanti) --</option>
                            @foreach ($picCandidates as $candidate)
                                <option value="{{ $candidate->id }}" {{ old('pic_user_id') == $candidate->id ? 'selected' : '' }}>
                                    {{ $candidate->name }} ({{ $candidate->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('pic_user_id')
                            <p class="text-xs text-[#9B3232] mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                        <p class="text-[11px] text-[#53645B] mt-1">User terpilih akan otomatis ditetapkan sebagai role PIC Sekolah.</p>
                    </div>

                    <div>
                        <label for="public_slug" class="block text-xs font-bold text-[#172923] mb-1.5">
                            Slug Portal Transparansi Publik (Opsional)
                        </label>
                        <input type="text" 
                               name="public_slug" 
                               id="public_slug" 
                               value="{{ old('public_slug') }}" 
                               placeholder="sdn-percobaan-01-surabaya"
                               class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border {{ $errors->has('public_slug') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] min-h-[44px]">
                        @error('public_slug')
                            <p class="text-xs text-[#9B3232] mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                        <p class="text-[11px] text-[#53645B] mt-1">Kosongkan untuk membuat URL publik secara otomatis dari nama sekolah.</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="pt-4 border-t border-[#DCE5DD] flex items-center justify-between gap-3">
                <a href="{{ route('head.schools.index') }}" 
                   class="px-4 py-2.5 text-xs sm:text-sm font-semibold text-[#53645B] hover:text-[#172923] bg-[#EDF2ED] hover:bg-[#E2EBE2] rounded-xl transition-colors min-h-[44px] flex items-center">
                    Batal
                </a>

                <button type="submit" 
                        class="px-6 py-2.5 text-xs sm:text-sm font-semibold text-white bg-[#1C6654] hover:bg-[#164D42] rounded-xl shadow-xs transition-colors min-h-[44px] cursor-pointer flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Simpan Sekolah Binaan</span>
                </button>
            </div>
        </form>
    </div>
</x-app-shell>
