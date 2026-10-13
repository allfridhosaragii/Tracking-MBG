<x-app-shell title="Konfirmasi Kebutuhan Porsi : {{ $school->name }} : MBG-Track">
    <div class="space-y-6">
        <!-- Header & Breadcrumb -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <div class="flex items-center gap-2 text-xs font-medium text-[#53645B] mb-1">
                    <a href="{{ route('school.dashboard') }}" class="hover:text-[#172923] smooth-transition">Penerimaan Sekolah</a>
                    <span aria-hidden="true">/</span>
                    <span class="text-[#21684B] font-semibold">Kebutuhan Porsi (P02)</span>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-[#172923]">
                        Konfirmasi Kebutuhan Porsi Harian
                    </h1>
                    @if ($portionRequest)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border {{ $portionRequest->status->badgeClasses() }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $portionRequest->status->dotClasses() }}" aria-hidden="true"></span>
                            <span>{{ $portionRequest->status->label() }}</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border bg-[#FAFBF9] text-[#53645B] border-[#DCE5DD]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#8E9B94]" aria-hidden="true"></span>
                            <span>Belum dibuat</span>
                        </span>
                    @endif
                </div>
                <p class="text-xs sm:text-sm text-[#53645B] mt-1 max-w-2xl leading-relaxed">
                    {{ $school->name }} (NPSN: {{ $school->school_code }}) : Mitra SPPG <strong>{{ $sppg->name }}</strong>. Angka porsi terkonfirmasi menjadi rujukan resmi rencana produksi dapur.
                </p>
            </div>

            <!-- Date Selector Navigator -->
            <div class="bg-white p-2 rounded-xl border border-[#DCE5DD] shadow-2xs self-start md:self-auto">
                <form method="GET" action="{{ route('school.portions.index') }}" class="flex items-center gap-2">
                    <label for="date-select" class="text-xs font-semibold text-[#53645B] shrink-0">Tanggal Layanan:</label>
                    <input type="date" 
                           id="date-select" 
                           name="date" 
                           value="{{ $selectedDate }}" 
                           onchange="this.form.submit()" 
                           class="px-2.5 py-1.5 text-xs bg-[#F5F7F3] border border-[#DCE5DD] rounded-lg text-[#172923] font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#21684B] smooth-transition touch-target">
                    <button type="submit" 
                            class="px-3 py-1.5 text-xs font-semibold bg-[#21684B] text-white rounded-lg hover:bg-[#184F39] smooth-transition touch-target">
                        Pilih
                    </button>
                </form>
            </div>
        </div>

        <!-- Cutoff & Operational Status Banner -->
        @if ($isPastCutoff)
            <div role="status" class="p-4 rounded-xl bg-[#FAF4EB] border border-[#E8D7BE] text-[#885D1C] text-xs sm:text-sm flex items-start gap-3">
                <svg class="w-5 h-5 mt-0.5 shrink-0 text-[#885D1C]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="leading-relaxed">
                    <p class="font-bold text-[#885D1C]">Batas Waktu (Cutoff) Konfirmasi Telah Terlewati</p>
                    <p class="text-[11px] sm:text-xs mt-0.5">
                        Cutoff konfirmasi untuk layanan tanggal <strong>{{ $parsedDate->translatedFormat('d F Y') }}</strong> adalah pada <strong>{{ $cutoff->translatedFormat('d M Y, H:i') }} WIB</strong>. Perubahan angka porsi saat ini hanya dapat dilakukan melalui prosedur <strong>Revisi Terotorisasi</strong> dengan menyertakan alasan tertulis.
                    </p>
                </div>
            </div>
        @else
            <div role="status" class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-[#1B573E] text-xs sm:text-sm flex items-start gap-3">
                <svg class="w-5 h-5 mt-0.5 shrink-0 text-[#21684B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="leading-relaxed">
                    <p class="font-bold text-[#1B573E]">Batas Waktu Konfirmasi Porsi (WIB)</p>
                    <p class="text-[11px] sm:text-xs mt-0.5">
                        Batas akhir konfirmasi mandiri PIC Sekolah: <strong>{{ $cutoff->translatedFormat('l, d F Y') }} pukul {{ $cutoff->format('H:i') }} WIB</strong>. Pastikan kebutuhan porsi terisi sebelum cutoff agar masuk ke dalam rencana produksi SPPG.
                    </p>
                </div>
            </div>
        @endif

        <!-- Main Form & Operational Ledger Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Left Column: Primary Portion Confirmation Form (7 cols on lg) -->
            <div class="lg:col-span-7 space-y-6">
                <!-- Portion Input Card -->
                <div class="bg-white border border-[#DCE5DD] rounded-xl shadow-2xs p-5 sm:p-6 space-y-5">
                    <div class="pb-3 border-b border-[#DCE5DD] flex items-center justify-between">
                        <div>
                            <h2 class="text-sm sm:text-base font-bold text-[#172923]">
                                Permintaan Porsi: {{ $parsedDate->translatedFormat('l, d F Y') }}
                            </h2>
                            <p class="text-xs text-[#53645B] mt-0.5">
                                Nyatakan jumlah siswa yang hadir dan kebutuhan porsi makan siang sesungguhnya.
                            </p>
                        </div>
                    </div>

                    <!-- Snapshot Data Pokok Siswa -->
                    <div class="p-4 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <span class="text-[11px] uppercase tracking-wider font-bold text-[#53645B] block">Siswa Terdaftar (Data Pokok Sekolah)</span>
                            <div class="flex items-baseline gap-2 mt-0.5">
                                <span class="text-2xl font-extrabold text-[#172923] tabular-nums">{{ number_format($portionRequest?->registered_count ?? $school->registered_beneficiaries) }}</span>
                                <span class="text-xs text-[#53645B]">anak terdaftar</span>
                            </div>
                        </div>
                        <div class="text-[11px] text-[#53645B] sm:text-right max-w-xs leading-relaxed">
                            Snapshot data pokok penerima MBG saat permintaan dibuat. Angka kebutuhan aktual tidak harus sama bila ada siswa berhalangan.
                        </div>
                    </div>

                    <!-- ACTION FORM ACCORDING TO STATE -->
                    @if (! $portionRequest || $portionRequest->isDraft())
                        <!-- DRAFT FORM: Save Draft or Confirm -->
                        <div class="space-y-4">
                            <!-- Form 1: Save Draft -->
                            <form method="POST" action="{{ route('school.portions.draft') }}" id="draft-form" class="space-y-4">
                                @csrf
                                <input type="hidden" name="service_date" value="{{ $selectedDate }}">

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <!-- Present Count -->
                                    <div class="space-y-1.5">
                                        <label for="present_count" class="block text-xs font-semibold text-[#172923]">
                                             Perkiraan Siswa Hadir (Opsional)
                                        </label>
                                        <input type="number" 
                                               id="present_count" 
                                               name="present_count" 
                                               value="{{ old('present_count', $portionRequest?->present_count) }}" 
                                               min="0" 
                                               placeholder="Contoh: {{ $portionRequest?->registered_count ?? $school->registered_beneficiaries }}" 
                                               class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white border @error('present_count') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl text-[#172923] font-mono focus:outline-none focus:ring-2 focus:ring-[#21684B] smooth-transition touch-target">
                                        <p class="text-[11px] text-[#53645B]">Perkiraan kehadiran siswa pada hari layanan.</p>
                                        @error('present_count')
                                            <p class="text-xs text-[#9B3232] mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Requested Portions -->
                                    <div class="space-y-1.5">
                                        <label for="requested_portions" class="block text-xs font-semibold text-[#172923]">
                                            Kebutuhan Porsi Dikonfirmasi <span class="text-[#9B3232]">*</span>
                                        </label>
                                        <input type="number" 
                                               id="requested_portions" 
                                               name="requested_portions" 
                                               value="{{ old('requested_portions', $portionRequest?->requested_portions) }}" 
                                               min="0" 
                                               required 
                                               placeholder="Contoh: {{ $portionRequest?->registered_count ?? $school->registered_beneficiaries }}" 
                                               class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white border @error('requested_portions') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl text-[#172923] font-mono font-bold focus:outline-none focus:ring-2 focus:ring-[#21684B] smooth-transition touch-target">
                                        <p class="text-[11px] text-[#53645B]">Angka final porsi yang dimasak oleh SPPG.</p>
                                        @error('requested_portions')
                                            <p class="text-xs text-[#9B3232] mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Justification note if requested > registered -->
                                <div class="space-y-1.5">
                                    <label for="revision_reason" class="block text-xs font-semibold text-[#172923]">
                                        Catatan Khusus / Alasan Tambahan
                                    </label>
                                    <textarea id="revision_reason" 
                                              name="revision_reason" 
                                              rows="2" 
                                              placeholder="Wajib diisi apabila porsi yang diminta melebihi jumlah siswa terdaftar (misal ada rombel tambahan kegiatan sekolah)" 
                                              class="w-full px-3.5 py-2 text-xs bg-white border @error('revision_reason') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#21684B] smooth-transition">{{ old('revision_reason', $portionRequest?->revision_reason) }}</textarea>
                                    @error('revision_reason')
                                        <p class="text-xs text-[#9B3232] mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Action Buttons -->
                                <div class="pt-3 border-t border-[#DCE5DD] flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                                    <button type="submit" 
                                            class="px-4 py-2.5 text-xs font-semibold text-[#172923] bg-[#F5F7F3] hover:bg-[#EDF2ED] border border-[#DCE5DD] rounded-xl smooth-transition touch-target">
                                        Simpan Sebagai Draft
                                    </button>

                                    <!-- Trigger Confirmation Action -->
                                    <button type="submit" 
                                            formaction="{{ $portionRequest ? route('school.portions.confirm', $portionRequest) : route('school.portions.confirm-direct') }}" 
                                            onclick="return confirm('Kirim dan konfirmasi porsi harian ini secara resmi ke SPPG?');" 
                                            class="px-5 py-2.5 text-xs sm:text-sm font-bold text-white bg-[#1C6654] hover:bg-[#154E40] rounded-xl shadow-xs smooth-transition touch-target focus:outline-none focus:ring-2 focus:ring-[#1C6654] focus:ring-offset-2 flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span>Konfirmasi Kebutuhan Porsi</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @elseif ($portionRequest->isConfirmed() || $portionRequest->isLocked() || $portionRequest->isRevised())
                        <!-- CONFIRMED / LOCKED / REVISED STATE -->
                        <div class="space-y-4">
                            <!-- Confirmed Details Display -->
                            <div class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-[#1B573E]">Status Kebutuhan Terkonfirmasi</span>
                                    <span class="font-mono text-xs font-extrabold text-[#1B573E] bg-white px-2.5 py-0.5 rounded border border-[#BCE2D0]">
                                        {{ number_format($portionRequest->requested_portions) }} Porsi
                                    </span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-[#1B573E] pt-2 border-t border-[#BCE2D0]/60">
                                    <div>
                                        <span class="text-[#53645B] block text-[11px]">Dikonfirmasi Oleh:</span>
                                        <strong>{{ $portionRequest->confirmedBy?->name ?? 'PIC Terdaftar' }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-[#53645B] block text-[11px]">Waktu Konfirmasi:</span>
                                        <span>{{ $portionRequest->confirmed_at?->translatedFormat('d M Y, H:i') }} WIB</span>
                                    </div>
                                </div>
                                @if ($portionRequest->revision_reason)
                                    <div class="pt-2 border-t border-[#BCE2D0]/60 text-xs">
                                        <span class="text-[#53645B] block text-[11px]">Catatan / Alasan:</span>
                                        <p class="font-medium text-[#172923] mt-0.5">{{ $portionRequest->revision_reason }}</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Revision Submission Section -->
                            <div class="pt-3 border-t border-[#DCE5DD]">
                                <details class="group rounded-xl border border-[#DCE5DD] bg-[#FAFBF9] p-4 transition-all">
                                    <summary class="flex items-center justify-between cursor-pointer text-xs font-bold text-[#172923] touch-target select-none">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-[#885D1C]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            <span>Ajukan Revisi Angka Porsi Terotorisasi</span>
                                        </div>
                                        <span class="text-[11px] text-[#53645B] group-open:rotate-180 transition-transform">&darr;</span>
                                    </summary>

                                    <form method="POST" action="{{ route('school.portions.revise', $portionRequest) }}" class="mt-4 space-y-4 pt-3 border-t border-[#DCE5DD]">
                                        @csrf
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div class="space-y-1">
                                                <label for="rev_requested_portions" class="block text-xs font-semibold text-[#172923]">
                                                    Angka Porsi Revisi Baru <span class="text-[#9B3232]">*</span>
                                                </label>
                                                <input type="number" 
                                                       id="rev_requested_portions" 
                                                       name="requested_portions" 
                                                       value="{{ old('requested_portions', $portionRequest->requested_portions) }}" 
                                                       min="0" 
                                                       required 
                                                       class="w-full px-3 py-2 text-xs bg-white border border-[#DCE5DD] rounded-xl text-[#172923] font-mono font-bold focus:outline-none focus:ring-2 focus:ring-[#885D1C] touch-target">
                                            </div>
                                            <div class="space-y-1">
                                                <label for="rev_present_count" class="block text-xs font-semibold text-[#172923]">
                                                    Jumlah Hadir Baru (Opsional)
                                                </label>
                                                <input type="number" 
                                                       id="rev_present_count" 
                                                       name="present_count" 
                                                       value="{{ old('present_count', $portionRequest->present_count) }}" 
                                                       min="0" 
                                                       class="w-full px-3 py-2 text-xs bg-white border border-[#DCE5DD] rounded-xl text-[#172923] font-mono focus:outline-none focus:ring-2 focus:ring-[#885D1C] touch-target">
                                            </div>
                                        </div>

                                        <div class="space-y-1">
                                            <label for="rev_reason" class="block text-xs font-semibold text-[#172923]">
                                                Alasan Resmi Revisi <span class="text-[#9B3232]">*</span>
                                            </label>
                                            <textarea id="rev_reason" 
                                                      name="revision_reason" 
                                                      rows="2" 
                                                      required 
                                                      placeholder="Contoh: Penyesuaian karena 1 rombel kelas 5 ada kegiatan luar sekolah" 
                                                      class="w-full px-3 py-2 text-xs bg-white border border-[#DCE5DD] rounded-xl text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#885D1C]"></textarea>
                                            <p class="text-[10px] text-[#53645B]">Wajib diisi minimal 5 karakter untuk jejak audit.</p>
                                        </div>

                                        <div class="flex justify-end">
                                            <button type="submit" 
                                                    onclick="return confirm('Kirim revisi kebutuhan porsi ini? Perubahan akan dicatat dalam audit log.');" 
                                                    class="px-4 py-2 text-xs font-bold text-white bg-[#885D1C] hover:bg-[#6D4916] rounded-xl shadow-xs smooth-transition touch-target">
                                                Kirim Revisi Terotorisasi
                                            </button>
                                        </div>
                                    </form>
                                </details>
                            </div>

                            <!-- Cancellation Form -->
                            <div class="pt-2">
                                <details class="group rounded-xl border border-[#F5C2C2] bg-[#FDF2F2] p-4 transition-all">
                                    <summary class="flex items-center justify-between cursor-pointer text-xs font-bold text-[#9B3232] touch-target select-none">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-[#9B3232]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                            <span>Batalkan Layanan Sekolah untuk Tanggal Ini</span>
                                        </div>
                                        <span class="text-[11px] text-[#9B3232] group-open:rotate-180 transition-transform">&darr;</span>
                                    </summary>

                                    <form method="POST" action="{{ route('school.portions.cancel', $portionRequest) }}" class="mt-4 space-y-3 pt-3 border-t border-[#F5C2C2]">
                                        @csrf
                                        <div class="space-y-1">
                                            <label for="cancel_reason" class="block text-xs font-semibold text-[#9B3232]">
                                                Alasan Pembatalan Layanan <span class="text-[#9B3232]">*</span>
                                            </label>
                                            <textarea id="cancel_reason" 
                                                      name="reason" 
                                                      rows="2" 
                                                      required 
                                                      placeholder="Contoh: Hari libur khusus sekolah / bencana alam lokal" 
                                                      class="w-full px-3 py-2 text-xs bg-white border border-[#F5C2C2] rounded-xl text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#9B3232]"></textarea>
                                        </div>

                                        <div class="flex justify-end">
                                            <button type="submit" 
                                                    onclick="return confirm('PERINGATAN: Batalkan layanan MBG untuk sekolah ini pada tanggal terpilih? Tindakan ini dicatat pada audit log.');" 
                                                    class="px-4 py-2 text-xs font-bold text-white bg-[#9B3232] hover:bg-[#7D2828] rounded-xl shadow-xs smooth-transition touch-target">
                                                Konfirmasi Pembatalan Layanan
                                            </button>
                                        </div>
                                    </form>
                                </details>
                            </div>
                        </div>
                    @elseif ($portionRequest->isCancelled())
                        <!-- CANCELLED STATE -->
                        <div class="p-4 rounded-xl bg-[#FDF2F2] border border-[#F5C2C2] text-xs text-[#9B3232] space-y-2">
                            <p class="font-bold">Layanan MBG Dibatalkan untuk Tanggal Ini</p>
                            <p class="text-[11px] leading-relaxed">
                                Alasan: <strong>{{ $portionRequest->revision_reason }}</strong>
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Column: Operational Rules & 10-Days Ledger (5 cols on lg) -->
            <div class="lg:col-span-5 space-y-6">
                <!-- Operational Guidelines Card -->
                <div class="bg-white border border-[#DCE5DD] rounded-xl shadow-2xs p-5 space-y-3">
                    <h3 class="text-sm font-bold text-[#172923] pb-2 border-b border-[#DCE5DD]">
                        Ketentuan Konfirmasi Porsi Harian
                    </h3>
                    <ul class="text-xs text-[#53645B] space-y-2 leading-relaxed">
                        <li class="flex items-start gap-2">
                            <span class="font-bold text-[#1C6654] shrink-0">•</span>
                            <span>Konfirmasi porsi dilakukan mandiri oleh PIC Sekolah terdaftar paling lambat <strong>H-1 pukul 16:00 WIB</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="font-bold text-[#1C6654] shrink-0">•</span>
                            <span>Sekolah yang belum mengonfirmasi <strong>TIDAK diasumsikan nol</strong> secara sepihak oleh sistem.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="font-bold text-[#1C6654] shrink-0">•</span>
                            <span>Jika porsi yang diminta melebihi data pokok siswa terdaftar ({{ number_format($portionRequest?->registered_count ?? $school->registered_beneficiaries) }} anak), sistem mewajibkan alasan tertulis.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="font-bold text-[#1C6654] shrink-0">•</span>
                            <span>Seluruh riwayat pengajuan dan revisi tercatat dalam log audit operasional SPPG.</span>
                        </li>
                    </ul>
                </div>

                <!-- Recent Portions History Ledger -->
                <div class="bg-white border border-[#DCE5DD] rounded-xl shadow-2xs p-5 space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-[#DCE5DD]">
                        <h3 class="text-sm font-bold text-[#172923]">
                            Riwayat Permintaan 10 Hari Terakhir
                        </h3>
                    </div>

                    @if ($history->isEmpty())
                        <p class="text-xs text-[#53645B] py-3 text-center">Belum ada riwayat konfirmasi sebelumnya.</p>
                    @else
                        <div class="divide-y divide-[#DCE5DD] text-xs">
                            @foreach ($history as $h)
                                <div class="py-2.5 flex items-center justify-between gap-2 first:pt-1 last:pb-1">
                                    <div>
                                        <a href="{{ route('school.portions.index', ['date' => $h->service_date->format('Y-m-d')]) }}" 
                                           class="font-semibold text-[#172923] hover:text-[#21684B] smooth-transition block">
                                            {{ $h->service_date->translatedFormat('d M Y') }}
                                        </a>
                                        <span class="text-[10px] text-[#53645B] block">
                                            {{ $h->status->label() }}
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-mono font-bold text-[#172923] block">
                                            {{ $h->requested_portions !== null ? number_format($h->requested_portions) . ' porsi' : 'Draft' }}
                                        </span>
                                        <span class="text-[10px] text-[#53645B] block">
                                            {{ $h->confirmed_at ? $h->confirmed_at->format('H:i') . ' WIB' : 'Belum konfirmasi' }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-shell>
