<x-app-shell title="Logistik & Rekonsiliasi Wadah">
    <div class="space-y-5">
        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <span class="eyebrow-label text-[#885D1C] block mb-1">Asisten Lapangan • {{ $user->sppg?->code ?? 'SPPG' }}</span>
                <h1 class="page-title">Penerimaan & Rekonsiliasi Ompreng SPPG</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-1">Pemeriksaan fisik wadah kembali dari kurir sebelum masuk lini pencucian dapur.</p>
            </div>

            <a href="{{ route('incidents.index') }}"
               class="min-h-[44px] px-3.5 py-2 rounded-xl border border-[#DCE5DD] bg-white hover:bg-[#FAFBF9] text-xs font-semibold text-[#172923] inline-flex items-center gap-1.5 shrink-0 smooth-transition">
                <span>Pusat Kendala</span>
            </a>
        </header>

        <details class="sop-disclosure">
            <summary>
                <span>Panduan Rekonsiliasi Wadah Tiba di Dapur SPPG</span>
                <svg class="w-4 h-4 text-[#53645B] disclosure-chevron shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>
            <div class="p-4 text-xs text-[#53645B] leading-relaxed">
                Saat kurir tiba kembali di SPPG, hitung fisik ompreng dan pisahkan wadah <strong>Baik</strong>, <strong>Rusak</strong>, serta <strong>Hilang</strong>. Selisih terhadap hitungan kurir dicatat otomatis sebagai insiden tanpa mengubah angka asli kurir.
            </div>
        </details>

        @if (session('success'))
            <div class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-xs sm:text-sm font-medium text-[#1B573E]">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-xl bg-[#FDF2F2] border border-[#F5C2C2] text-xs text-[#9B3232] space-y-1">
                <p class="font-bold">Gagal memproses rekonsiliasi:</p>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Incoming Queue -->
        <section class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="eyebrow-label text-[#172923]">Wadah Masuk Menunggu Pemeriksaan ({{ count($incomingReturns) }})</h2>
            </div>

            @forelse ($incomingReturns as $incoming)
                @php
                    $school = $incoming->schoolServiceRecord?->tripStop?->school;
                    $origTrip = $incoming->schoolServiceRecord?->tripStop?->trip;
                @endphp

                <div class="luxury-surface p-5 space-y-4 border-l-4 border-l-[#1C6654]">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 pb-3 border-b border-[#E7ECE8]">
                        <div>
                            <h3 class="text-base font-bold text-[#172923]">{{ $school?->name ?? 'Sekolah MBG' }}</h3>
                            <p class="text-xs text-[#53645B]">{{ $school?->address ?? '-' }}</p>
                        </div>
                        <div class="sm:text-right text-xs font-mono text-[#53645B]">
                            <span class="font-bold text-[#172923] block">Wadah #{{ $incoming->id }}</span>
                            <span>Trip: {{ $origTrip?->trip_code ?? '-' }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                        <div class="inset-surface p-3">
                            <span class="text-[10px] text-[#53645B] block">Ekspektasi Kirim</span>
                            <span class="text-base font-bold text-[#172923] tabular-nums mt-0.5 block">{{ $incoming->expected_trays ?? '-' }}</span>
                        </div>
                        <div class="inset-surface p-3">
                            <span class="text-[10px] text-[#53645B] block">Siap Sekolah</span>
                            <span class="text-base font-bold text-[#172923] tabular-nums mt-0.5 block">{{ $incoming->ready_count ?? 0 }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0]">
                            <span class="text-[10px] text-[#21684B] font-semibold block">Diambil Kurir</span>
                            <span class="text-base font-bold text-[#1B573E] tabular-nums mt-0.5 block">{{ $incoming->picked_up_count ?? 0 }}</span>
                        </div>
                        <div class="inset-surface p-3">
                            <span class="text-[10px] text-[#53645B] block">Waktu Jemput</span>
                            <span class="text-xs font-bold text-[#172923] tabular-nums mt-1 block">
                                {{ $incoming->picked_up_at ? $incoming->picked_up_at->setTimezone('Asia/Jakarta')->format('H:i') . ' WIB' : '-' }}
                            </span>
                        </div>
                    </div>

                    @if ($incoming->notes)
                        <div class="inset-surface p-3 text-xs text-[#53645B]">
                            <strong class="text-[#172923]">Catatan Kurir:</strong> {{ $incoming->notes }}
                        </div>
                    @endif

                    <form action="{{ route('production.tray-returns.receive', $incoming) }}" method="POST" enctype="multipart/form-data" class="inset-surface p-4 space-y-3.5">
                        @csrf
                        <span class="text-xs font-bold text-[#172923] block">Input Hasil Hitung Fisik di Dapur SPPG</span>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                            <div>
                                <label for="returned_{{ $incoming->id }}" class="block font-semibold text-[#172923] mb-1">
                                    Kondisi Baik <span class="text-[#9B3232]">*</span>
                                </label>
                                <input type="number"
                                       id="returned_{{ $incoming->id }}"
                                       name="returned_count"
                                       min="0"
                                       value="{{ old('returned_count', $incoming->picked_up_count ?? $incoming->expected_trays) }}"
                                       required
                                       class="w-full min-h-[44px] px-3 py-2 rounded-xl border border-[#DCE5DD] bg-white text-[#172923] font-bold text-sm tabular-nums">
                            </div>
                            <div>
                                <label for="damaged_{{ $incoming->id }}" class="block font-semibold text-[#172923] mb-1">Rusak / Pecah</label>
                                <input type="number"
                                       id="damaged_{{ $incoming->id }}"
                                       name="damaged_count"
                                       min="0"
                                       value="{{ old('damaged_count', (int)$incoming->damaged_count) }}"
                                       class="w-full min-h-[44px] px-3 py-2 rounded-xl border border-[#DCE5DD] bg-white text-[#172923] text-sm tabular-nums">
                            </div>
                            <div>
                                <label for="missing_{{ $incoming->id }}" class="block font-semibold text-[#172923] mb-1">Hilang</label>
                                <input type="number"
                                       id="missing_{{ $incoming->id }}"
                                       name="missing_count"
                                       min="0"
                                       value="{{ old('missing_count', 0) }}"
                                       class="w-full min-h-[44px] px-3 py-2 rounded-xl border border-[#DCE5DD] bg-white text-[#172923] text-sm tabular-nums">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div>
                                <label for="receive_notes_{{ $incoming->id }}" class="block font-semibold text-[#172923] mb-1">Catatan Pemeriksaan</label>
                                <input type="text"
                                       id="receive_notes_{{ $incoming->id }}"
                                       name="notes"
                                       placeholder="Contoh: Diserahkan ke tim pencucian"
                                       class="w-full min-h-[44px] px-3 py-2 rounded-xl border border-[#DCE5DD] bg-white text-[#172923]">
                            </div>
                            <div>
                                <label for="receive_photo_{{ $incoming->id }}" class="block font-semibold text-[#172923] mb-1">Foto Bukti (Opsional)</label>
                                <input type="file"
                                       id="receive_photo_{{ $incoming->id }}"
                                       name="photo"
                                       accept="image/*"
                                       class="w-full text-xs text-[#53645B] file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-[#1C6654] file:text-white">
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full sm:w-auto min-h-[44px] px-5 py-2.5 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white font-bold text-xs cursor-pointer smooth-transition">
                            Simpan Rekonsiliasi Depot
                        </button>
                    </form>
                </div>
            @empty
                <div class="luxury-surface p-8 text-center text-xs text-[#53645B] space-y-1">
                    <p class="font-bold text-sm text-[#172923]">Tidak Ada Wadah Menunggu Pemeriksaan</p>
                    <p>Seluruh ompreng yang dijemput kurir telah selesai direkonsiliasi di SPPG.</p>
                </div>
            @endforelse
        </section>

        <!-- Completed Reconciliation Ledger (Dual Render) -->
        <x-card title="Riwayat Rekonsiliasi Ompreng" badge="{{ $completedReturns->total() }} Catatan">
            @if ($completedReturns->isEmpty())
                <p class="py-6 text-center text-xs text-[#53645B]">Belum ada rekonsiliasi yang diselesaikan.</p>
            @else
                <!-- Desktop Table -->
                <div class="hidden md:block -mx-6 -mb-6 overflow-x-auto">
                    <table class="ledger-table text-xs">
                        <thead>
                            <tr>
                                <th>Sekolah</th>
                                <th>Status</th>
                                <th class="text-right">Kirim</th>
                                <th class="text-right">Diambil</th>
                                <th class="text-right">Kembali Baik</th>
                                <th class="text-right">Rusak / Hilang</th>
                                <th>Waktu</th>
                                <th class="text-right">Tautan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($completedReturns as $record)
                                @php $school = $record->schoolServiceRecord?->tripStop?->school; @endphp
                                <tr>
                                    <td>
                                        <span class="font-semibold text-[#172923] block">{{ $school?->name ?? 'Sekolah MBG' }}</span>
                                        <span class="font-mono text-[11px] text-[#53645B]">Wadah #{{ $record->id }}</span>
                                    </td>
                                    <td>
                                        <span class="text-[10px] px-2 py-0.5 rounded-md font-semibold border {{ $record->status->badgeClass() }}">
                                            {{ $record->status->label() }}
                                        </span>
                                    </td>
                                    <td class="text-right font-mono tabular-nums">{{ $record->expected_trays }}</td>
                                    <td class="text-right font-mono tabular-nums">{{ $record->picked_up_count ?? '-' }}</td>
                                    <td class="text-right font-mono font-bold text-[#21684B] tabular-nums">{{ $record->returned_count ?? '-' }}</td>
                                    <td class="text-right font-mono tabular-nums {{ ($record->damaged_count > 0 || $record->missing_count > 0) ? 'text-[#9B3232] font-bold' : 'text-[#53645B]' }}">
                                        {{ (int)$record->damaged_count }} / {{ (int)$record->missing_count }}
                                    </td>
                                    <td class="text-[#53645B] font-mono text-[11px] tabular-nums">
                                        {{ $record->returned_at ? $record->returned_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') : '-' }}
                                    </td>
                                    <td class="text-right">
                                        @if ($record->incidents->isNotEmpty())
                                            <a href="{{ route('incidents.show', $record->incidents->first()) }}"
                                               class="px-2.5 py-1 rounded-lg bg-[#FDF2F2] text-[#9B3232] border border-[#F5C2C2] text-[11px] font-semibold">
                                                Lihat Insiden
                                            </a>
                                        @else
                                            <span class="text-[11px] text-[#21684B] font-semibold">Sesuai</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden divide-y divide-[#E7ECE8] -my-1">
                    @foreach ($completedReturns as $record)
                        @php $school = $record->schoolServiceRecord?->tripStop?->school; @endphp
                        <div class="py-3.5 space-y-2 text-xs">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="text-sm font-bold text-[#172923] block">{{ $school?->name ?? 'Sekolah MBG' }}</span>
                                    <span class="text-[11px] font-mono text-[#53645B]">Wadah #{{ $record->id }} • {{ $record->returned_at ? $record->returned_at->setTimezone('Asia/Jakarta')->format('d/m H:i') : '-' }}</span>
                                </div>
                                <span class="shrink-0 text-[10px] px-2 py-0.5 rounded-md font-semibold border {{ $record->status->badgeClass() }}">
                                    {{ $record->status->label() }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-[#53645B] tabular-nums">
                                <span>Kembali baik: <strong class="text-[#21684B]">{{ $record->returned_count ?? '-' }}</strong> / {{ $record->expected_trays }}</span>
                                @if ($record->incidents->isNotEmpty())
                                    <a href="{{ route('incidents.show', $record->incidents->first()) }}" class="text-[#9B3232] font-semibold hover:underline">Lihat Insiden &rarr;</a>
                                @else
                                    <span>Rusak/Hilang: {{ (int)$record->damaged_count }}/{{ (int)$record->missing_count }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($completedReturns->hasPages())
                    <div class="pt-4 mt-4 border-t border-[#E7ECE8]">
                        {{ $completedReturns->links() }}
                    </div>
                @endif
            @endif
        </x-card>
    </div>
</x-app-shell>
