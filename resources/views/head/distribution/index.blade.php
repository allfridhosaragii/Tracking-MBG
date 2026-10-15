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
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 mb-6">
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
            <span class="text-[11px] text-[#708177] mt-1 block">{{ $unassigned_delivery_items->count() }} paket alokasi</span>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-[#DCE5DD]">
            <span class="text-[11px] font-semibold text-[#53645B] block uppercase tracking-wider">Perjalanan Hari Ini</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold text-[#285F80]">{{ $trips->count() }}</span>
                <span class="text-xs text-[#53645B]">perjalanan</span>
            </div>
            <span class="text-[11px] text-[#708177] mt-1 block">{{ $trips->where('status.value', 'IN_PROGRESS')->count() }} berjalan</span>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-[#DCE5DD] col-span-2 sm:col-span-1">
            <span class="text-[11px] font-semibold text-[#55327A] block uppercase tracking-wider">Ompreng Siap Jemput</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold text-[#55327A]">{{ $ready_tray_returns->where('status.value', 'READY_FOR_PICKUP')->count() }}</span>
                <span class="text-xs text-[#53645B]">sekolah ({{ $ready_tray_returns->where('status.value', 'READY_FOR_PICKUP')->sum('ready_count') }} wadah)</span>
            </div>
            <span class="text-[11px] text-[#708177] mt-1 block">{{ $ready_tray_returns->where('status.value', 'PICKUP_ASSIGNED')->count() }} telah ditugaskan</span>
        </div>
    </div>

    <!-- Layout Dua Kolom: Form Penugasan & Daftar Trip Berjalan -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Kolom Kiri (7 Kolom): Logistik Balik Wadah & Form Penugasan Perjalanan -->
        <div class="lg:col-span-7 space-y-6">
            <!-- Modul PBI-12: Ompreng Siap Dijemput (Reverse Logistics) -->
            <div class="p-5 rounded-2xl bg-white border border-[#DCE5DD]" id="section-tray-returns">
                <div class="flex items-center justify-between pb-3 border-b border-[#DCE5DD] mb-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 text-[10px] font-bold bg-[#F3EEF9] text-[#55327A] border border-[#DFCDEE] rounded">
                                PBI-12 Reverse Logistics
                            </span>
                        </div>
                        <h2 class="text-base font-bold text-[#172923]">Wadah Ompreng Siap Dijemput</h2>
                        <p class="text-xs text-[#53645B]">Daftar ompreng makanan yang telah disahkan siap kembali oleh PIC Sekolah dan memerlukan penugasan kurir resmi.</p>
                    </div>
                    <span class="text-xs font-mono font-bold text-[#55327A] px-2 py-1 bg-[#F3EEF9] rounded-lg">
                        {{ $ready_tray_returns->count() }} Sekolah
                    </span>
                </div>

                @if ($ready_tray_returns->isEmpty())
                    <div class="p-6 rounded-xl bg-[#FAFBF9] border border-dashed border-[#DCE5DD] text-center">
                        <svg class="w-8 h-8 mx-auto text-[#708177] mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <h3 class="text-sm font-bold text-[#172923]">Belum Ada Ompreng Menunggu Penjemputan</h3>
                        <p class="text-xs text-[#53645B] max-w-md mx-auto mt-1">
                            Ketika PIC Sekolah mengonfirmasi wadah bersih dan siap diambil melalui Halaman Layanan Sekolah (P06), daftar penjemputan akan otomatis muncul di sini.
                        </p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($ready_tray_returns as $tray)
                            @php
                                $school = $tray->school;
                                $availablePickupStops = \App\Models\TripStop::where('school_id', $tray->school_id)
                                    ->where('stop_type', 'PICKUP')
                                    ->whereNotIn('status', [\App\Enums\TripStopStatus::SKIPPED, \App\Enums\TripStopStatus::COMPLETED])
                                    ->whereHas('trip', function ($q) use ($service_date) {
                                        $q->where('sppg_id', auth()->user()->sppg_id)
                                          ->whereDate('service_date', $service_date)
                                          ->whereIn('status', [
                                              \App\Enums\TripStatus::ASSIGNED,
                                              \App\Enums\TripStatus::VEHICLE_CHECKED,
                                              \App\Enums\TripStatus::READY,
                                              \App\Enums\TripStatus::IN_PROGRESS,
                                          ]);
                                    })
                                    ->with(['trip.driver', 'trip.vehicle'])
                                    ->get();
                            @endphp

                            <div class="p-4 rounded-xl border {{ $tray->status === \App\Enums\TrayReturnStatus::PICKUP_ASSIGNED ? 'bg-[#F9FCFA] border-[#BCE2D0]' : 'bg-[#FAFBF9] border-[#DCE5DD]' }} transition-colors">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-[#E8EEE9]">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-bold text-[#172923]">{{ $school?->name ?? 'Sekolah #'.$tray->school_id }}</span>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded border {{ $tray->status->badgeClass() }}">
                                                {{ $tray->status->label() }}
                                            </span>
                                        </div>
                                        <span class="text-[11px] text-[#53645B] mt-0.5 block">
                                            Dikonfirmasi PIC: {{ $tray->ready_at ? $tray->ready_at->translatedFormat('d M Y, H:i') : '-' }} WIB
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="text-right">
                                            <span class="text-lg font-bold text-[#1B573E]">{{ $tray->ready_count }}</span>
                                            <span class="text-xs text-[#53645B]">wadah siap</span>
                                        </div>
                                        @if ($tray->damaged_count > 0)
                                            <div class="text-right pl-3 border-l border-[#DCE5DD]">
                                                <span class="text-sm font-bold text-[#9B3232]">{{ $tray->damaged_count }}</span>
                                                <span class="text-[11px] text-[#9B3232] block">rusak</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @if ($tray->hasDiscrepancy() && $tray->discrepancy_reason)
                                    <div class="mt-2 p-2 rounded-lg bg-[#FDF8EE] border border-[#F5E6BE] text-[11px] text-[#7A5B14]">
                                        <span class="font-bold">Catatan PIC:</span> {{ $tray->discrepancy_reason }}
                                    </div>
                                @endif

                                @if ($tray->status === \App\Enums\TrayReturnStatus::PICKUP_ASSIGNED && $tray->pickupTripStop)
                                    <div class="mt-3 p-2.5 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                        <div>
                                            <span class="text-xs font-bold text-[#1B573E] block">
                                                Kurir Ditugaskan: {{ $tray->pickupTripStop->trip->driver?->name }}
                                            </span>
                                            <span class="text-[11px] text-[#53645B]">
                                                Trip: {{ $tray->pickupTripStop->trip->trip_code }} : Armada: {{ $tray->pickupTripStop->trip->vehicle?->plate_number }} (Perhentian #{{ $tray->pickupTripStop->sequence_number }})
                                            </span>
                                        </div>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-[#1C6654] text-white shrink-0">
                                            Aktif di D05
                                        </span>
                                    </div>
                                @endif

                                <!-- Form Penugasan / Pemindahan Penugasan -->
                                <form method="POST" action="{{ route('head.distribution.trays.assign', $tray) }}" class="mt-3 pt-3 border-t border-[#E8EEE9] flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                    @csrf
                                    @if ($availablePickupStops->isNotEmpty())
                                        <div class="flex-1">
                                            <label for="pickup_stop_{{ $tray->id }}" class="sr-only">Pilih Perjalanan Penjemputan</label>
                                            <select id="pickup_stop_{{ $tray->id }}" 
                                                    name="pickup_trip_stop_id" 
                                                    required 
                                                    class="w-full px-3 py-2 text-xs font-medium text-[#172923] bg-white border border-[#DCE5DD] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1C6654] touch-target">
                                                <option value="">-- Pilih Perjalanan &amp; Kurir Penjemput --</option>
                                                @foreach ($availablePickupStops as $pStop)
                                                    <option value="{{ $pStop->id }}" {{ $tray->pickup_trip_stop_id === $pStop->id ? 'selected' : '' }}>
                                                        {{ $pStop->trip->trip_code }} : Kurir {{ $pStop->trip->driver?->name }} (Armada {{ $pStop->trip->vehicle?->plate_number }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="submit" 
                                                class="px-4 py-2 rounded-xl bg-[#55327A] hover:bg-[#432662] text-white text-xs font-bold transition-colors touch-target cursor-pointer whitespace-nowrap">
                                            {{ $tray->status === \App\Enums\TrayReturnStatus::PICKUP_ASSIGNED ? 'Ubah Penugasan' : 'Tugaskan Kurir' }}
                                        </button>
                                    @else
                                        <div class="text-[11px] text-[#7A5B14] bg-[#FEF9ED] p-2.5 rounded-xl border border-[#F5E6BE] w-full flex items-center justify-between gap-2">
                                            <span>Belum ada rute penjemputan (PICKUP) untuk sekolah ini hari ini. Buat perjalanan penjemputan pada form di bawah.</span>
                                            <button type="button" 
                                                    onclick="document.getElementById('trip_type_select').value='PICKUP'; document.getElementById('trip_type_select').dispatchEvent(new Event('change')); document.getElementById('form-trip-planning').scrollIntoView({behavior: 'smooth'});"
                                                    class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-[#55327A] text-white hover:bg-[#432662] transition-colors whitespace-nowrap touch-target cursor-pointer">
                                                Buat Trip PICKUP
                                            </button>
                                        </div>
                                    @endif
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Modul Form Pembuatan Perjalanan Baru (DELIVERY / PICKUP / MIXED) -->
            <div class="p-5 rounded-2xl bg-white border border-[#DCE5DD]" id="form-trip-planning">
                <div class="flex items-center justify-between pb-3 border-b border-[#DCE5DD] mb-4">
                    <div>
                        <h2 class="text-base font-bold text-[#172923]">Form Penugasan Rute &amp; Muatan Baru</h2>
                        <p class="text-xs text-[#53645B]">Pilih armada, kurir, dan atur rute sekolah untuk pengantaran makanan atau penjemputan ompreng.</p>
                    </div>
                    <span class="px-2 py-0.5 text-[11px] font-bold bg-[#EDF2ED] text-[#172923] border border-[#DCE5DD] rounded">
                        PBI-08 &amp; PBI-12
                    </span>
                </div>

                <form method="POST" action="{{ route('head.distribution.trips.store') }}" id="create-trip-form" class="space-y-4">
                    @csrf
                    <input type="hidden" name="service_date" value="{{ $service_date }}">

                    <!-- Pemilih Jenis Perjalanan (DELIVERY / PICKUP / MIXED) -->
                    <div>
                        <label for="trip_type_select" class="block text-xs font-bold text-[#172923] mb-1">
                            Jenis Perjalanan <span class="text-red-500">*</span>
                        </label>
                        <select id="trip_type_select" 
                                name="trip_type" 
                                onchange="switchTripType(this.value)"
                                class="w-full px-3 py-2 text-xs font-bold text-[#172923] bg-[#FAFBF9] border border-[#DCE5DD] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1C6654] touch-target">
                            <option value="DELIVERY" {{ old('trip_type', $unassigned_delivery_items->isNotEmpty() ? 'DELIVERY' : 'PICKUP') === 'DELIVERY' ? 'selected' : '' }}>
                                Pengantaran Makanan (DELIVERY) : Memerlukan alokasi makanan QC RELEASED
                            </option>
                            <option value="PICKUP" {{ old('trip_type', $unassigned_delivery_items->isEmpty() ? 'PICKUP' : 'DELIVERY') === 'PICKUP' ? 'selected' : '' }}>
                                Penjemputan Ompreng (PICKUP) : Kendaraan berangkat kosong untuk ambil wadah
                            </option>
                            <option value="MIXED" {{ old('trip_type') === 'MIXED' ? 'selected' : '' }}>
                                Gabungan (MIXED) : Pengantaran makanan &amp; penjemputan ompreng
                            </option>
                        </select>
                    </div>

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

                    <!-- Waktu Keberangkatan Rencana -->
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

                    <!-- KONTEN STOP 1: PENGANTARAN MAKANAN (DELIVERY) -->
                    <div id="section-delivery-stops" class="space-y-3 pt-3 border-t border-[#DCE5DD]">
                        <h3 class="text-xs font-bold text-[#172923] uppercase tracking-wider flex items-center justify-between">
                            <span>Daftar Alokasi Sekolah Siap Kirim (DELIVERY):</span>
                            <span class="text-[11px] font-normal text-[#53645B]">{{ $unassigned_delivery_items->count() }} alokasi siap</span>
                        </h3>

                        @if ($unassigned_delivery_items->isEmpty())
                            <div class="p-4 rounded-xl bg-[#FAFBF9] border border-dashed border-[#DCE5DD] text-center text-xs text-[#53645B]">
                                Tidak ada alokasi makanan siap muat untuk pengantaran saat ini. Pilih jenis perjalanan <strong>Penjemputan Ompreng (PICKUP)</strong> di atas untuk menjalankan logistik balik wadah.
                            </div>
                        @else
                            <div class="space-y-3">
                                @php
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
                                    <div class="p-3 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] delivery-stop-item">
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

                                        <input type="hidden" class="delivery-stop-input" name="stops[{{ $seq - 1 }}][school_id]" value="{{ $school->id }}">
                                        <input type="hidden" class="delivery-stop-input" name="stops[{{ $seq - 1 }}][sequence_number]" value="{{ $seq }}">
                                        <input type="hidden" class="delivery-stop-input" name="stops[{{ $seq - 1 }}][stop_type]" value="DELIVERY">

                                        <div class="space-y-2 mt-2 pt-2 border-t border-[#E8EEE9]">
                                            @foreach ($items as $itemIdx => $item)
                                                <div class="flex items-center justify-between text-[11px] text-[#53645B] pl-2">
                                                    <div>
                                                        <span>Batch: <strong>{{ $item->foodBatch->batch_code }}</strong></span>
                                                        <span class="text-[#708177]">({{ $item->foodBatch->recipeMenu?->name ?? 'Menu' }})</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <span>Rencana Muat:</span>
                                                        <input type="hidden" class="delivery-stop-input" name="stops[{{ $seq - 1 }}][items][{{ $itemIdx }}][delivery_item_id]" value="{{ $item->id }}">
                                                        <input type="number" 
                                                               class="delivery-stop-input w-20 px-2 py-1 text-xs font-bold text-right text-[#172923] bg-white border border-[#DCE5DD] rounded-lg focus:outline-none focus:ring-1 focus:ring-[#1C6654]"
                                                               name="stops[{{ $seq - 1 }}][items][{{ $itemIdx }}][planned_portions]" 
                                                               value="{{ $item->released_portions }}"
                                                               max="{{ $item->released_portions }}"
                                                               min="1"
                                                               required>
                                                        <span class="text-[11px] text-[#708177]">/ {{ $item->released_portions }} porsi</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @php $seq++; @endphp
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- KONTEN STOP 2: PENJEMPUTAN OMPRENG (PICKUP) -->
                    <div id="section-pickup-stops" class="space-y-3 pt-3 border-t border-[#DCE5DD] hidden">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xs font-bold text-[#172923] uppercase tracking-wider">
                                    Pilih Sekolah Tujuan Penjemputan (PICKUP):
                                </h3>
                                <p class="text-[11px] text-[#53645B]">Kendaraan berangkat kosong. Kurir mengambil wadah ompreng di lokasi sekolah.</p>
                            </div>
                            <span class="text-[11px] font-bold text-[#55327A] bg-[#F3EEF9] px-2 py-0.5 rounded border border-[#DFCDEE]">
                                Logistik Balik
                            </span>
                        </div>

                        <div class="space-y-2.5">
                            @php
                                $pickupSeq = 1;
                            @endphp
                            @foreach ($schools as $school)
                                @php
                                    $readyTray = $ready_tray_returns->firstWhere('school_id', $school->id);
                                    $hasReady = ($readyTray !== null);
                                    $readyCount = $readyTray?->ready_count ?? 0;
                                @endphp
                                <div class="p-3 rounded-xl border {{ $hasReady ? 'bg-[#F9FCFA] border-[#BCE2D0]' : 'bg-[#FAFBF9] border-[#DCE5DD]' }} flex items-center justify-between gap-3">
                                    <label class="flex items-center gap-3 cursor-pointer flex-1">
                                        <input type="checkbox" 
                                               class="pickup-school-checkbox w-4 h-4 rounded text-[#1C6654] border-[#DCE5DD] focus:ring-[#1C6654]" 
                                               data-school-id="{{ $school->id }}"
                                               data-school-name="{{ $school->name }}"
                                               {{ $hasReady ? 'checked' : '' }}
                                               onchange="rebuildPickupStops()">
                                        <div>
                                            <span class="text-xs font-bold text-[#172923] block">{{ $school->name }}</span>
                                            <span class="text-[11px] {{ $hasReady ? 'text-[#1B573E] font-semibold' : 'text-[#708177]' }}">
                                                {{ $hasReady ? $readyCount . ' wadah siap dijemput (' . $readyTray->status->label() . ')' : 'Belum ada laporan wadah siap' }}
                                            </span>
                                        </div>
                                    </label>
                                    @if ($hasReady)
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-[#EDF7F2] text-[#1B573E] border border-[#BCE2D0]">
                                            Prioritas Retur
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <!-- Container tersembunyi yang diisi otomatis oleh Vanilla JS untuk stops penjemputan -->
                        <div id="dynamic-pickup-stops-container"></div>
                    </div>

                    <!-- Tombol Submit Terbitkan Trip -->
                    <div class="pt-3 border-t border-[#DCE5DD] flex justify-end">
                        <button type="submit" 
                                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs font-bold shadow-xs transition-colors touch-target cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span id="submit-btn-text">Rencanakan Penugasan Perjalanan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Kolom Kanan (5 Kolom): Daftar Perjalanan Aktif & Kontrol Dispatch Gate -->
        <div class="lg:col-span-5 space-y-4">
            <div class="p-5 rounded-2xl bg-white border border-[#DCE5DD]">
                <div class="flex items-center justify-between pb-3 border-b border-[#DCE5DD] mb-4">
                    <h2 class="text-base font-bold text-[#172923]">Monitoring Perjalanan &amp; Dispatch Gate</h2>
                    <span class="text-xs text-[#53645B] font-mono">{{ $trips->count() }} Terdaftar</span>
                </div>

                @if ($trips->isEmpty())
                    <div class="p-6 rounded-xl bg-[#FAFBF9] border border-dashed border-[#DCE5DD] text-center">
                        <p class="text-xs text-[#53645B]">
                            Belum ada jadwal perjalanan pengantaran yang diterbitkan untuk tanggal {{ \Carbon\Carbon::parse($service_date)->translatedFormat('d F Y') }}.
                        </p>
                    </div>
                @else
                    @php
                        $selectedTripId = (int) request('trip_id', $trips->first()?->id);
                        $selectedTrip = $trips->firstWhere('id', $selectedTripId) ?? $trips->first();
                    @endphp

                    <!-- Tab Pilihan Trip untuk Peta Kontrol -->
                    @if ($trips->count() > 1)
                        <div class="flex items-center gap-2 overflow-x-auto pb-2 mb-3">
                            @foreach ($trips as $t)
                                <a href="{{ route('head.distribution.index', ['service_date' => $service_date, 'trip_id' => $t->id]) }}" 
                                   class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap border transition-colors touch-target {{ $t->id === $selectedTrip->id ? 'bg-[#1C6654] text-white border-[#1C6654]' : 'bg-[#FAFBF9] text-[#53645B] border-[#DCE5DD] hover:bg-white' }}">
                                    {{ $t->trip_code }} ({{ $t->driver->name }})
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <!-- Dispatch Control Map Container -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold text-[#172923] flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-[#1C6654]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                </svg>
                                <span>Peta Rute Multi-Sekolah: {{ $selectedTrip->trip_code }}</span>
                            </span>
                            @if ($selectedTrip->total_distance_m)
                                <span class="text-[11px] text-[#53645B] font-mono">
                                    {{ round($selectedTrip->total_distance_m / 1000, 1) }} km | ~{{ round($selectedTrip->estimated_duration_s / 60) }} mnt
                                </span>
                            @endif
                        </div>
                        <div id="dispatch-control-map" 
                             class="w-full h-64 sm:h-72 rounded-xl overflow-hidden border border-[#DCE5DD] bg-slate-100">
                        </div>
                        <p class="text-[10px] text-[#708177] mt-1">
                            Peta berbasis OpenFreeMap &amp; OSRM. Jalur rute jalan dihitung antar perhentian sekolah.
                        </p>
                    </div>

                    <div class="space-y-4">
                        @foreach ($trips as $trip)
                            <div class="p-4 rounded-xl {{ $trip->id === $selectedTrip->id ? 'bg-white border-2 border-[#1C6654]' : 'bg-[#FAFBF9] border border-[#DCE5DD]' }} transition-colors">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-mono font-bold text-[#172923] block">{{ $trip->trip_code }}</span>
                                            @if ($trip->id === $selectedTrip->id)
                                                <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-[#1C6654] text-white">Aktif di Peta</span>
                                            @endif
                                        </div>
                                        <span class="text-[11px] text-[#53645B]">
                                            Kurir: <strong>{{ $trip->driver->name }}</strong> : {{ $trip->vehicle->plate_number }} (Kapasitas: {{ $trip->vehicle->capacity_portions ?? 'N/A' }})
                                        </span>
                                    </div>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded border {{ $trip->status->badgeClass() }}">
                                        {{ $trip->status->label() }}
                                    </span>
                                </div>

                                <!-- 4-Block Dispatch Gate Status -->
                                <div class="my-3 p-3 rounded-lg bg-[#FAFBF9] border border-[#E8EEE9] space-y-2 text-xs">
                                    <span class="font-bold text-[#172923] block text-[11px] uppercase tracking-wider">
                                        Status Gerbang Keberangkatan (Dispatch Gate):
                                    </span>
                                    <div class="grid grid-cols-2 gap-2 text-[11px]">
                                        <!-- Gate 1: Mutu QC -->
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-3.5 h-3.5 rounded-full flex items-center justify-center bg-[#EDF7F2] text-[#1B573E] font-bold text-[9px]">1</span>
                                            <span class="text-[#53645B]">QC Makanan:</span>
                                            <span class="font-bold text-[#1B573E]">RELEASED</span>
                                        </div>

                                        <!-- Gate 2: Verifikasi Muatan Fisik -->
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-3.5 h-3.5 rounded-full flex items-center justify-center {{ $trip->loading_verified ? 'bg-[#EDF7F2] text-[#1B573E]' : 'bg-[#FDF2F2] text-[#9B3232]' }} font-bold text-[9px]">2</span>
                                            <span class="text-[#53645B]">Fisik Muatan:</span>
                                            @if ($trip->loading_verified)
                                                <span class="font-bold text-[#1B573E]">Terverifikasi</span>
                                            @else
                                                <span class="font-bold text-[#9B3232]">Belum Muat</span>
                                            @endif
                                        </div>

                                        <!-- Gate 3: Kelaikan Kendaraan -->
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-3.5 h-3.5 rounded-full flex items-center justify-center {{ $trip->vehicle_check_passed ? 'bg-[#EDF7F2] text-[#1B573E]' : 'bg-[#FAFBF9] text-[#708177]' }} font-bold text-[9px]">3</span>
                                            <span class="text-[#53645B]">Kelaikan Armada:</span>
                                            @if ($trip->vehicle_check_passed)
                                                <span class="font-bold text-[#1B573E]">Laik Jalan</span>
                                            @else
                                                <span class="font-bold text-[#708177]">Menunggu Kurir</span>
                                            @endif
                                        </div>

                                        <!-- Gate 4: Otorisasi Kepala SPPG -->
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-3.5 h-3.5 rounded-full flex items-center justify-center {{ $trip->dispatch_approved ? 'bg-[#EDF7F2] text-[#1B573E]' : 'bg-[#FAFBF9] text-[#708177]' }} font-bold text-[9px]">4</span>
                                            <span class="text-[#53645B]">Izin Dispatch:</span>
                                            @if ($trip->dispatch_approved)
                                                <span class="font-bold text-[#1B573E]">Disahkan</span>
                                            @else
                                                <span class="font-bold text-[#708177]">Menunggu Izin</span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Aksi Verifikasi Fisik Muatan -->
                                    @if (! $trip->loading_verified && in_array($trip->status->value, ['ASSIGNED', 'VEHICLE_CHECKED']))
                                        <div class="mt-2 pt-2 border-t border-[#E8EEE9]">
                                            <form method="POST" action="{{ route('head.distribution.trips.verify-loading', $trip) }}" class="space-y-2">
                                                @csrf
                                                <div class="text-[11px] font-bold text-[#172923]">Form Verifikasi Muatan Fisik:</div>
                                                @foreach ($trip->stops as $stop)
                                                    @foreach ($stop->deliveryItems as $item)
                                                        <div class="flex items-center justify-between text-[11px]">
                                                            <span class="truncate max-w-[150px]">{{ $stop->school->name }} ({{ $item->foodBatch->batch_code }}):</span>
                                                            <div class="flex items-center gap-1">
                                                                <input type="number" 
                                                                       name="items[{{ $item->id }}]" 
                                                                       value="{{ $item->planned_portions ?: $item->released_portions }}" 
                                                                       max="{{ $item->released_portions }}" 
                                                                       min="1" 
                                                                       required 
                                                                       class="w-16 px-1.5 py-0.5 text-xs text-right font-bold border border-[#DCE5DD] rounded">
                                                                <span class="text-[10px] text-[#708177]">/ {{ $item->released_portions }}</span>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @endforeach
                                                <div class="flex justify-end pt-1">
                                                    <button type="submit" 
                                                            class="px-3 py-1.5 rounded-lg bg-[#285F80] hover:bg-[#1E4861] text-white text-[11px] font-bold transition-colors touch-target cursor-pointer">
                                                        Sahkan Verifikasi Muatan Fisik
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    @endif

                                    <!-- Aksi Otorisasi Keberangkatan oleh Kepala SPPG -->
                                    @if ($trip->loading_verified && $trip->vehicle_check_passed && ! $trip->dispatch_approved && in_array($trip->status->value, ['ASSIGNED', 'VEHICLE_CHECKED']))
                                        <div class="mt-2 pt-2 border-t border-[#E8EEE9] flex items-center justify-between">
                                            <span class="text-[11px] text-[#1B573E] font-semibold">Syarat terpenuhi. Siap disahkan:</span>
                                            <form method="POST" action="{{ route('head.distribution.trips.approve-dispatch', $trip) }}">
                                                @csrf
                                                <button type="submit" 
                                                        class="px-3 py-1.5 rounded-lg bg-[#1C6654] hover:bg-[#164D42] text-white text-[11px] font-bold shadow-2xs transition-colors touch-target cursor-pointer">
                                                    Sahkan Keberangkatan (READY)
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>

                                <!-- Ringkasan Stop Rute -->
                                <div class="mt-3 pt-2 border-t border-[#E8EEE9] space-y-1.5">
                                    <div class="text-[11px] font-semibold text-[#53645B] flex justify-between">
                                        <span>Rute Perhentian ({{ $trip->stops->count() }} Sekolah):</span>
                                        <span class="font-bold text-[#172923]">{{ $trip->total_loaded_portions ?: $trip->stops->sum('planned_portions') }} Porsi</span>
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
                                        <span>Buka Detail &amp; Rute (C03)</span>
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

                    @if ($selectedTrip)
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                if (window.initDispatchMap) {
                                    window.initDispatchMap('dispatch-control-map', {
                                        dataUrl: '{{ route('trips.map-data', $selectedTrip) }}',
                                        pollInterval: {{ $selectedTrip->status === \App\Enums\TripStatus::IN_PROGRESS ? 10000 : 0 }}
                                    });
                                }
                            });
                        </script>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <script>
        function switchTripType(type) {
            const deliverySection = document.getElementById('section-delivery-stops');
            const pickupSection = document.getElementById('section-pickup-stops');
            const submitBtnText = document.getElementById('submit-btn-text');
            const deliveryInputs = document.querySelectorAll('.delivery-stop-input');

            if (type === 'DELIVERY') {
                if (deliverySection) deliverySection.classList.remove('hidden');
                if (pickupSection) pickupSection.classList.add('hidden');
                if (submitBtnText) submitBtnText.textContent = 'Rencanakan Pengantaran Makanan';
                deliveryInputs.forEach(function(input) { input.disabled = false; });
            } else if (type === 'PICKUP') {
                if (deliverySection) deliverySection.classList.add('hidden');
                if (pickupSection) pickupSection.classList.remove('hidden');
                if (submitBtnText) submitBtnText.textContent = 'Rencanakan Penjemputan Ompreng';
                deliveryInputs.forEach(function(input) { input.disabled = true; });
                rebuildPickupStops();
            } else if (type === 'MIXED') {
                if (deliverySection) deliverySection.classList.remove('hidden');
                if (pickupSection) pickupSection.classList.remove('hidden');
                if (submitBtnText) submitBtnText.textContent = 'Rencanakan Perjalanan Campuran';
                deliveryInputs.forEach(function(input) { input.disabled = false; });
                rebuildPickupStops();
            }
        }

        function rebuildPickupStops() {
            const container = document.getElementById('dynamic-pickup-stops-container');
            const typeSelect = document.getElementById('trip_type_select');
            if (!container || !typeSelect) return;

            const tripType = typeSelect.value;
            container.innerHTML = '';

            if (tripType === 'DELIVERY') {
                return;
            }

            const checkedBoxes = Array.from(document.querySelectorAll('.pickup-school-checkbox:checked'));
            let startSeq = 1;

            if (tripType === 'MIXED') {
                const deliveryStops = document.querySelectorAll('.delivery-stop-item');
                startSeq = deliveryStops.length + 1;
            }

            checkedBoxes.forEach(function(checkbox, index) {
                const schoolId = checkbox.dataset.schoolId;
                const seq = startSeq + index;
                const stopIdx = seq - 1;

                const schoolInput = document.createElement('input');
                schoolInput.type = 'hidden';
                schoolInput.name = 'stops[' + stopIdx + '][school_id]';
                schoolInput.value = schoolId;

                const seqInput = document.createElement('input');
                seqInput.type = 'hidden';
                seqInput.name = 'stops[' + stopIdx + '][sequence_number]';
                seqInput.value = seq;

                const typeInput = document.createElement('input');
                typeInput.type = 'hidden';
                typeInput.name = 'stops[' + stopIdx + '][stop_type]';
                typeInput.value = 'PICKUP';

                container.appendChild(schoolInput);
                container.appendChild(seqInput);
                container.appendChild(typeInput);
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const typeSelect = document.getElementById('trip_type_select');
            if (typeSelect) {
                switchTripType(typeSelect.value);
            }
        });
    </script>
</x-app-shell>
