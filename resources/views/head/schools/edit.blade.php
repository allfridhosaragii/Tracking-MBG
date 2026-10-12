<x-app-shell title="Edit Sekolah Binaan — Kepala SPPG">
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
            <span class="text-xs text-[#53645B] font-mono">{{ $school->school_code }}</span>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">Perbarui Data: {{ $school->name }}</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">Kelola identitas sekolah, koordinat, data siswa, dan penugasan PIC</p>
            </div>
            <div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $school->is_active ? 'bg-[#EDF7F2] text-[#21684B] border border-[#BCE2D0]' : 'bg-[#FDF2F2] text-[#9B3232] border border-[#F5C2C2]' }}">
                    {{ $school->is_active ? 'Status: AKTIF' : 'Status: NONAKTIF' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Main Card Form -->
    <div class="max-w-3xl mx-auto bg-white rounded-2xl border border-[#DCE5DD] p-5 sm:p-8 subtle-shadow">
        <form method="POST" action="{{ route('head.schools.update', $school) }}" class="space-y-6">
            @csrf
            @method('PUT')

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
                               value="{{ old('school_code', $school->school_code) }}" 
                               required 
                               class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border {{ $errors->has('school_code') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] min-h-[44px]">
                        @error('school_code')
                            <p class="text-xs text-[#9B3232] mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="education_level" class="block text-xs font-bold text-[#172923] mb-1.5">
                            Jenjang Pendidikan <span class="text-[#9B3232]">*</span>
                        </label>
                        <select name="education_level" 
                                id="education_level" 
                                required
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border {{ $errors->has('education_level') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] min-h-[44px]">
                            @foreach (['SD', 'SMP', 'SMA', 'SMK', 'MI', 'MTs', 'MA', 'PAUD', 'Lainnya'] as $level)
                                <option value="{{ $level }}" {{ old('education_level', $school->education_level) === $level ? 'selected' : '' }}>{{ $level }}</option>
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
                               value="{{ old('name', $school->name) }}" 
                               required 
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
                                  class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border {{ $errors->has('address') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654]">{{ old('address', $school->address) }}</textarea>
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
                               value="{{ old('latitude', $school->latitude) }}" 
                               class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border {{ $errors->has('latitude') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] min-h-[44px]">
                        @error('latitude')
                            <p class="text-xs text-[#9B3232] mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="longitude" class="block text-xs font-bold text-[#172923] mb-1.5">
                            Longitude (Bujur)
                        </label>
                        <input type="number" 
                               step="any" 
                               name="longitude" 
                               id="longitude" 
                               value="{{ old('longitude', $school->longitude) }}" 
                               class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border {{ $errors->has('longitude') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] min-h-[44px]">
                        @error('longitude')
                            <p class="text-xs text-[#9B3232] mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="registered_beneficiaries" class="block text-xs font-bold text-[#172923] mb-1.5">
                            Jumlah Siswa Penerima Terdaftar (Master Data Pokok)
                        </label>
                        <input type="number" 
                               name="registered_beneficiaries" 
                               id="registered_beneficiaries" 
                               value="{{ old('registered_beneficiaries', $school->registered_beneficiaries) }}" 
                               min="0"
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
                            Penugasan Akun PIC Sekolah
                        </label>
                        <select name="pic_user_id" 
                                id="pic_user_id" 
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border {{ $errors->has('pic_user_id') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] min-h-[44px]">
                            <option value="">-- Tidak Ada PIC / Kosongkan --</option>
                            @foreach ($picCandidates as $candidate)
                                <option value="{{ $candidate->id }}" {{ old('pic_user_id', $currentPic?->id) == $candidate->id ? 'selected' : '' }}>
                                    {{ $candidate->name }} ({{ $candidate->email }}) {{ $currentPic?->id === $candidate->id ? '[PIC Saat Ini]' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('pic_user_id')
                            <p class="text-xs text-[#9B3232] mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                        @if ($currentPic)
                            <p class="text-[11px] text-[#21684B] mt-1 font-medium">Saat ini ditugaskan ke: {{ $currentPic->name }}</p>
                        @endif
                    </div>

                    <div>
                        <label for="public_slug" class="block text-xs font-bold text-[#172923] mb-1.5">
                            Slug Portal Transparansi Publik
                        </label>
                        <input type="text" 
                               name="public_slug" 
                               id="public_slug" 
                               value="{{ old('public_slug', $school->public_slug) }}" 
                               class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border {{ $errors->has('public_slug') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] min-h-[44px]">
                        @error('public_slug')
                            <p class="text-xs text-[#9B3232] mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 4: Status Operasional (Non-Destructive Active Flag) -->
            <div class="bg-[#FAFBF9] rounded-xl border border-[#DCE5DD] p-4">
                <div class="flex items-start gap-3">
                    <div class="flex items-center h-5 mt-0.5">
                        <input type="checkbox" 
                               name="is_active" 
                               id="is_active" 
                               value="1" 
                               {{ old('is_active', $school->is_active) ? 'checked' : '' }}
                               class="w-4 h-4 text-[#1C6654] border-[#DCE5DD] rounded focus:ring-[#1C6654] cursor-pointer">
                    </div>
                    <div>
                        <label for="is_active" class="text-xs sm:text-sm font-bold text-[#172923] cursor-pointer">
                            Sekolah Aktif Dalam Jadwal Distribusi SPPG
                        </label>
                        <p class="text-[11px] text-[#53645B] mt-0.5">
                            Hapus centang untuk menonaktifkan sekolah. Sekolah nonaktif dikecualikan dari menu harian dan jadwal pengantaran, namun seluruh riwayat pengiriman masa lalu tetap tersimpan secara permanen.
                        </p>
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
                    <span>Perbarui Data Sekolah</span>
                </button>
            </div>
        </form>
    </div>
</x-app-shell>
