<x-app-shell title="Persiapan Perjalanan : {{ $trip->trip_code }} : MBG-Track">
    <!-- Header Persiapan Perjalanan (Page D02) -->
    <header class="mb-6 pb-4 border-b border-[#DCE5DD]">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                    <a href="{{ route('driver.trips.index') }}" class="text-xs font-bold text-[#1C6654] hover:underline flex items-center gap-1 touch-target">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span>Kembali ke Daftar Tugas (D01)</span>
                    </a>
                    <span class="text-xs font-mono text-[#53645B] bg-white border border-[#DCE5DD] px-2 py-0.5 rounded">
                        Trip: {{ $trip->trip_code }}
                    </span>
                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full border {{ $trip->status->badgeClass() }}">
                        Status: {{ $trip->status->label() }}
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">
                    Persiapan Keberangkatan &amp; Checklist Armada
                </h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">
                    Armada: <strong>{{ $trip->vehicle->plate_number }} ({{ $trip->vehicle->vehicle_type }})</strong> : Tanggal Layanan: <strong>{{ $trip->service_date->translatedFormat('l, d F Y') }}</strong>
                </p>
            </div>

            <!-- Petunjuk Keamanan -->
            <div class="p-3 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] text-[11px] text-[#53645B] max-w-xs">
                <span class="font-bold text-[#172923] block mb-0.5">Fokus Keselamatan:</span>
                Periksa kelaikan kendaraan secara fisik sebelum memuat makanan dan memulai perjalanan.
            </div>
        </div>
    </header>

    <!-- Notifikasi Flash -->
    @if (session('success'))
        <div class="mb-6 p-4 rounded-2xl bg-[#EDF7F2] border border-[#BCE2D0] text-[#1B573E] text-xs sm:text-sm font-semibold flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 p-4 rounded-2xl bg-[#FDF2F2] border border-[#F5C2C2] text-[#9B3232] text-xs sm:text-sm font-semibold flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Kolom Kiri (7 Kolom): Checklist Kelaikan Kendaraan & Tombol Mulai -->
        <div class="lg:col-span-7 space-y-6">
            <!-- 1. Form Checklist Kelaikan Armada -->
            <div class="p-5 rounded-2xl bg-white border border-[#DCE5DD]">
                <div class="flex items-center justify-between pb-3 border-b border-[#DCE5DD] mb-4">
                    <div>
                        <h2 class="text-base font-bold text-[#172923]">Pemeriksaan Fisik Kelaikan Armada</h2>
                        <p class="text-xs text-[#53645B]">Wajib diverifikasi sebelum armada diizinkan keluar dari dapur SPPG.</p>
                    </div>
                    <span class="text-xs font-mono font-bold text-[#172923] bg-[#EDF2ED] px-2 py-0.5 rounded border border-[#DCE5DD]">
                        {{ $trip->vehicle->plate_number }}
                    </span>
                </div>

                <!-- Status Hasil Pemeriksaan Sebelumnya -->
                @if ($trip->vehicle_checked_at)
                    <div class="mb-4 p-3.5 rounded-xl border {{ $trip->vehicle_check_passed ? 'bg-[#EDF7F2] border-[#BCE2D0] text-[#1B573E]' : 'bg-[#FDF2F2] border-[#F5C2C2] text-[#9B3232]' }} text-xs">
                        <div class="font-bold flex items-center gap-1.5 mb-1">
                            @if ($trip->vehicle_check_passed)
                                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Pemeriksaan Terakhir: LAIK BEROPERASI</span>
                            @else
                                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <span>Pemeriksaan Terakhir: TIDAK LAIK / TERDAPAT KENDALA</span>
                            @endif
                        </div>
                        <p class="text-[11px]">
                            Diverifikasi pada: {{ $trip->vehicle_checked_at->format('d/m/Y H:i') }} WIB
                            @if ($trip->vehicle_check_notes)
                                <br>Catatan: <em>"{{ $trip->vehicle_check_notes }}"</em>
                            @endif
                        </p>
                    </div>
                @endif

                @if ($trip->status->value === 'IN_PROGRESS' || $trip->status->value === 'COMPLETED')
                    <div class="p-4 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] text-xs text-[#53645B]">
                        Pemeriksaan kendaraan telah dikunci karena perjalanan sedang berlangsung atau telah selesai.
                    </div>
                @else
                    <form method="POST" action="{{ route('driver.trips.vehicle-check', $trip) }}" class="space-y-4">
                        @csrf

                        <!-- Item-Item Checklist Visual -->
                        <div class="p-3.5 rounded-xl bg-[#FAFBF9] border border-[#E8EEE9] space-y-2 text-xs text-[#172923]">
                            <span class="font-bold text-[#53645B] block mb-1 uppercase tracking-wider text-[11px]">
                                Poin Pemeriksaan Mandiri Kurir:
                            </span>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-[#1C6654]"></span>
                                <span>Sistem pengereman dan lampu kendaraan berfungsi normal.</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-[#1C6654]"></span>
                                <span>Tekanan angin ban cukup dan tidak ada kebocoran.</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-[#1C6654]"></span>
                                <span>Bahan bakar / daya baterai mencukupi untuk seluruh rute.</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-[#1C6654]"></span>
                                <span>Boks insulasi makanan bersih, higienis, dan penutup rapat.</span>
                            </div>
                        </div>

                        <!-- Pilihan Radio Hasil -->
                        <div>
                            <span class="block text-xs font-bold text-[#172923] mb-2">
                                Keputusan Kelaikan Armada: <span class="text-red-500">*</span>
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label class="p-3 rounded-xl border border-[#DCE5DD] hover:border-[#BCE2D0] cursor-pointer flex items-center gap-3 bg-white">
                                    <input type="radio" 
                                           name="passed" 
                                           value="1" 
                                           {{ old('passed', $trip->vehicle_check_passed ? '1' : '') === '1' ? 'checked' : '' }}
                                           required
                                           class="w-4 h-4 text-[#1C6654] focus:ring-[#1C6654]">
                                    <div>
                                        <span class="text-xs font-bold text-[#172923] block">Armada Laik Jalan</span>
                                        <span class="text-[10px] text-[#53645B]">Kendaraan aman dan siap berangkat</span>
                                    </div>
                                </label>

                                <label class="p-3 rounded-xl border border-[#DCE5DD] hover:border-[#F5C2C2] cursor-pointer flex items-center gap-3 bg-white">
                                    <input type="radio" 
                                           name="passed" 
                                           value="0" 
                                           {{ old('passed', $trip->vehicle_check_passed === false ? '0' : '') === '0' ? 'checked' : '' }}
                                           required
                                           class="w-4 h-4 text-[#9B3232] focus:ring-[#9B3232]">
                                    <div>
                                        <span class="text-xs font-bold text-[#9B3232] block">Kendaraan Bermasalah</span>
                                        <span class="text-[10px] text-[#53645B]">Ada kendala teknis / tidak laik</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Catatan Tambahan -->
                        <div>
                            <label for="notes" class="block text-xs font-bold text-[#172923] mb-1">
                                Catatan Pemeriksaan (Opsional)
                            </label>
                            <textarea id="notes" 
                                      name="notes" 
                                      rows="2" 
                                      placeholder="Contoh: Kondisi rem baik, boks bersih, bahan bakar penuh."
                                      class="w-full px-3 py-2 text-xs text-[#172923] bg-white border border-[#DCE5DD] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1C6654]">{{ old('notes', $trip->vehicle_check_notes) }}</textarea>
                        </div>

                        <div class="pt-2 flex justify-end">
                            <button type="submit" 
                                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-white border border-[#DCE5DD] hover:bg-[#FAFBF9] text-xs font-bold text-[#172923] shadow-2xs transition-colors touch-target cursor-pointer">
                                <span>Simpan Hasil Pemeriksaan Kendaraan</span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>

            <!-- 2. Gateway Memulai Perjalanan (Start Dispatch) -->
            <div class="p-5 rounded-2xl bg-white border border-[#DCE5DD]">
                <h2 class="text-base font-bold text-[#172923] mb-1">Mulai Perjalanan Pengantaran</h2>
                <p class="text-xs text-[#53645B] mb-4">
                    Setelah muatan selesai dinaikkan ke armada dan pemeriksaan kendaraan lolos, Anda dapat meresmikan awal perjalanan.
                </p>

                @if ($trip->status->value === 'IN_PROGRESS')
                    <div class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-[#1B573E] text-xs">
                        <span class="font-bold block mb-1">Perjalanan Sedang Berlangsung</span>
                        Waktu mulai: {{ $trip->started_at?->format('H:i') }} WIB. Silakan lanjutkan menuju sekolah pemberhentian pertama.
                        <div class="mt-3">
                            <a href="{{ route('trips.show', $trip) }}" 
                               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#1C6654] text-white font-bold touch-target">
                                <span>Buka Status Rute &amp; Stop (C03)</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                @elseif ($trip->status->value === 'READY')
                    <form method="POST" action="{{ route('driver.trips.start', $trip) }}" onsubmit="return confirm('Apakah Anda yakin muatan telah selesai dinaikkan dan siap memulai perjalanan pengantaran?')">
                        @csrf
                        <div class="p-4 rounded-xl bg-[#FAFBF9] border border-[#BCE2D0] mb-4 text-xs text-[#53645B]">
                            <span class="font-bold text-[#1B573E] block mb-1">Kondisi Siap Berangkat (READY):</span>
                            Semua persyaratan operasional terpenuhi. Menekan tombol di bawah akan mencatat waktu mulai resmi dan mengaktifkan status menuju sekolah pertama.
                        </div>

                        <button type="submit" 
                                class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-sm font-bold shadow-xs transition-colors touch-target cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span>Mulai Perjalanan Pengantaran Sekarang</span>
                        </button>
                    </form>
                @else
                    <div class="p-4 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] text-xs text-[#53645B]">
                        <span class="font-bold text-[#172923] block mb-1">Tombol Mulai Belum Aktif</span>
                        Lakukan dan simpan checklist pemeriksaan armada dengan hasil <strong>Armada Laik Jalan</strong> terlebih dahulu untuk membuka izin keberangkatan.
                    </div>
                @endif
            </div>
        </div>

        <!-- Kolom Kanan (5 Kolom): Manifes Muatan & Rute Sekolah -->
        <div class="lg:col-span-5 space-y-4">
            <div class="p-5 rounded-2xl bg-white border border-[#DCE5DD]">
                <div class="flex items-center justify-between pb-3 border-b border-[#DCE5DD] mb-4">
                    <h2 class="text-base font-bold text-[#172923]">Manifes Rute &amp; Muatan</h2>
                    <span class="text-xs font-bold text-[#1C6654] bg-[#EDF7F2] px-2 py-0.5 rounded border border-[#BCE2D0]">
                        Total: {{ $trip->total_loaded_portions }} Porsi
                    </span>
                </div>

                <div class="space-y-3">
                    @foreach ($trip->stops as $stop)
                        <div class="p-4 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD]">
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-[#1C6654] text-white text-xs font-bold flex items-center justify-center shrink-0">
                                        {{ $stop->sequence_number }}
                                    </span>
                                    <div>
                                        <span class="text-xs font-bold text-[#172923] block">{{ $stop->school->name }}</span>
                                        <span class="text-[10px] text-[#53645B]">{{ $stop->school->address }}</span>
                                    </div>
                                </div>
                                <span class="text-[10px] px-2 py-0.5 rounded border {{ $stop->status->badgeClass() }}">
                                    {{ $stop->status->label() }}
                                </span>
                            </div>

                            <!-- Rincian Alokasi Paket di Stop -->
                            <div class="mt-3 pt-2 border-t border-[#E8EEE9] space-y-1.5">
                                <span class="text-[10px] font-semibold text-[#53645B] block uppercase tracking-wider">
                                    Paket Makanan yang Dimuat:
                                </span>
                                @foreach ($stop->deliveryItems as $item)
                                    <div class="flex items-center justify-between text-[11px] pl-2 border-l-2 border-[#1C6654]">
                                        <span class="text-[#172923]">
                                            Batch: {{ $item->foodBatch->batch_code }}
                                            <span class="text-[#708177]">({{ $item->foodBatch->recipeMenu?->name ?? 'Menu' }})</span>
                                        </span>
                                        <span class="font-bold text-[#1B573E]">
                                            {{ $item->loaded_portions ?? $item->released_portions }} Porsi
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-shell>
