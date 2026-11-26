<x-app-shell title="Persiapan Perjalanan {{ $trip->trip_code }}">
    <div class="space-y-5">
        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 pb-4 border-b border-[#DCE5DD]">
            <div>
                <a href="{{ route('driver.trips.index') }}" class="text-xs font-semibold text-[#1C6654] hover:underline inline-flex items-center gap-1 mb-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Daftar Tugas Perjalanan</span>
                </a>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="page-title">Persiapan &amp; Checklist Armada</h1>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-lg border {{ $trip->status->badgeClass() }}">{{ $trip->status->label() }}</span>
                </div>
                <p class="text-xs text-[#53645B] mt-1">
                    <span class="font-mono font-semibold text-[#172923]">{{ $trip->trip_code }}</span> • {{ $trip->vehicle->plate_number }} ({{ $trip->vehicle->vehicle_type }}) • {{ $trip->service_date->translatedFormat('l, d F Y') }}
                </p>
            </div>
        </header>

        @if (session('success'))
            <div class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-[#1B573E] text-xs sm:text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
            <!-- Left: Vehicle Inspection & Start Gate (7 cols) -->
            <div class="lg:col-span-7 space-y-5">
                <x-card title="Pemeriksaan Fisik Kelaikan Armada" subtitle="Wajib diverifikasi sebelum kendaraan keluar dari dapur SPPG." badge="{{ $trip->vehicle->plate_number }}">
                    @if ($trip->vehicle_checked_at)
                        <div class="mb-4 p-3.5 rounded-xl border {{ $trip->vehicle_check_passed ? 'bg-[#EDF7F2] border-[#BCE2D0] text-[#1B573E]' : 'bg-[#FDF2F2] border-[#F5C2C2] text-[#9B3232]' }} text-xs space-y-0.5">
                            <p class="font-bold">{{ $trip->vehicle_check_passed ? 'Pemeriksaan Terakhir: Laik Beroperasi' : 'Pemeriksaan Terakhir: Terdapat Kendala Armada' }}</p>
                            <p class="text-[11px] tabular-nums">Tercatat pukul {{ $trip->vehicle_checked_at->format('H:i') }} WIB {{ $trip->vehicle_check_notes ? '• ' . $trip->vehicle_check_notes : '' }}</p>
                        </div>
                    @endif

                    @if ($trip->status->value === 'IN_PROGRESS' || $trip->status->value === 'COMPLETED')
                        <p class="p-4 inset-surface text-xs text-[#53645B]">Pemeriksaan kendaraan telah dikunci karena perjalanan sedang berjalan atau selesai.</p>
                    @else
                        <form method="POST" action="{{ route('driver.trips.vehicle-check', $trip) }}" class="space-y-4">
                            @csrf

                            <details class="sop-disclosure">
                                <summary>
                                    <span>4 Poin Pemeriksaan Mandiri Kurir</span>
                                    <svg class="w-4 h-4 text-[#53645B] disclosure-chevron shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </summary>
                                <ul class="p-4 text-xs text-[#53645B] space-y-1.5 list-disc list-inside">
                                    <li>Rem dan lampu kendaraan berfungsi normal.</li>
                                    <li>Tekanan angin ban mencukupi dan aman.</li>
                                    <li>Bahan bakar mencukupi untuk seluruh rute.</li>
                                    <li>Kompartemen makanan bersih, higienis, dan rapat.</li>
                                </ul>
                            </details>

                            <div>
                                <span class="block text-xs font-semibold text-[#172923] mb-2">Keputusan Kelaikan Armada <span class="text-[#9B3232]">*</span></span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <label class="p-3.5 rounded-xl border border-[#DCE5DD] bg-[#FAFBF9] cursor-pointer flex items-center gap-3 has-checked:border-[#1C6654] has-checked:bg-[#EDF7F2]">
                                        <input type="radio"
                                               name="passed"
                                               value="1"
                                               {{ old('passed', $trip->vehicle_check_passed ? '1' : '') === '1' ? 'checked' : '' }}
                                               required
                                               class="w-4 h-4 text-[#1C6654]">
                                        <div>
                                            <span class="text-xs font-bold text-[#172923] block">Armada Laik Jalan</span>
                                            <span class="text-[11px] text-[#53645B]">Aman dan siap berangkat</span>
                                        </div>
                                    </label>

                                    <label class="p-3.5 rounded-xl border border-[#DCE5DD] bg-[#FAFBF9] cursor-pointer flex items-center gap-3 has-checked:border-[#9B3232] has-checked:bg-[#FDF2F2]">
                                        <input type="radio"
                                               name="passed"
                                               value="0"
                                               {{ old('passed', $trip->vehicle_check_passed === false ? '0' : '') === '0' ? 'checked' : '' }}
                                               required
                                               class="w-4 h-4 text-[#9B3232]">
                                        <div>
                                            <span class="text-xs font-bold text-[#9B3232] block">Kendaraan Bermasalah</span>
                                            <span class="text-[11px] text-[#53645B]">Tidak laik jalan</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label for="notes" class="block text-xs font-semibold text-[#172923] mb-1">Catatan Pemeriksaan (Opsional)</label>
                                <textarea id="notes"
                                          name="notes"
                                          rows="2"
                                          placeholder="Contoh: Boks insulasi bersih, bahan bakar penuh."
                                          class="w-full px-3.5 py-2 text-xs text-[#172923] bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl">{{ old('notes', $trip->vehicle_check_notes) }}</textarea>
                            </div>

                            <button type="submit"
                                    class="w-full sm:w-auto min-h-[44px] px-5 py-2.5 rounded-xl bg-[#172923] hover:bg-[#1C6654] text-xs font-bold text-white cursor-pointer smooth-transition">
                                Simpan Hasil Pemeriksaan Kendaraan
                            </button>
                        </form>
                    @endif
                </x-card>

                <!-- Start Dispatch Gate -->
                <x-card title="Mulai Perjalanan Pengantaran" subtitle="Empat gerbang kesiapan sebelum keberangkatan resmi dicatat.">
                    <div class="mb-4 grid grid-cols-2 gap-2 text-xs">
                        <div class="inset-surface px-3 py-2 flex items-center justify-between">
                            <span class="text-[#53645B]">1. QC Gizi</span>
                            <span class="font-bold text-[#21684B]">Lolos</span>
                        </div>
                        <div class="inset-surface px-3 py-2 flex items-center justify-between">
                            <span class="text-[#53645B]">2. Muatan</span>
                            <span class="font-bold {{ $trip->loading_verified ? 'text-[#21684B]' : 'text-[#9B3232]' }}">{{ $trip->loading_verified ? 'Sah' : 'Menunggu' }}</span>
                        </div>
                        <div class="inset-surface px-3 py-2 flex items-center justify-between">
                            <span class="text-[#53645B]">3. Armada</span>
                            <span class="font-bold {{ $trip->vehicle_check_passed ? 'text-[#21684B]' : 'text-[#9B3232]' }}">{{ $trip->vehicle_check_passed ? 'Laik' : 'Belum' }}</span>
                        </div>
                        <div class="inset-surface px-3 py-2 flex items-center justify-between">
                            <span class="text-[#53645B]">4. Izin SPPG</span>
                            <span class="font-bold {{ $trip->dispatch_approved ? 'text-[#21684B]' : 'text-[#9B3232]' }}">{{ $trip->dispatch_approved ? 'Disetujui' : 'Menunggu' }}</span>
                        </div>
                    </div>

                    @if ($trip->status->value === 'IN_PROGRESS')
                        <a href="{{ route('driver.trips.active', $trip) }}"
                           class="w-full min-h-[48px] px-4 py-3 rounded-xl bg-[#1C6654] text-white text-xs sm:text-sm font-bold inline-flex items-center justify-center gap-2">
                            <span>Buka Navigasi Rute Aktif</span>
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    @elseif ($trip->status->value === 'READY')
                        <div class="space-y-3">
                            <p class="text-xs text-[#21684B] font-medium">Seluruh gerbang terpenuhi. Geser tombol di bawah untuk memulai perjalanan menuju sekolah pertama.</p>

                            <div id="driver-swipe-slider"
                                 class="relative w-full h-14 bg-[#FAFBF9] border-2 border-[#1C6654]/40 rounded-2xl overflow-hidden flex items-center select-none p-1"
                                 data-action-url="{{ route('driver.trips.start', $trip) }}">
                                <div class="swipe-fill absolute left-0 top-0 bottom-0 bg-[#EDF7F2] rounded-2xl transition-all pointer-events-none" style="width: 0px;"></div>
                                <div class="swipe-label w-full text-center text-xs font-bold text-[#1C6654] pointer-events-none z-1">
                                    Geser untuk mulai perjalanan &rarr;
                                </div>
                                <div class="swipe-thumb absolute left-1 top-1 bottom-1 w-12 bg-[#1C6654] text-white rounded-xl shadow-sm flex items-center justify-center cursor-grab active:cursor-grabbing z-2 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                                    </svg>
                                </div>
                                <button type="button" class="swipe-accessible-btn sr-only focus:not-sr-only focus:absolute focus:z-10 focus:p-2 focus:bg-white focus:text-[#1C6654] focus:rounded-xl">
                                    Mulai Perjalanan
                                </button>
                            </div>
                        </div>
                    @else
                        <p class="p-3.5 inset-surface text-xs text-[#53645B]">
                            Tombol mulai perjalanan akan aktif setelah pemeriksaan armada disimpan laik jalan, muatan diverifikasi, dan izin keberangkatan disahkan Kepala SPPG.
                        </p>
                    @endif
                </x-card>
            </div>

            <!-- Right: Map & Manifest (5 cols) -->
            <div class="lg:col-span-5 space-y-5">
                <x-card title="Peta Rute Pengantaran" compact>
                    <div id="driver-route-map" class="w-full h-56 sm:h-64 rounded-xl overflow-hidden border border-[#DCE5DD] bg-[#FAFBF9] mb-2.5"></div>
                    <div class="text-[11px] text-[#53645B] flex justify-between tabular-nums">
                        <span>{{ $trip->stops->count() }} perhentian</span>
                        <span>~{{ round(($trip->estimated_duration_s ?? 0) / 60) }} menit</span>
                    </div>
                </x-card>

                <x-card title="Manifes Rute &amp; Muatan" badge="{{ $trip->total_loaded_portions ?: $trip->stops->sum('planned_portions') }} Porsi">
                    <div class="space-y-3">
                        @foreach ($trip->stops as $stop)
                            <div class="inset-surface p-3.5 space-y-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="w-6 h-6 rounded-full bg-[#1C6654] text-white text-xs font-bold flex items-center justify-center shrink-0 tabular-nums">{{ $stop->sequence_number }}</span>
                                        <div class="min-w-0">
                                            <span class="text-xs font-bold text-[#172923] block truncate">{{ $stop->school->name }}</span>
                                            <span class="text-[11px] text-[#53645B] block truncate">{{ $stop->school->address }}</span>
                                        </div>
                                    </div>
                                    <span class="text-[10px] px-2 py-0.5 rounded border shrink-0 {{ $stop->status->badgeClass() }}">{{ $stop->status->label() }}</span>
                                </div>

                                @if ($stop->deliveryItems->isNotEmpty())
                                    <div class="pt-2 border-t border-[#E7ECE8] space-y-1 text-[11px]">
                                        @foreach ($stop->deliveryItems as $item)
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="font-mono text-[#53645B]">{{ $item->foodBatch->batch_code }}</span>
                                                <span class="font-bold text-[#21684B] tabular-nums">{{ $item->loaded_portions ?? $item->planned_portions }} porsi</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-card>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.initDispatchMap) {
                window.initDispatchMap('driver-route-map', {
                    dataUrl: '{{ route('trips.map-data', $trip) }}'
                });
            }
            if (window.initSwipeToDepart) {
                window.initSwipeToDepart('driver-swipe-slider');
            }
        });
    </script>
</x-app-shell>
