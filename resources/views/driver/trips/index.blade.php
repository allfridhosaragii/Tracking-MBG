<x-app-shell title="Tugas Pengantaran Saya : MBG-Track">
    <!-- Header Tugas Kurir (Page D01) -->
    <header class="mb-6 pb-4 border-b border-[#DCE5DD]">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                    <x-role-badge :role="auth()->user()->role" />
                    <span class="text-xs font-mono text-[#53645B] bg-white border border-[#DCE5DD] px-2 py-0.5 rounded">
                        SPPG: {{ auth()->user()->sppg?->code ?? 'SPPG-'.auth()->user()->sppg_id }}
                    </span>
                    <span class="text-xs font-bold px-2 py-0.5 rounded border bg-[#F3E8FF] text-[#55327A] border-[#D8B4FE]">
                        Logistik Kurir
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">
                    Tugas Perjalanan Pengantaran Saya
                </h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">
                    Daftar penugasan resmi rute sekolah dan pemeriksaan kelaikan armada sebelum keberangkatan.
                </p>
            </div>

            <!-- Petunjuk Keselamatan Berkendara -->
            <div class="p-3 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] text-[11px] text-[#53645B] max-w-xs">
                <span class="font-bold text-[#172923] block mb-0.5">Protokol Keselamatan:</span>
                Gunakan aplikasi hanya saat kendaraan berhenti sempurna. Utamakan keselamatan berkendara.
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

    <!-- 1. Bagian Tugas Aktif / Ditugaskan Hari Ini -->
    <section class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold text-[#172923] flex items-center gap-2">
                <span>Tugas Aktif &amp; Siap Dijalankan</span>
                @if ($activeTrips->isNotEmpty())
                    <span class="px-2 py-0.5 text-xs font-bold bg-[#1C6654] text-white rounded-full">
                        {{ $activeTrips->count() }}
                    </span>
                @endif
            </h2>
        </div>

        @if ($activeTrips->isEmpty())
            <div class="p-8 rounded-2xl bg-white border border-dashed border-[#DCE5DD] text-center">
                <svg class="w-12 h-12 mx-auto text-[#708177] mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="text-sm font-bold text-[#172923]">Belum Ada Tugas Pengantaran Aktif</h3>
                <p class="text-xs text-[#53645B] max-w-sm mx-auto mt-1">
                    Anda belum memiliki penugasan rute pengantaran baru saat ini. Silakan berkoordinasi dengan Kepala SPPG atau tunggu penugasan diterbitkan.
                </p>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($activeTrips as $trip)
                    <div class="p-5 rounded-2xl bg-white border border-[#DCE5DD] shadow-xs hover:border-[#BCE2D0] transition-colors">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-[#E8EEE9]">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-sm font-mono font-bold text-[#172923]">{{ $trip->trip_code }}</span>
                                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full border {{ $trip->status->badgeClass() }}">
                                        {{ $trip->status->label() }}
                                    </span>
                                </div>
                                <p class="text-xs text-[#53645B]">
                                    Layanan: <strong>{{ $trip->service_date->translatedFormat('l, d F Y') }}</strong> : Rencana Berangkat: <strong>{{ $trip->planned_departure_at ? $trip->planned_departure_at->format('H:i') . ' WIB' : 'Segera' }}</strong>
                                </p>
                            </div>

                            <!-- Detail Armada -->
                            <div class="sm:text-right">
                                <span class="text-xs font-bold text-[#172923] block">{{ $trip->vehicle->plate_number }} ({{ $trip->vehicle->vehicle_type }})</span>
                                <span class="text-[11px] text-[#53645B]">{{ $trip->vehicle->vehicle_name ?? 'Armada Operasional' }}</span>
                            </div>
                        </div>

                        <!-- Ringkasan Rute Perhentian Sekolah -->
                        <div class="py-4">
                            <div class="flex items-center justify-between text-xs font-semibold text-[#53645B] mb-2.5">
                                <span>Rute Tujuan ({{ $trip->stops->count() }} Sekolah):</span>
                                <span class="font-bold text-[#172923]">{{ $trip->total_loaded_portions }} Porsi Makanan</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5">
                                @foreach ($trip->stops as $stop)
                                    <div class="p-3 rounded-xl bg-[#FAFBF9] border border-[#E8EEE9] flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="w-6 h-6 rounded-full bg-[#1C6654] text-white text-xs font-bold flex items-center justify-center shrink-0">
                                                {{ $stop->sequence_number }}
                                            </span>
                                            <div>
                                                <span class="text-xs font-bold text-[#172923] block truncate max-w-[140px]">{{ $stop->school->name }}</span>
                                                <span class="text-[10px] text-[#53645B]">{{ $stop->total_loaded_portions }} porsi</span>
                                            </div>
                                        </div>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded border {{ $stop->status->badgeClass() }}">
                                            {{ $stop->status->label() }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Tombol Aksi Kurir Utama -->
                        <div class="pt-3 border-t border-[#E8EEE9] flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div class="text-xs text-[#53645B]">
                                @if ($trip->vehicle_check_passed === true)
                                    <span class="text-[#1B573E] font-semibold flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Pemeriksaan kelaikan armada telah lolos
                                    </span>
                                @elseif ($trip->vehicle_check_passed === false)
                                    <span class="text-[#9B3232] font-semibold flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        Pemeriksaan mencatat ketidaklayakan armada
                                    </span>
                                @else
                                    <span class="text-[#885D1C] font-semibold">
                                        Wajib lakukan checklist kelaikan kendaraan sebelum berangkat
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 w-full sm:w-auto">
                                @if (in_array($trip->status->value, ['ASSIGNED', 'VEHICLE_CHECKED', 'READY']))
                                    <a href="{{ route('driver.trips.prepare', $trip) }}" 
                                       class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs font-bold shadow-xs transition-colors touch-target">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                        </svg>
                                        <span>Persiapan &amp; Checklist Armada (D02)</span>
                                    </a>
                                @elseif ($trip->status->value === 'IN_PROGRESS')
                                    <a href="{{ route('trips.show', $trip) }}" 
                                       class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-[#285F80] hover:bg-[#1E4861] text-white text-xs font-bold shadow-xs transition-colors touch-target">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                        </svg>
                                        <span>Perjalanan Sedang Berlangsung (C03)</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <!-- 2. Bagian Riwayat Perjalanan Selesai -->
    @if ($completedTrips->isNotEmpty())
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-bold text-[#172923]">Riwayat Perjalanan Selesai</h2>
                <span class="text-xs text-[#53645B]">{{ $completedTrips->count() }} Perjalanan</span>
            </div>

            <div class="space-y-3">
                @foreach ($completedTrips as $trip)
                    <div class="p-4 rounded-xl bg-white border border-[#DCE5DD] flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-mono font-bold text-[#172923]">{{ $trip->trip_code }}</span>
                                <span class="text-[10px] font-bold px-2 py-0.2 rounded border {{ $trip->status->badgeClass() }}">
                                    {{ $trip->status->label() }}
                                </span>
                            </div>
                            <p class="text-[11px] text-[#53645B] mt-0.5">
                                {{ $trip->service_date->translatedFormat('d F Y') }} : {{ $trip->vehicle->plate_number }} : {{ $trip->stops->count() }} Sekolah ({{ $trip->total_loaded_portions }} porsi)
                            </p>
                        </div>
                        <a href="{{ route('trips.show', $trip) }}" class="text-xs font-bold text-[#1C6654] hover:underline touch-target">
                            Lihat Rute
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</x-app-shell>
