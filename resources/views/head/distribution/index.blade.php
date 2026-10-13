<x-app-shell title="Kontrol Distribusi &amp; Penugasan Armada : MBG-Track">
    <!-- Header Operasional Kontrol Distribusi (Page K05) -->
    <header class="mb-6 pb-4 border-b border-[#DCE5DD]">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                    <x-role-badge :role="auth()->user()->role" />
                    <span class="text-xs font-mono text-[#53645B] bg-white border border-[#DCE5DD] px-2 py-0.5 rounded">
                        SPPG: {{ auth()->user()->sppg?->code ?? 'SPPG-'.auth()->user()->sppg_id }}
                    </span>
                    <span class="text-xs font-bold px-2 py-0.5 rounded border bg-[#EDF7F2] text-[#1B573E] border-[#BCE2D0]">
                        Logistik PBI-08
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">
                    Kontrol Distribusi, Armada &amp; Rute Pengantaran
                </h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">
                    Penjadwalan pengiriman multi-sekolah berbasis batch makanan berstatus RELEASED dan kapasitas armada yang sah.
                </p>
            </div>

            <!-- Pemilih Tanggal Layanan -->
            <form method="GET" action="{{ route('head.distribution.index') }}" class="flex items-center gap-2">
                <label for="service_date" class="text-xs font-semibold text-[#53645B] whitespace-nowrap">Tanggal Layanan:</label>
                <input type="date" 
                       id="service_date" 
                       name="service_date" 
                       value="{{ $service_date }}"
                       onchange="this.form.submit()"
                       class="px-3 py-1.5 text-xs font-medium text-[#172923] bg-white border border-[#DCE5DD] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1C6654] touch-target">
            </form>
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

    @if ($errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-[#FDF2F2] border border-[#F5C2C2] text-[#9B3232] text-xs sm:text-sm">
            <div class="font-bold mb-1 flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <span>Terdapat kendala validasi integritas pengiriman:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 ml-1">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Ringkasan Kesiapan Logistik Operasional -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <div class="p-4 rounded-2xl bg-white border border-[#DCE5DD]">
            <span class="text-[11px] font-semibold text-[#53645B] block uppercase tracking-wider">Armada Tersedia</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold text-[#172923]">{{ $vehicles->where('is_busy', false)->count() }}</span>
                <span class="text-xs text-[#53645B]">dari {{ $vehicles->count() }} unit</span>
            </div>
            <span class="text-[11px] text-[#708177] mt-1 block">Kendaraan aktif terdaftar</span>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-[#DCE5DD]">
            <span class="text-[11px] font-semibold text-[#53645B] block uppercase tracking-wider">Kurir Tersedia</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold text-[#172923]">{{ $drivers->where('is_busy', false)->count() }}</span>
                <span class="text-xs text-[#53645B]">dari {{ $drivers->count() }} petugas</span>
            </div>
            <span class="text-[11px] text-[#708177] mt-1 block">Kurir siap penugasan</span>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-[#DCE5DD]">
            <span class="text-[11px] font-semibold text-[#53645B] block uppercase tracking-wider">Alokasi Siap Muat</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold text-[#1B573E]">{{ $unassigned_delivery_items->sum('released_portions') }}</span>
                <span class="text-xs text-[#53645B]">porsi</span>
            </div>
            <span class="text-[11px] text-[#708177] mt-1 block">{{ $unassigned_delivery_items->count() }} paket alokasi sekolah</span>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-[#DCE5DD]">
            <span class="text-[11px] font-semibold text-[#53645B] block uppercase tracking-wider">Perjalanan Hari Ini</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold text-[#285F80]">{{ $trips->count() }}</span>
                <span class="text-xs text-[#53645B]">perjalanan</span>
            </div>
            <span class="text-[11px] text-[#708177] mt-1 block">{{ $trips->where('status.value', 'IN_PROGRESS')->count() }} sedang berjalan</span>
        </div>
    </div>

    <!-- Layout Dua Kolom: Form Penugasan & Daftar Trip Berjalan -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Kolom Kiri (7 Kolom): Perencana Penugasan Perjalanan Baru -->
        <div class="lg:col-span-7 space-y-6">
            <div class="p-5 rounded-2xl bg-white border border-[#DCE5DD]">
                <div class="flex items-center justify-between pb-3 border-b border-[#DCE5DD] mb-4">
                    <div>
                        <h2 class="text-base font-bold text-[#172923]">Form Penugasan Rute &amp; Muatan Baru</h2>
                        <p class="text-xs text-[#53645B]">Pilih armada, kurir, dan susun sekolah tujuan dengan kuantitas sah.</p>
                    </div>
                    <span class="px-2 py-0.5 text-[11px] font-bold bg-[#EDF2ED] text-[#172923] border border-[#DCE5DD] rounded">
                        PBI-08
                    </span>
                </div>

                @if ($unassigned_delivery_items->isEmpty())
                    <div class="p-6 rounded-xl bg-[#FAFBF9] border border-dashed border-[#DCE5DD] text-center">
                        <svg class="w-8 h-8 mx-auto text-[#708177] mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <h3 class="text-sm font-bold text-[#172923]">Tidak Ada Alokasi Siap Muat</h3>
                        <p class="text-xs text-[#53645B] max-w-md mx-auto mt-1">
                            Semua alokasi makanan pada tanggal ini sudah dijadwalkan pada perjalanan aktif, atau belum ada batch makanan yang disetujui inspeksi mutu (RELEASED).
                        </p>
                    </div>
                @else
                    <form method="POST" action="{{ route('head.distribution.trips.store') }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="service_date" value="{{ $service_date }}">

                        <!-- Baris Armada & Kurir -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="vehicle_id" class="block text-xs font-bold text-[#172923] mb-1">
                                    Armada Kendaraan <span class="text-red-500">*</span>
                                </label>
                                <select id="vehicle_id" 
                                        name="vehicle_id" 
                                        required 
                                        class="w-full px-3 py-2 text-xs font-medium text-[#172923] bg-white border border-[#DCE5DD] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1C6654] touch-target">
                                    <option value="">-- Pilih Kendaraan Tersedia --</option>
                                    @foreach ($vehicles as $v)
                                        <option value="{{ $v->id }}" {{ $v->is_busy ? 'disabled' : '' }}>
                                            {{ $v->plate_number }} ({{ $v->vehicle_type }}) : Kapasitas {{ $v->capacity_portions ?? 'Tak terbatas' }} porsi {{ $v->is_busy ? '[Sedang Bertugas]' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="driver_id" class="block text-xs font-bold text-[#172923] mb-1">
                                    Kurir yang Ditugaskan <span class="text-red-500">*</span>
                                </label>
                                <select id="driver_id" 
                                        name="driver_id" 
                                        required 
                                        class="w-full px-3 py-2 text-xs font-medium text-[#172923] bg-white border border-[#DCE5DD] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1C6654] touch-target">
                                    <option value="">-- Pilih Kurir Aktif --</option>
                                    @foreach ($drivers as $d)
                                        <option value="{{ $d->id }}" {{ $d->is_busy ? 'disabled' : '' }}>
                                            {{ $d->name }} {{ $d->phone ? '('.$d->phone.')' : '' }} {{ $d->is_busy ? '[Sedang Bertugas]' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Rencana Waktu Keberangkatan -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="planned_departure_at" class="block text-xs font-bold text-[#172923] mb-1">
                                    Waktu Keberangkatan Rencana
                                </label>
                                <input type="datetime-local" 
                                       id="planned_departure_at" 
                                       name="planned_departure_at" 
                                       value="{{ \Carbon\Carbon::parse($service_date)->setTime(9, 30)->format('Y-m-d\TH:i') }}"
                                       class="w-full px-3 py-2 text-xs font-medium text-[#172923] bg-white border border-[#DCE5DD] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1C6654] touch-target">
                            </div>
                            <div>
                                <label for="trip_type" class="block text-xs font-bold text-[#172923] mb-1">
                                    Jenis Perjalanan
                                </label>
                                <select id="trip_type" 
                                        name="trip_type" 
                                        class="w-full px-3 py-2 text-xs font-medium text-[#172923] bg-white border border-[#DCE5DD] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1C6654] touch-target">
                                    <option value="DELIVERY">Pengantaran Makanan (DELIVERY)</option>
                                    <option value="PICKUP">Penjemputan Ompreng (PICKUP)</option>
                                    <option value="MIXED">Gabungan Antar &amp; Jemput (MIXED)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Rute Perhentian & Muatan Sekolah -->
                        <div class="mt-4 pt-3 border-t border-[#DCE5DD]">
                            <h3 class="text-xs font-bold text-[#172923] mb-2 uppercase tracking-wider">
                                Daftar Alokasi Sekolah Siap Ditugaskan:
                            </h3>
                            <div class="space-y-3">
                                @php
                                    // Group unassigned items by school
                                    $groupedBySchool = $unassigned_delivery_items->groupBy(function($item) {
                                        return $item->productionAllocation->school_id;
                                    });
                                    $seq = 1;
                                @endphp

                                @foreach ($groupedBySchool as $schoolId => $items)
                                    @php
                                        $school = $items->first()->productionAllocation->school;
                                        $totalSchoolPortions = $items->sum('released_portions');
                                    @endphp
                                    <div class="p-3 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD]">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-2">
                                                <span class="w-5 h-5 rounded-full bg-[#1C6654] text-white text-[10px] font-bold flex items-center justify-center">
                                                    {{ $seq }}
                                                </span>
                                                <span class="text-xs font-bold text-[#172923]">{{ $school->name }}</span>
                                            </div>
                                            <span class="text-xs font-semibold text-[#1B573E] bg-[#EDF7F2] px-2 py-0.5 rounded border border-[#BCE2D0]">
                                                {{ $totalSchoolPortions }} porsi siap
                                            </span>
                                        </div>

                                        <input type="hidden" name="stops[{{ $seq - 1 }}][school_id]" value="{{ $school->id }}">
                                        <input type="hidden" name="stops[{{ $seq - 1 }}][sequence_number]" value="{{ $seq }}">
                                        <input type="hidden" name="stops[{{ $seq - 1 }}][stop_type]" value="DELIVERY">

                                        <!-- Rincian Batch per Item -->
                                        <div class="space-y-2 mt-2 pt-2 border-t border-[#E8EEE9]">
                                            @foreach ($items as $itemIdx => $item)
                                                <div class="flex items-center justify-between text-[11px] text-[#53645B] pl-2">
                                                    <div>
                                                        <span>Batch: <strong>{{ $item->foodBatch->batch_code }}</strong></span>
                                                        <span class="text-[#708177]">({{ $item->foodBatch->recipeMenu?->name ?? 'Menu' }})</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <span>Muat:</span>
                                                        <input type="hidden" name="stops[{{ $seq - 1 }}][items][{{ $itemIdx }}][delivery_item_id]" value="{{ $item->id }}">
                                                        <input type="number" 
                                                               name="stops[{{ $seq - 1 }}][items][{{ $itemIdx }}][loaded_portions]" 
                                                               value="{{ $item->released_portions ?? $item->planned_portions }}"
                                                               max="{{ $item->released_portions ?? $item->planned_portions }}"
                                                               min="1"
                                                               required
                                                               class="w-20 px-2 py-1 text-xs font-bold text-right text-[#172923] bg-white border border-[#DCE5DD] rounded-lg focus:outline-none focus:ring-1 focus:ring-[#1C6654]">
                                                        <span class="text-[11px] text-[#708177]">/ {{ $item->released_portions }} porsi</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @php $seq++; @endphp
                                @endforeach
                            </div>
                        </div>

                        <!-- Tombol Submit Terbitkan Trip -->
                        <div class="pt-3 border-t border-[#DCE5DD] flex justify-end">
                            <button type="submit" 
                                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs font-bold shadow-xs transition-colors touch-target cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Terbitkan Penugasan Perjalanan</span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        <!-- Kolom Kanan (5 Kolom): Daftar Perjalanan Aktif & Terjadwal Hari Ini -->
        <div class="lg:col-span-5 space-y-4">
            <div class="p-5 rounded-2xl bg-white border border-[#DCE5DD]">
                <div class="flex items-center justify-between pb-3 border-b border-[#DCE5DD] mb-4">
                    <h2 class="text-base font-bold text-[#172923]">Perjalanan Hari Ini</h2>
                    <span class="text-xs text-[#53645B] font-mono">{{ $trips->count() }} Terdaftar</span>
                </div>

                @if ($trips->isEmpty())
                    <div class="p-6 rounded-xl bg-[#FAFBF9] border border-dashed border-[#DCE5DD] text-center">
                        <p class="text-xs text-[#53645B]">
                            Belum ada jadwal perjalanan pengantaran yang diterbitkan untuk tanggal {{ \Carbon\Carbon::parse($service_date)->translatedFormat('d F Y') }}.
                        </p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($trips as $trip)
                            <div class="p-4 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] hover:border-[#BCE2D0] transition-colors">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <div>
                                        <span class="text-xs font-mono font-bold text-[#172923] block">{{ $trip->trip_code }}</span>
                                        <span class="text-[11px] text-[#53645B]">
                                            Kurir: <strong>{{ $trip->driver->name }}</strong> : {{ $trip->vehicle->plate_number }}
                                        </span>
                                    </div>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded border {{ $trip->status->badgeClass() }}">
                                        {{ $trip->status->label() }}
                                    </span>
                                </div>

                                <!-- Ringkasan Stop Rute -->
                                <div class="mt-3 pt-2 border-t border-[#E8EEE9] space-y-1.5">
                                    <div class="text-[11px] font-semibold text-[#53645B] flex justify-between">
                                        <span>Rute Perhentian ({{ $trip->stops->count() }} Sekolah):</span>
                                        <span class="font-bold text-[#172923]">{{ $trip->total_loaded_portions }} Porsi</span>
                                    </div>
                                    <div class="space-y-1">
                                        @foreach ($trip->stops as $stop)
                                            <div class="flex items-center justify-between text-[11px] pl-2 border-l-2 border-[#1C6654]">
                                                <span class="text-[#172923] truncate max-w-[180px]">
                                                    {{ $stop->sequence_number }}. {{ $stop->school->name }}
                                                </span>
                                                <span class="text-[10px] px-1.5 py-0.2 rounded border {{ $stop->status->badgeClass() }}">
                                                    {{ $stop->status->label() }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Tombol Aksi Detail & Batal -->
                                <div class="mt-4 pt-2 border-t border-[#E8EEE9] flex items-center justify-between gap-2">
                                    <a href="{{ route('trips.show', $trip) }}" 
                                       class="text-xs font-bold text-[#1C6654] hover:underline flex items-center gap-1 touch-target">
                                        <span>Buka Detail (C03)</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>

                                    @if (in_array($trip->status->value, ['DRAFT', 'ASSIGNED', 'VEHICLE_CHECKED', 'READY']))
                                        <form method="POST" action="{{ route('head.distribution.trips.cancel', $trip) }}" 
                                              onsubmit="return confirm('Apakah Anda yakin ingin membatalkan perjalanan ini? Alokasi makanan akan dikembalikan ke status siap muat.')">
                                            @csrf
                                            <input type="hidden" name="reason" value="Dibatalkan oleh Kepala SPPG sebelum keberangkatan">
                                            <button type="submit" 
                                                    class="text-[11px] font-semibold text-[#9B3232] hover:text-[#7A2424] hover:underline touch-target cursor-pointer">
                                                Batalkan
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-shell>
