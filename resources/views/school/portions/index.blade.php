<x-app-shell title="Konfirmasi Kebutuhan Porsi | {{ $school->name }}">
    <div class="space-y-6">
        <!-- Page Header -->
        <header class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-5 border-b border-[#DCE5DD]">
            <div class="space-y-1.5">
                <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-medium text-[#53645B]">
                    <a href="{{ route('school.dashboard') }}" class="min-h-[44px] inline-flex items-center hover:text-[#172923] smooth-transition">Beranda Sekolah</a>
                    <span aria-hidden="true">/</span>
                    <span class="text-[#1C6654] font-semibold">Konfirmasi Porsi (P02)</span>
                </nav>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="page-title">Konfirmasi Kebutuhan Porsi Harian</h1>
                    @if ($portionRequest)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold border {{ $portionRequest->status->badgeClasses() }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $portionRequest->status->dotClasses() }}" aria-hidden="true"></span>
                            <span>{{ $portionRequest->status->label() }}</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold border bg-[#FAFBF9] text-[#53645B] border-[#DCE5DD]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#53645B]" aria-hidden="true"></span>
                            <span>Belum dibuat</span>
                        </span>
                    @endif
                </div>
                <p class="text-xs sm:text-sm text-[#53645B] max-w-2xl leading-relaxed">
                    {{ $school->name }} (Kode: <span class="font-mono font-semibold text-[#172923]">{{ $school->school_code }}</span>) • Mitra SPPG <strong>{{ $sppg->name }}</strong>. Angka terkonfirmasi menjadi acuan resmi produksi dapur.
                </p>
            </div>

            <!-- Date Selector -->
            <div class="luxury-surface p-2 self-start md:self-auto w-full sm:w-auto">
                <form method="GET" action="{{ route('school.portions.index') }}" class="flex items-center gap-2">
                    <label for="date-select" class="text-xs font-semibold text-[#53645B] pl-2 shrink-0">Tanggal:</label>
                    <input type="date"
                           id="date-select"
                           name="date"
                           value="{{ $selectedDate }}"
                           onchange="this.form.submit()"
                           class="min-h-[44px] flex-1 sm:flex-initial px-3 py-2 text-xs bg-[#F5F7F3] border border-[#DCE5DD] rounded-xl text-[#172923] font-semibold tabular-nums focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] smooth-transition">
                    <button type="submit"
                            class="min-h-[44px] px-4 py-2 text-xs font-bold bg-[#1C6654] text-white rounded-xl hover:bg-[#164D42] smooth-transition shrink-0 cursor-pointer">
                        Pilih
                    </button>
                </form>
            </div>
        </header>

        <!-- Cutoff Status Strip -->
        @if ($isPastCutoff)
            <div role="status" class="p-4 rounded-2xl bg-[#FEF7EC] border border-[#F7D7A3] text-[#885D1C] text-xs flex items-start gap-3">
                <svg class="w-5 h-5 mt-0.5 shrink-0 text-[#885D1C]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="leading-relaxed">
                    <p class="font-bold">Batas Waktu (Cutoff) Konfirmasi Telah Terlewati</p>
                    <p class="mt-0.5">
                        Cutoff layanan tanggal <strong>{{ $parsedDate->translatedFormat('d F Y') }}</strong> berakhir pada <strong>{{ $cutoff->translatedFormat('d M Y, H:i') }} WIB</strong>. Perubahan porsi dilakukan melalui prosedur <strong>Revisi Terotorisasi</strong> disertai alasan tertulis.
                    </p>
                </div>
            </div>
        @else
            <div role="status" class="p-4 rounded-2xl bg-[#EDF7F2] border border-[#BCE2D0] text-[#21684B] text-xs flex items-start gap-3">
                <svg class="w-5 h-5 mt-0.5 shrink-0 text-[#21684B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="leading-relaxed">
                    <p class="font-bold">Batas Waktu Konfirmasi Porsi (WIB)</p>
                    <p class="mt-0.5">
                        Batas akhir konfirmasi mandiri: <strong>{{ $cutoff->translatedFormat('l, d F Y') }} pukul {{ $cutoff->format('H:i') }} WIB</strong>. Pastikan kebutuhan porsi dikirim sebelum cutoff agar masuk rencana masak SPPG.
                    </p>
                </div>
            </div>
        @endif

        <!-- Main Workspace Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Left Column: Primary Portion Action Card -->
            <div class="lg:col-span-7 space-y-5">
                <section aria-labelledby="portion-form-heading" class="luxury-surface p-5 sm:p-6 space-y-5">
                    <div class="pb-3.5 border-b border-[#E7ECE8]">
                        <span class="eyebrow-label block mb-1">Formulir Kebutuhan Harian</span>
                        <h2 id="portion-form-heading" class="section-title">
                            Permintaan Porsi: {{ $parsedDate->translatedFormat('l, d F Y') }}
                        </h2>
                        <p class="text-xs text-[#53645B] mt-0.5">
                            Nyatakan perkiraan kehadiran siswa dan kebutuhan porsi aktual hari layanan.
                        </p>
                    </div>

                    <!-- Registered Beneficiaries Reference -->
                    <div class="inset-surface p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <span class="eyebrow-label block">Siswa Terdaftar (Data Pokok Sekolah)</span>
                            <div class="flex items-baseline gap-2 mt-1">
                                <span class="text-2xl font-bold text-[#172923] tabular-nums">{{ number_format($portionRequest?->registered_count ?? $school->registered_beneficiaries) }}</span>
                                <span class="text-xs text-[#53645B]">anak terdaftar</span>
                            </div>
                        </div>
                        <p class="text-[11px] text-[#53645B] sm:text-right max-w-xs leading-relaxed">
                            Data pokok penerima MBG. Angka porsi aktual dapat disesuaikan bila ada siswa absen atau kegiatan luar.
                        </p>
                    </div>

                    @if (! $portionRequest || $portionRequest->isDraft())
                        <form method="POST" action="{{ route('school.portions.draft') }}" id="draft-form" class="space-y-4">
                            @csrf
                            <input type="hidden" name="service_date" value="{{ $selectedDate }}">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                                           class="min-h-[44px] w-full px-3.5 py-2.5 text-sm bg-white border @error('present_count') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl text-[#172923] tabular-nums focus:outline-none focus:ring-2 focus:ring-[#1C6654] smooth-transition">
                                    <p class="text-[11px] text-[#53645B]">Estimasi siswa hadir di sekolah.</p>
                                    @error('present_count')
                                        <p class="text-xs text-[#9B3232] font-medium mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

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
                                           class="min-h-[44px] w-full px-3.5 py-2.5 text-sm bg-white border @error('requested_portions') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl text-[#172923] font-bold tabular-nums focus:outline-none focus:ring-2 focus:ring-[#1C6654] smooth-transition">
                                    <p class="text-[11px] text-[#53645B]">Angka final porsi yang dimasak SPPG.</p>
                                    @error('requested_portions')
                                        <p class="text-xs text-[#9B3232] font-medium mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label for="revision_reason" class="block text-xs font-semibold text-[#172923]">
                                    Catatan Khusus / Alasan Tambahan
                                </label>
                                <textarea id="revision_reason"
                                          name="revision_reason"
                                          rows="2"
                                          placeholder="Wajib diisi bila porsi melebihi siswa terdaftar (misal ada rombel kegiatan tambahan)"
                                          class="w-full px-3.5 py-2.5 text-xs bg-white border @error('revision_reason') border-[#9B3232] @else border-[#DCE5DD] @enderror rounded-xl text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654] smooth-transition">{{ old('revision_reason', $portionRequest?->revision_reason) }}</textarea>
                                @error('revision_reason')
                                    <p class="text-xs text-[#9B3232] font-medium mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="pt-3.5 border-t border-[#E7ECE8] flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3">
                                <button type="submit"
                                        class="min-h-[44px] px-4 py-2.5 text-xs font-semibold text-[#172923] bg-[#F5F7F3] hover:bg-[#EDF2ED] border border-[#DCE5DD] rounded-xl smooth-transition cursor-pointer">
                                    Simpan Sebagai Draft
                                </button>

                                <button type="submit"
                                        formaction="{{ $portionRequest ? route('school.portions.confirm', $portionRequest) : route('school.portions.confirm-direct') }}"
                                        onclick="return confirm('Kirim dan konfirmasi porsi harian ini secara resmi ke SPPG?');"
                                        class="min-h-[44px] px-5 py-2.5 text-xs sm:text-sm font-bold text-white bg-[#1C6654] hover:bg-[#164D42] rounded-xl subtle-shadow smooth-transition flex items-center justify-center gap-2 cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Konfirmasi Kebutuhan Porsi</span>
                                </button>
                            </div>
                        </form>
                    @elseif ($portionRequest->isConfirmed() || $portionRequest->isLocked() || $portionRequest->isRevised())
                        <div class="space-y-4">
                            <div class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] space-y-3">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-[#21684B]">Status Kebutuhan Terkonfirmasi</span>
                                    <span class="text-xs font-bold tabular-nums text-[#21684B] bg-white px-2.5 py-1 rounded-lg border border-[#BCE2D0]">
                                        {{ number_format($portionRequest->requested_portions) }} Porsi
                                    </span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-[#172923] pt-2.5 border-t border-[#BCE2D0]/70">
                                    <div>
                                        <span class="text-[#53645B] block text-[11px]">Dikonfirmasi Oleh</span>
                                        <strong>{{ $portionRequest->confirmedBy?->name ?? 'PIC Terdaftar' }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-[#53645B] block text-[11px]">Waktu Konfirmasi</span>
                                        <span class="tabular-nums font-medium">{{ $portionRequest->confirmed_at?->translatedFormat('d M Y, H:i') }} WIB</span>
                                    </div>
                                </div>
                                @if ($portionRequest->revision_reason)
                                    <div class="pt-2.5 border-t border-[#BCE2D0]/70 text-xs">
                                        <span class="text-[#53645B] block text-[11px]">Catatan / Alasan</span>
                                        <p class="font-medium text-[#172923] mt-0.5">{{ $portionRequest->revision_reason }}</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Authorized Revision Disclosure -->
                            <details class="sop-disclosure">
                                <summary>
                                    <span class="flex items-center gap-2 text-xs font-bold text-[#172923]">
                                        <svg class="w-4 h-4 text-[#885D1C] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        <span>Ajukan Revisi Angka Porsi Terotorisasi</span>
                                    </span>
                                    <span class="text-[11px] font-semibold text-[#53645B]">Buka</span>
                                </summary>
                                <div class="sop-disclosure-body">
                                    <form method="POST" action="{{ route('school.portions.revise', $portionRequest) }}" class="space-y-4">
                                        @csrf
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div class="space-y-1.5">
                                                <label for="rev_requested_portions" class="block text-xs font-semibold text-[#172923]">
                                                    Angka Porsi Revisi Baru <span class="text-[#9B3232]">*</span>
                                                </label>
                                                <input type="number"
                                                       id="rev_requested_portions"
                                                       name="requested_portions"
                                                       value="{{ old('requested_portions', $portionRequest->requested_portions) }}"
                                                       min="0"
                                                       required
                                                       class="min-h-[44px] w-full px-3.5 py-2 text-sm bg-white border border-[#DCE5DD] rounded-xl text-[#172923] font-bold tabular-nums focus:outline-none focus:ring-2 focus:ring-[#885D1C]">
                                            </div>
                                            <div class="space-y-1.5">
                                                <label for="rev_present_count" class="block text-xs font-semibold text-[#172923]">
                                                    Jumlah Hadir Baru (Opsional)
                                                </label>
                                                <input type="number"
                                                       id="rev_present_count"
                                                       name="present_count"
                                                       value="{{ old('present_count', $portionRequest->present_count) }}"
                                                       min="0"
                                                       class="min-h-[44px] w-full px-3.5 py-2 text-sm bg-white border border-[#DCE5DD] rounded-xl text-[#172923] tabular-nums focus:outline-none focus:ring-2 focus:ring-[#885D1C]">
                                            </div>
                                        </div>

                                        <div class="space-y-1.5">
                                            <label for="rev_reason" class="block text-xs font-semibold text-[#172923]">
                                                Alasan Resmi Revisi <span class="text-[#9B3232]">*</span>
                                            </label>
                                            <textarea id="rev_reason"
                                                      name="revision_reason"
                                                      rows="2"
                                                      required
                                                      placeholder="Contoh: Penyesuaian karena 1 rombel kelas 5 mengikuti kegiatan luar sekolah"
                                                      class="w-full px-3.5 py-2 text-xs bg-white border border-[#DCE5DD] rounded-xl text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#885D1C]"></textarea>
                                            <p class="text-[11px] text-[#53645B]">Minimal 5 karakter untuk jejak audit operasional.</p>
                                        </div>

                                        <div class="flex justify-end">
                                            <button type="submit"
                                                    onclick="return confirm('Kirim revisi kebutuhan porsi ini? Perubahan akan dicatat dalam audit log.');"
                                                    class="min-h-[44px] px-4 py-2 text-xs font-bold text-white bg-[#885D1C] hover:bg-[#6E4A15] rounded-xl smooth-transition cursor-pointer">
                                                Kirim Revisi Terotorisasi
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </details>

                            <!-- Service Cancellation Disclosure -->
                            <details class="rounded-xl border border-[#F5C2C2] bg-[#FDF2F2] overflow-hidden">
                                <summary class="min-h-[44px] px-4 py-3 flex items-center justify-between cursor-pointer list-none select-none">
                                    <span class="flex items-center gap-2 text-xs font-bold text-[#9B3232]">
                                        <svg class="w-4 h-4 text-[#9B3232] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                        <span>Batalkan Layanan Sekolah untuk Tanggal Ini</span>
                                    </span>
                                    <span class="text-[11px] font-semibold text-[#9B3232]">Buka</span>
                                </summary>
                                <div class="p-4 border-t border-[#F5C2C2]">
                                    <form method="POST" action="{{ route('school.portions.cancel', $portionRequest) }}" class="space-y-3">
                                        @csrf
                                        <div class="space-y-1.5">
                                            <label for="cancel_reason" class="block text-xs font-semibold text-[#9B3232]">
                                                Alasan Pembatalan Layanan <span class="text-[#9B3232]">*</span>
                                            </label>
                                            <textarea id="cancel_reason"
                                                      name="reason"
                                                      rows="2"
                                                      required
                                                      placeholder="Contoh: Hari libur khusus sekolah atau kondisi darurat lokal"
                                                      class="w-full px-3.5 py-2 text-xs bg-white border border-[#F5C2C2] rounded-xl text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#9B3232]"></textarea>
                                        </div>

                                        <div class="flex justify-end">
                                            <button type="submit"
                                                    onclick="return confirm('PERINGATAN: Batalkan layanan MBG untuk sekolah ini pada tanggal terpilih? Tindakan ini dicatat pada audit log.');"
                                                    class="min-h-[44px] px-4 py-2 text-xs font-bold text-white bg-[#9B3232] hover:bg-[#7D2828] rounded-xl smooth-transition cursor-pointer">
                                                Konfirmasi Pembatalan Layanan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </details>
                        </div>
                    @elseif ($portionRequest->isCancelled())
                        <div class="p-4 rounded-xl bg-[#FDF2F2] border border-[#F5C2C2] text-xs text-[#9B3232] space-y-1.5">
                            <p class="font-bold">Layanan MBG Dibatalkan untuk Tanggal Ini</p>
                            <p class="leading-relaxed">Alasan: <strong>{{ $portionRequest->revision_reason }}</strong></p>
                        </div>
                    @endif
                </section>

                <!-- Progressive SOP Disclosure -->
                <details class="sop-disclosure">
                    <summary>
                        <span class="text-xs font-bold text-[#172923]">Ketentuan Konfirmasi Porsi Harian</span>
                        <span class="text-[11px] font-semibold text-[#1C6654]">Panduan SOP</span>
                    </summary>
                    <div class="sop-disclosure-body">
                        <ul class="text-xs text-[#53645B] space-y-2 leading-relaxed">
                            <li class="flex items-start gap-2">
                                <span class="font-bold text-[#1C6654] shrink-0">1.</span>
                                <span>Konfirmasi porsi dilakukan mandiri oleh PIC Sekolah terdaftar paling lambat <strong>H-1 pukul 16:00 WIB</strong>.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="font-bold text-[#1C6654] shrink-0">2.</span>
                                <span>Sekolah yang belum mengonfirmasi <strong>tidak diasumsikan nol</strong> secara sepihak oleh sistem.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="font-bold text-[#1C6654] shrink-0">3.</span>
                                <span>Jika porsi melebihi data pokok siswa terdaftar ({{ number_format($portionRequest?->registered_count ?? $school->registered_beneficiaries) }} anak), alasan tertulis wajib dicantumkan.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="font-bold text-[#1C6654] shrink-0">4.</span>
                                <span>Seluruh riwayat pengajuan dan revisi terekam dalam log audit operasional SPPG.</span>
                            </li>
                        </ul>
                    </div>
                </details>
            </div>

            <!-- Right Column: 10-Day History Ledger -->
            <div class="lg:col-span-5">
                <section aria-labelledby="history-ledger-heading" class="luxury-surface p-5 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-[#E7ECE8]">
                        <div>
                            <span class="eyebrow-label block mb-0.5">Arsip Layanan</span>
                            <h3 id="history-ledger-heading" class="section-title">Riwayat Permintaan 10 Hari Terakhir</h3>
                        </div>
                    </div>

                    @if ($history->isEmpty())
                        <p class="text-xs text-[#53645B] py-6 text-center">Belum ada riwayat konfirmasi sebelumnya.</p>
                    @else
                        <div class="divide-y divide-[#E7ECE8] text-xs">
                            @foreach ($history as $h)
                                <a href="{{ route('school.portions.index', ['date' => $h->service_date->format('Y-m-d')]) }}"
                                   class="min-h-[48px] py-2.5 flex items-center justify-between gap-3 hover:bg-[#FAFBF9] rounded-lg px-2 -mx-2 smooth-transition">
                                    <div>
                                        <span class="font-semibold text-[#172923] block tabular-nums">
                                            {{ $h->service_date->translatedFormat('d M Y') }}
                                        </span>
                                        <span class="text-[11px] text-[#53645B] block mt-0.5">
                                            {{ $h->status->label() }}
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-bold text-[#172923] tabular-nums block">
                                            {{ $h->requested_portions !== null ? number_format($h->requested_portions) . ' porsi' : 'Draft' }}
                                        </span>
                                        <span class="text-[11px] text-[#53645B] tabular-nums block mt-0.5">
                                            {{ $h->confirmed_at ? $h->confirmed_at->format('H:i') . ' WIB' : 'Belum konfirmasi' }}
                                        </span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>
</x-app-shell>
