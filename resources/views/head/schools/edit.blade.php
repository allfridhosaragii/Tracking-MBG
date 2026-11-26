<x-app-shell title="Perbarui Sekolah : {{ $school->name }}">
    <div class="max-w-3xl mx-auto space-y-5">
        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 pb-4 border-b border-[#DCE5DD]">
            <div>
                <a href="{{ route('head.schools.show', $school) }}" class="text-xs font-semibold text-[#1C6654] hover:underline inline-flex items-center gap-1 mb-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span>Kembali ke Detail Sekolah</span>
                </a>
                <h1 class="page-title">Perbarui Data Sekolah</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">{{ $school->name }} • <span class="font-mono">{{ $school->school_code }}</span></p>
            </div>

            <span class="self-start sm:self-auto px-2.5 py-1 rounded-lg text-xs font-semibold border {{ $school->is_active ? 'bg-[#EDF7F2] text-[#21684B] border-[#BCE2D0]' : 'bg-[#FDF2F2] text-[#9B3232] border-[#F5C2C2]' }}">
                {{ $school->is_active ? 'Status Aktif' : 'Status Nonaktif' }}
            </span>
        </header>

        <div class="luxury-surface p-5 sm:p-7">
            <form method="POST" action="{{ route('head.schools.update', $school) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <h2 class="section-title pb-2 border-b border-[#E7ECE8]">1. Identitas Sekolah</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="school_code" class="block text-xs font-semibold text-[#172923] mb-1.5">
                                Kode Sekolah / NPSN <span class="text-[#9B3232]">*</span>
                            </label>
                            <input type="text"
                                   name="school_code"
                                   id="school_code"
                                   value="{{ old('school_code', $school->school_code) }}"
                                   required
                                   class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm rounded-xl border {{ $errors->has('school_code') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white">
                            @error('school_code')
                                <p class="text-xs text-[#9B3232] mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="education_level" class="block text-xs font-semibold text-[#172923] mb-1.5">
                                Jenjang Pendidikan <span class="text-[#9B3232]">*</span>
                            </label>
                            <select name="education_level"
                                    id="education_level"
                                    required
                                    class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm rounded-xl border {{ $errors->has('education_level') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white">
                                @foreach (['SD', 'SMP', 'SMA', 'SMK', 'MI', 'MTs', 'MA', 'PAUD', 'Lainnya'] as $level)
                                    <option value="{{ $level }}" {{ old('education_level', $school->education_level) === $level ? 'selected' : '' }}>{{ $level }}</option>
                                @endforeach
                            </select>
                            @error('education_level')
                                <p class="text-xs text-[#9B3232] mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="name" class="block text-xs font-semibold text-[#172923] mb-1.5">
                                Nama Resmi Sekolah <span class="text-[#9B3232]">*</span>
                            </label>
                            <input type="text"
                                   name="name"
                                   id="name"
                                   value="{{ old('name', $school->name) }}"
                                   required
                                   class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm rounded-xl border {{ $errors->has('name') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white">
                            @error('name')
                                <p class="text-xs text-[#9B3232] mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="address" class="block text-xs font-semibold text-[#172923] mb-1.5">
                                Alamat Lengkap <span class="text-[#9B3232]">*</span>
                            </label>
                            <textarea name="address"
                                      id="address"
                                      rows="2"
                                      required
                                      class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border {{ $errors->has('address') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white">{{ old('address', $school->address) }}</textarea>
                            @error('address')
                                <p class="text-xs text-[#9B3232] mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h2 class="section-title pb-2 border-b border-[#E7ECE8]">2. Data Siswa &amp; Koordinat</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label for="registered_beneficiaries" class="block text-xs font-semibold text-[#172923] mb-1.5">
                                Jumlah Siswa Penerima Terdaftar
                            </label>
                            <input type="number"
                                   name="registered_beneficiaries"
                                   id="registered_beneficiaries"
                                   value="{{ old('registered_beneficiaries', $school->registered_beneficiaries) }}"
                                   min="0"
                                   class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm rounded-xl border {{ $errors->has('registered_beneficiaries') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white tabular-nums">
                            @error('registered_beneficiaries')
                                <p class="text-xs text-[#9B3232] mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="latitude" class="block text-xs font-semibold text-[#172923] mb-1.5">Latitude</label>
                            <input type="number"
                                   step="any"
                                   name="latitude"
                                   id="latitude"
                                   value="{{ old('latitude', $school->latitude) }}"
                                   class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm rounded-xl border {{ $errors->has('latitude') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white font-mono">
                            @error('latitude')
                                <p class="text-xs text-[#9B3232] mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="longitude" class="block text-xs font-semibold text-[#172923] mb-1.5">Longitude</label>
                            <input type="number"
                                   step="any"
                                   name="longitude"
                                   id="longitude"
                                   value="{{ old('longitude', $school->longitude) }}"
                                   class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm rounded-xl border {{ $errors->has('longitude') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white font-mono">
                            @error('longitude')
                                <p class="text-xs text-[#9B3232] mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h2 class="section-title pb-2 border-b border-[#E7ECE8]">3. Penugasan PIC &amp; Status Aktif</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="pic_user_id" class="block text-xs font-semibold text-[#172923] mb-1.5">Akun PIC Sekolah</label>
                            <select name="pic_user_id"
                                    id="pic_user_id"
                                    class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm rounded-xl border {{ $errors->has('pic_user_id') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white">
                                <option value="">Kosongkan PIC</option>
                                @foreach ($picCandidates as $candidate)
                                    <option value="{{ $candidate->id }}" {{ old('pic_user_id', $currentPic?->id) == $candidate->id ? 'selected' : '' }}>
                                        {{ $candidate->name }} ({{ $candidate->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('pic_user_id')
                                <p class="text-xs text-[#9B3232] mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="public_slug" class="block text-xs font-semibold text-[#172923] mb-1.5">Slug Portal Publik</label>
                            <input type="text"
                                   name="public_slug"
                                   id="public_slug"
                                   value="{{ old('public_slug', $school->public_slug) }}"
                                   class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm rounded-xl border {{ $errors->has('public_slug') ? 'border-[#9B3232] bg-[#FDF2F2]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} focus:bg-white font-mono">
                            @error('public_slug')
                                <p class="text-xs text-[#9B3232] mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <label class="inset-surface p-3.5 flex items-start gap-3 cursor-pointer">
                        <input type="checkbox"
                               name="is_active"
                               id="is_active"
                               value="1"
                               {{ old('is_active', $school->is_active) ? 'checked' : '' }}
                               class="w-4 h-4 mt-0.5 text-[#1C6654] border-[#DCE5DD] rounded cursor-pointer">
                        <span class="text-xs leading-relaxed">
                            <strong class="text-[#172923] block">Sekolah Aktif dalam Jadwal Distribusi</strong>
                            <span class="text-[#53645B]">Hapus centang untuk menonaktifkan sementara tanpa menghapus riwayat pengiriman sebelumnya.</span>
                        </span>
                    </label>
                </div>

                <div class="pt-4 border-t border-[#E7ECE8] flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-3">
                    <a href="{{ route('head.schools.show', $school) }}"
                       class="min-h-[44px] px-4 py-2.5 text-xs sm:text-sm font-semibold text-[#53645B] hover:text-[#172923] bg-[#F5F7F3] hover:bg-[#EDF2ED] rounded-xl inline-flex items-center justify-center smooth-transition">
                        Batal
                    </a>
                    <button type="submit"
                            class="min-h-[44px] px-6 py-2.5 text-xs sm:text-sm font-semibold text-white bg-[#1C6654] hover:bg-[#164D42] rounded-xl inline-flex items-center justify-center cursor-pointer smooth-transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-shell>
