<x-app-shell title="Penjemputan Wadah & Ompreng">
    <div class="space-y-5">
        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <span class="eyebrow-label text-[#55327A] block mb-1">Kurir Pengantaran • {{ $driver->sppg?->code ?? 'SPPG' }}</span>
                <h1 class="page-title">Penjemputan Wadah & Ompreng</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-1">Pengangkutan kembali wadah bersih dari sekolah binaan menuju dapur SPPG.</p>
            </div>

            <a href="{{ route('driver.trips.index') }}"
               class="min-h-[44px] px-3.5 py-2 rounded-xl border border-[#DCE5DD] bg-white hover:bg-[#FAFBF9] text-xs font-semibold text-[#172923] inline-flex items-center shrink-0 smooth-transition">
                Daftar Rute Pengiriman
            </a>
        </header>

        <details class="sop-disclosure">
            <summary>
                <span>Panduan Hitung Fisik Penjemputan Ompreng</span>
                <svg class="w-4 h-4 text-[#53645B] disclosure-chevron shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>
            <div class="p-4 text-xs text-[#53645B] leading-relaxed">
                Hitung jumlah fisik ompreng bersama PIC Sekolah sebelum dimuat ke kendaraan. Jika jumlah berbeda dari laporan siap sekolah, isi jumlah riil dan tuliskan keterangan selisihnya.
            </div>
        </details>

        @if (session('success'))
            <div class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-xs sm:text-sm font-medium text-[#1B573E]">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-xl bg-[#FDF2F2] border border-[#F5C2C2] text-xs text-[#9B3232] space-y-1">
                <p class="font-bold">Gagal memproses penjemputan:</p>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Pending Pickups -->
        <section class="space-y-4">
            <h2 class="eyebrow-label text-[#172923]">Sekolah Menunggu Penjemputan ({{ count($pendingPickups) }})</h2>

            @forelse ($pendingPickups as $tray)
                @php
                    $school = $tray->schoolServiceRecord?->tripStop?->school;
                    $origTrip = $tray->schoolServiceRecord?->tripStop?->trip;
                @endphp

                <div class="luxury-surface p-5 space-y-4 border-l-4 border-l-[#885D1C]">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 pb-3 border-b border-[#E7ECE8]">
                        <div>
                            <span class="text-[10px] font-semibold text-[#885D1C] uppercase tracking-wider bg-[#FEF7EC] px-2 py-0.5 rounded-md border border-[#F7D7A3]">Siap Diambil</span>
                            <h3 class="text-base font-bold text-[#172923] mt-1.5">{{ $school?->name ?? 'Sekolah MBG' }}</h3>
                            <p class="text-xs text-[#53645B]">{{ $school?->address ?? '-' }}</p>
                        </div>
                        <div class="sm:text-right text-xs font-mono text-[#53645B]">
                            <span class="font-bold text-[#172923] block">Wadah #{{ $tray->id }}</span>
                            <span>Trip: {{ $origTrip?->trip_code ?? '-' }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                        <div class="inset-surface p-3">
                            <span class="text-[10px] text-[#53645B] block">Terkirim Awal</span>
                            <span class="text-base font-bold text-[#172923] tabular-nums mt-0.5 block">{{ $tray->expected_trays }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-[#FEF7EC] border border-[#F7D7A3]">
                            <span class="text-[10px] text-[#885D1C] font-semibold block">Siap di Sekolah</span>
                            <span class="text-base font-bold text-[#885D1C] tabular-nums mt-0.5 block">{{ $tray->ready_count ?? 0 }}</span>
                        </div>
                        <div class="inset-surface p-3">
                            <span class="text-[10px] text-[#53645B] block">Laporan Rusak</span>
                            <span class="text-base font-bold text-[#172923] tabular-nums mt-0.5 block">{{ (int) $tray->damaged_count }}</span>
                        </div>
                        <div class="inset-surface p-3">
                            <span class="text-[10px] text-[#53645B] block">Waktu Siap</span>
                            <span class="text-xs font-bold text-[#172923] tabular-nums mt-1 block">
                                {{ $tray->ready_at ? $tray->ready_at->setTimezone('Asia/Jakarta')->format('H:i') . ' WIB' : '-' }}
                            </span>
                        </div>
                    </div>

                    @if ($tray->notes)
                        <div class="inset-surface p-3 text-xs text-[#53645B]">
                            <strong class="text-[#172923]">Catatan PIC Sekolah:</strong> {{ $tray->notes }}
                        </div>
                    @endif

                    <form action="{{ route('driver.tray-returns.pickup', $tray) }}" method="POST" enctype="multipart/form-data" class="inset-surface p-4 space-y-3.5">
                        @csrf
                        <span class="text-xs font-bold text-[#172923] block">Konfirmasi Jumlah Fisik Dimuat</span>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div>
                                <label for="pickup_count_{{ $tray->id }}" class="block font-semibold text-[#172923] mb-1">
                                    Jumlah Wadah Dimuat ke Armada <span class="text-[#9B3232]">*</span>
                                </label>
                                <input type="number"
                                       id="pickup_count_{{ $tray->id }}"
                                       name="picked_up_count"
                                       min="0"
                                       value="{{ old('picked_up_count', $tray->ready_count ?? $tray->expected_trays) }}"
                                       required
                                       class="w-full min-h-[44px] px-3.5 py-2 rounded-xl border border-[#DCE5DD] bg-white text-[#172923] font-bold text-sm tabular-nums">
                            </div>
                            <div>
                                <label for="pickup_photo_{{ $tray->id }}" class="block font-semibold text-[#172923] mb-1">Foto Muatan (Opsional)</label>
                                <input type="file"
                                       id="pickup_photo_{{ $tray->id }}"
                                       name="photo"
                                       accept="image/*"
                                       class="w-full text-xs text-[#53645B] file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-[#1C6654] file:text-white">
                            </div>
                        </div>

                        <div class="text-xs">
                            <label for="pickup_notes_{{ $tray->id }}" class="block font-semibold text-[#172923] mb-1">
                                Catatan Serah Terima Fisik (Wajib bila selisih)
                            </label>
                            <input type="text"
                                   id="pickup_notes_{{ $tray->id }}"
                                   name="notes"
                                   placeholder="Contoh: 1 ompreng masih tertinggal di ruang kelas"
                                   class="w-full min-h-[44px] px-3.5 py-2 rounded-xl border border-[#DCE5DD] bg-white text-[#172923]">
                        </div>

                        <button type="submit"
                                class="w-full sm:w-auto min-h-[46px] px-5 py-2.5 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white font-bold text-xs cursor-pointer smooth-transition">
                            Konfirmasi Wadah Berhasil Diambil
                        </button>
                    </form>
                </div>
            @empty
                <div class="luxury-surface p-8 text-center text-xs text-[#53645B] space-y-1">
                    <p class="font-bold text-sm text-[#172923]">Tidak Ada Antrean Penjemputan</p>
                    <p>Belum ada sekolah yang menandai wadah ompreng siap dijemput saat ini.</p>
                </div>
            @endforelse
        </section>

        <!-- Completed History -->
        <x-card title="Riwayat Pengambilan Wadah" badge="{{ $completedPickups->total() }} Selesai">
            @if ($completedPickups->isEmpty())
                <p class="py-4 text-center text-xs text-[#53645B]">Belum ada riwayat penjemputan sebelumnya.</p>
            @else
                <div class="divide-y divide-[#E7ECE8] -my-1">
                    @foreach ($completedPickups as $completed)
                        @php $school = $completed->schoolServiceRecord?->tripStop?->school; @endphp
                        <div class="py-3.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs">
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-bold text-[#172923] text-sm">{{ $school?->name ?? 'Sekolah MBG' }}</span>
                                    <span class="text-[10px] px-2 py-0.5 rounded-md font-semibold border {{ $completed->status->badgeClass() }}">
                                        {{ $completed->status->label() }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-[#53645B] tabular-nums">
                                    Diambil: <strong>{{ $completed->picked_up_count ?? 0 }} wadah</strong> • {{ $completed->picked_up_at ? $completed->picked_up_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') : '-' }}
                                    @if ($completed->returned_count !== null)
                                        • Tiba SPPG: <strong>{{ $completed->returned_count }}</strong>
                                    @endif
                                </p>
                            </div>

                            @if ($completed->evidencePhotos->isNotEmpty())
                                <div class="flex gap-1.5 shrink-0">
                                    @foreach ($completed->evidencePhotos as $photo)
                                        <a href="{{ route('evidence-photos.show', $photo) }}" target="_blank" rel="noopener"
                                           class="w-10 h-10 rounded-lg border border-[#DCE5DD] overflow-hidden bg-[#FAFBF9]">
                                            <img src="{{ route('evidence-photos.show', $photo) }}" alt="Bukti" class="w-full h-full object-cover">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if ($completedPickups->hasPages())
                    <div class="pt-4 mt-4 border-t border-[#E7ECE8]">
                        {{ $completedPickups->links() }}
                    </div>
                @endif
            @endif
        </x-card>
    </div>
</x-app-shell>
