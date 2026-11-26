<x-app-shell title="Beranda Misi Kurir">
    <div class="space-y-5">
        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 pb-4 border-b border-[#DCE5DD]">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <span class="eyebrow-label text-[#55327A]">Kurir Pengantaran • {{ $sppg->code }}</span>
                    @if (($activeTrips ?? collect())->isNotEmpty())
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-[#EDF7F2] text-[#21684B] font-semibold text-[11px] border border-[#BCE2D0]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#21684B]" aria-hidden="true"></span>
                            <span>MISI AKTIF</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-[#F4F0F7] text-[#55327A] font-semibold text-[11px] border border-[#D8C7EA]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#55327A]" aria-hidden="true"></span>
                            <span>PANGKALAN STANDBY</span>
                        </span>
                    @endif
                </div>
                <h1 class="page-title">Siaga Distribusi &amp; Rute Sekolah</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-1">{{ $sppg->name }} • Utamakan keselamatan, operasikan aplikasi hanya saat kendaraan berhenti sempurna.</p>
            </div>
        </header>

        <!-- Primary Thumb-First Action Cards -->
        @if (isset($activeTrips) && $activeTrips->isNotEmpty())
            <section aria-label="Misi Perjalanan Aktif" class="space-y-3">
                @foreach ($activeTrips as $trip)
                    <div class="luxury-surface p-5 border-2 border-[#1C6654] space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <span class="text-xs font-mono font-bold text-[#1C6654] block">{{ $trip->trip_code }}</span>
                                <h2 class="text-base sm:text-lg font-bold text-[#172923] mt-0.5">
                                    {{ $trip->stops->count() }} Perhentian Sekolah • {{ $trip->vehicle?->plate_number }}
                                </h2>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold border {{ $trip->status->badgeClass() }}">
                                {{ $trip->status->label() }}
                            </span>
                        </div>

                        <a href="{{ $trip->status->value === 'IN_PROGRESS' ? route('driver.trips.active', $trip) : route('driver.trips.prepare', $trip) }}"
                           class="w-full min-h-[48px] px-5 py-3 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs sm:text-sm font-bold inline-flex items-center justify-center gap-2 smooth-transition">
                            <span>{{ $trip->status->value === 'IN_PROGRESS' ? 'Lanjutkan Navigasi Rute Aktif' : 'Buka Checklist & Persiapan Armada' }}</span>
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                @endforeach
            </section>
        @endif

        <!-- Quick Operational Navigation Strip -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <a href="{{ route('driver.trips.index') }}" class="luxury-surface p-4 sm:p-5 hover:border-[#1C6654] smooth-transition flex items-center justify-between gap-3">
                <div>
                    <span class="eyebrow-label text-[#1C6654] block">Pengantaran Pagi</span>
                    <span class="text-base font-bold text-[#172923] mt-1 block">Daftar Tugas Perjalanan</span>
                    <span class="text-xs text-[#53645B] mt-0.5 block">Cek armada, navigasi GPS, &amp; bukti serah terima</span>
                </div>
                <span class="w-10 h-10 rounded-xl bg-[#EDF7F2] text-[#1C6654] flex items-center justify-center shrink-0 font-bold">&rarr;</span>
            </a>

            <a href="{{ route('driver.pickups.index') }}" class="luxury-surface p-4 sm:p-5 hover:border-[#55327A] smooth-transition flex items-center justify-between gap-3">
                <div>
                    <span class="eyebrow-label text-[#55327A] block">Logistik Balik Siang</span>
                    <span class="text-base font-bold text-[#172923] mt-1 block">Penjemputan Wadah Ompreng</span>
                    <span class="text-xs text-[#53645B] mt-0.5 block">
                        {{ ($pendingPickupsCount ?? 0) > 0 ? $pendingPickupsCount . ' sekolah siap dijemput' : 'Pantau wadah bersih siap angkut' }}
                    </span>
                </div>
                <span class="w-10 h-10 rounded-xl bg-[#F4F0F7] text-[#55327A] flex items-center justify-center shrink-0 font-bold">&rarr;</span>
            </a>
        </div>

        <!-- Territory Coverage -->
        <x-card title="Daftar Sekolah Wilayah Pelayanan" subtitle="Titik lokasi sekolah binaan {{ $sppg->code }} ({{ $totalStops }} sekolah • {{ number_format($totalPortions) }} siswa pokok).">
            @if ($stops->isEmpty())
                <div class="py-10 px-4 text-center">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-[#F4F0F7] text-[#55327A] flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-[#172923]">Belum Ada Titik Sekolah Terdaftar</h3>
                    <p class="text-xs text-[#53645B] mt-1 max-w-sm mx-auto leading-relaxed">
                        Belum ada sekolah binaan aktif dalam jangkauan unit {{ $sppg->code }}. Titik tujuan rute akan tampil otomatis setelah didaftarkan Kepala SPPG.
                    </p>
                </div>
            @else
                <div class="divide-y divide-[#E7ECE8] -my-1">
                    @foreach ($stops as $index => $stop)
                        <div class="py-3.5 flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 min-w-0">
                                <span class="w-7 h-7 rounded-lg bg-[#FAFBF9] border border-[#DCE5DD] text-[#172923] font-bold text-xs flex items-center justify-center shrink-0 mt-0.5 tabular-nums">
                                    {{ $index + 1 }}
                                </span>
                                <div class="min-w-0">
                                    <span class="font-bold text-sm text-[#172923] block leading-snug">{{ $stop->name }}</span>
                                    <span class="text-xs text-[#53645B] block mt-0.5">{{ $stop->address }}</span>
                                    <span class="text-[11px] font-mono text-[#53645B] block mt-0.5">{{ $stop->school_code }} • {{ $stop->education_level }}</span>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-sm font-bold text-[#172923] tabular-nums block">{{ number_format($stop->registered_beneficiaries) }}</span>
                                <span class="text-[10px] text-[#53645B]">siswa</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</x-app-shell>
