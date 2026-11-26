<x-app-shell title="Tugas Perjalanan Saya">
    <div class="space-y-5">
        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 pb-4 border-b border-[#DCE5DD]">
            <div>
                <span class="eyebrow-label text-[#55327A] block mb-1">Kurir Pengantaran • {{ auth()->user()->sppg?->code ?? 'SPPG' }}</span>
                <h1 class="page-title">Tugas Perjalanan Pengantaran Saya</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-1">Penugasan rute sekolah, pemeriksaan armada, dan navigasi pengantaran aktif.</p>
            </div>
        </header>

        @if (session('success'))
            <div class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-[#1B573E] text-xs sm:text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif

        <!-- Active Trips -->
        <section class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="section-title">Tugas Aktif &amp; Siap Dijalankan</h2>
                @if ($activeTrips->isNotEmpty())
                    <span class="px-2.5 py-0.5 text-xs font-bold bg-[#1C6654] text-white rounded-lg tabular-nums">{{ $activeTrips->count() }}</span>
                @endif
            </div>

            @if ($activeTrips->isEmpty())
                <div class="luxury-surface p-8 text-center space-y-2">
                    <h3 class="text-sm font-bold text-[#172923]">Belum Ada Tugas Pengantaran Aktif</h3>
                    <p class="text-xs text-[#53645B] max-w-sm mx-auto">Anda belum memiliki penugasan rute baru saat ini. Penugasan dari Kepala SPPG akan muncul otomatis di halaman ini.</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($activeTrips as $trip)
                        <div class="luxury-surface p-5 space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-[#E7ECE8]">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-mono font-bold text-[#172923]">{{ $trip->trip_code }}</span>
                                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-lg border {{ $trip->status->badgeClass() }}">
                                            {{ $trip->status->label() }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-[#53645B] mt-1">
                                        {{ $trip->service_date->translatedFormat('l, d F Y') }} • Rencana {{ $trip->planned_departure_at ? $trip->planned_departure_at->format('H:i') . ' WIB' : 'Segera' }}
                                    </p>
                                </div>

                                <div class="sm:text-right text-xs">
                                    <span class="font-bold text-[#172923] block">{{ $trip->vehicle->plate_number }} ({{ $trip->vehicle->vehicle_type }})</span>
                                    @if ($trip->vehicle->vehicle_name)
                                        <span class="text-[11px] text-[#53645B]">{{ $trip->vehicle->vehicle_name }}</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Stops -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-xs text-[#53645B]">
                                    <span>Tujuan ({{ $trip->stops->count() }} Sekolah)</span>
                                    <span class="font-bold text-[#172923] tabular-nums">{{ $trip->total_loaded_portions }} Porsi</span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                    @foreach ($trip->stops as $stop)
                                        <div class="inset-surface p-3 flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span class="w-6 h-6 rounded-full bg-[#1C6654] text-white text-xs font-bold flex items-center justify-center shrink-0 tabular-nums">
                                                    {{ $stop->sequence_number }}
                                                </span>
                                                <div class="min-w-0">
                                                    <span class="text-xs font-bold text-[#172923] block truncate">{{ $stop->school->name }}</span>
                                                    <span class="text-[10px] text-[#53645B] tabular-nums">{{ $stop->total_loaded_portions }} porsi</span>
                                                </div>
                                            </div>
                                            <span class="text-[10px] px-1.5 py-0.5 rounded border shrink-0 {{ $stop->status->badgeClass() }}">
                                                {{ $stop->status->label() }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Primary CTA -->
                            <div class="pt-3 border-t border-[#E7ECE8] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <span class="text-xs font-medium {{ $trip->vehicle_check_passed === true ? 'text-[#21684B]' : ($trip->vehicle_check_passed === false ? 'text-[#9B3232]' : 'text-[#885D1C]') }}">
                                    @if ($trip->vehicle_check_passed === true)
                                        Armada telah terverifikasi laik jalan
                                    @elseif ($trip->vehicle_check_passed === false)
                                        Pemeriksaan mencatat kendala armada
                                    @else
                                        Wajib cek kelaikan kendaraan sebelum berangkat
                                    @endif
                                </span>

                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                    @if (in_array($trip->status->value, ['ASSIGNED', 'VEHICLE_CHECKED', 'READY']))
                                        <a href="{{ route('driver.trips.prepare', $trip) }}"
                                           class="min-h-[46px] px-5 py-2.5 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs font-bold inline-flex items-center justify-center gap-2 smooth-transition">
                                            <span>Persiapan &amp; Checklist Armada</span>
                                            <span aria-hidden="true">&rarr;</span>
                                        </a>
                                    @elseif ($trip->status->value === 'IN_PROGRESS')
                                        <a href="{{ route('driver.trips.active', $trip) }}"
                                           class="min-h-[46px] px-5 py-2.5 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs font-bold inline-flex items-center justify-center gap-2 smooth-transition">
                                            <span>Buka Navigasi Rute Aktif</span>
                                            <span aria-hidden="true">&rarr;</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <!-- Completed History -->
        @if ($completedTrips->isNotEmpty())
            <x-card title="Riwayat Perjalanan Selesai" badge="{{ $completedTrips->count() }} Rute">
                <div class="divide-y divide-[#E7ECE8] -my-1">
                    @foreach ($completedTrips as $trip)
                        <div class="py-3.5 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-mono font-bold text-[#172923]">{{ $trip->trip_code }}</span>
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md border {{ $trip->status->badgeClass() }}">
                                        {{ $trip->status->label() }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-[#53645B] mt-0.5 tabular-nums">
                                    {{ $trip->service_date->translatedFormat('d M Y') }} • {{ $trip->vehicle->plate_number }} • {{ $trip->stops->count() }} sekolah
                                </p>
                            </div>
                            <a href="{{ route('trips.show', $trip) }}" class="min-h-[40px] px-3 py-1.5 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] text-xs font-semibold text-[#1C6654] inline-flex items-center shrink-0">
                                Detail
                            </a>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif
    </div>
</x-app-shell>
