<x-app-shell title="Kontrol Distribusi & Armada">
    <div class="space-y-5">
        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <span class="eyebrow-label text-[#1C6654] block mb-1">Kepala SPPG • {{ auth()->user()->sppg?->code ?? 'SPPG' }}</span>
                <h1 class="page-title">Kontrol Distribusi, Armada &amp; Rute Pengantaran</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-1">Penjadwalan pengiriman multi-sekolah dari batch lolos QC, otorisasi keberangkatan, dan penjemputan wadah ompreng.</p>
            </div>

            <form method="GET" action="{{ route('head.distribution.index') }}" class="flex items-center gap-2 shrink-0">
                <label for="service_date" class="text-xs font-semibold text-[#53645B]">Tanggal:</label>
                <input type="date"
                       id="service_date"
                       name="service_date"
                       value="{{ $service_date }}"
                       onchange="this.form.submit()"
                       class="min-h-[44px] px-3 py-2 text-xs font-medium text-[#172923] bg-white border border-[#DCE5DD] rounded-xl">
            </form>
        </header>

        @if (session('success'))
            <div class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-[#1B573E] text-xs sm:text-sm font-medium flex items-center gap-3 subtle-shadow">
                <svg class="w-5 h-5 shrink-0 text-[#21684B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-xl bg-[#FDF2F2] border border-[#F5C2C2] text-[#9B3232] text-xs sm:text-sm space-y-1">
                <p class="font-bold">Validasi Pengiriman Belum Terpenuhi</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Compact Logistics Readiness Strip -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            <div class="luxury-surface p-3.5 sm:p-4">
                <span class="eyebrow-label block">Armada Tersedia</span>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl font-bold text-[#172923] tabular-nums">{{ $vehicles->where('is_busy', false)->count() }}</span>
                    <span class="text-xs text-[#53645B]">/ {{ $vehicles->count() }}</span>
                </div>
            </div>

            <div class="luxury-surface p-3.5 sm:p-4">
                <span class="eyebrow-label block">Kurir Siap</span>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl font-bold text-[#172923] tabular-nums">{{ $drivers->where('is_busy', false)->count() }}</span>
                    <span class="text-xs text-[#53645B]">/ {{ $drivers->count() }}</span>
                </div>
            </div>

            <div class="luxury-surface p-3.5 sm:p-4">
                <span class="eyebrow-label block">Siap Muat</span>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl font-bold text-[#1C6654] tabular-nums">{{ number_format($unassigned_delivery_items->sum('released_portions')) }}</span>
                    <span class="text-xs text-[#53645B]">porsi</span>
                </div>
            </div>

            <div class="luxury-surface p-3.5 sm:p-4">
                <span class="eyebrow-label block">Trip Hari Ini</span>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl font-bold text-[#285F80] tabular-nums">{{ $trips->count() }}</span>
                    <span class="text-xs text-[#53645B]">rute</span>
                </div>
            </div>

            <div class="luxury-surface p-3.5 sm:p-4 col-span-2 sm:col-span-1">
                <span class="eyebrow-label block text-[#55327A]">Ompreng Siap Jemput</span>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl font-bold text-[#55327A] tabular-nums">{{ $ready_tray_returns->where('status.value', 'READY_FOR_PICKUP')->count() }}</span>
                    <span class="text-xs text-[#53645B]">sekolah</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
            <!-- Left Column (7 Cols): Reverse Logistics & New Trip Form -->
            <div class="lg:col-span-7 space-y-5">
                <!-- Tray Returns Queue -->
                <x-card title="Ompreng Siap Jemput" subtitle="Wadah bersih yang telah disahkan PIC Sekolah dan menunggu penugasan kurir." badge="{{ $ready_tray_returns->count() }} Sekolah" id="section-tray-returns">
                    @if ($ready_tray_returns->isEmpty())
                        <div class="p-6 inset-surface text-center text-xs text-[#53645B]">
                            <p class="font-semibold text-[#172923]">Belum Ada Ompreng Menunggu Penjemputan</p>
                            <p class="mt-1">Daftar sekolah akan tampil otomatis setelah PIC Sekolah menandai ompreng siap dijemput.</p>
                        </div>
                    @else
                        <div class="space-y-3">
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

                                <div class="p-4 rounded-xl border {{ $tray->status === \App\Enums\TrayReturnStatus::PICKUP_ASSIGNED ? 'bg-[#F9FCFA] border-[#BCE2D0]' : 'bg-[#FAFBF9] border-[#DCE5DD]' }} space-y-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-sm font-bold text-[#172923]">{{ $school?->name ?? 'Sekolah #'.$tray->school_id }}</span>
                                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md border {{ $tray->status->badgeClass() }}">
                                                    {{ $tray->status->label() }}
                                                </span>
                                            </div>
                                            <span class="text-[11px] text-[#53645B] block mt-0.5 tabular-nums">
                                                Siap sejak {{ $tray->ready_at ? $tray->ready_at->translatedFormat('d M Y, H:i') : '-' }} WIB
                                            </span>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="text-lg font-bold text-[#1C6654] tabular-nums block">{{ $tray->ready_count }}</span>
                                            <span class="text-[10px] text-[#53645B]">wadah</span>
                                        </div>
                                    </div>

                                    @if ($tray->hasDiscrepancy() && $tray->discrepancy_reason)
                                        <div class="p-2.5 rounded-lg bg-[#FEF7EC] border border-[#F7D7A3] text-[11px] text-[#885D1C]">
                                            <strong>Catatan PIC:</strong> {{ $tray->discrepancy_reason }}
                                        </div>
                                    @endif

                                    @if ($tray->status === \App\Enums\TrayReturnStatus::PICKUP_ASSIGNED && $tray->pickupTripStop)
                                        <div class="p-2.5 rounded-lg bg-[#EDF7F2] border border-[#BCE2D0] text-xs text-[#1B573E]">
                                            Kurir: <strong>{{ $tray->pickupTripStop->trip->driver?->name }}</strong> ({{ $tray->pickupTripStop->trip->trip_code }} • {{ $tray->pickupTripStop->trip->vehicle?->plate_number }})
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('head.distribution.trays.assign', $tray) }}" class="pt-2 border-t border-[#E7ECE8] flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                        @csrf
                                        @if ($availablePickupStops->isNotEmpty())
                                            <select id="pickup_stop_{{ $tray->id }}"
                                                    name="pickup_trip_stop_id"
                                                    required
                                                    aria-label="Pilih perjalanan penjemputan"
                                                    class="flex-1 min-h-[44px] px-3 py-2 text-xs font-medium text-[#172923] bg-white border border-[#DCE5DD] rounded-xl">
                                                <option value="">Pilih Perjalanan &amp; Kurir Penjemput</option>
                                                @foreach ($availablePickupStops as $pStop)
                                                    <option value="{{ $pStop->id }}" {{ $tray->pickup_trip_stop_id === $pStop->id ? 'selected' : '' }}>
                                                        {{ $pStop->trip->trip_code }} • {{ $pStop->trip->driver?->name }} ({{ $pStop->trip->vehicle?->plate_number }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit"
                                                    class="min-h-[44px] px-4 py-2 rounded-xl bg-[#55327A] hover:bg-[#432662] text-white text-xs font-semibold cursor-pointer whitespace-nowrap smooth-transition">
                                                {{ $tray->status === \App\Enums\TrayReturnStatus::PICKUP_ASSIGNED ? 'Ubah Penugasan' : 'Tugaskan Kurir' }}
                                            </button>
                                        @else
                                            <div class="text-[11px] text-[#885D1C] bg-[#FEF7EC] p-2.5 rounded-xl border border-[#F7D7A3] w-full flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                <span>Belum ada rute PICKUP untuk sekolah ini hari ini.</span>
                                                <button type="button"
                                                        onclick="document.getElementById('trip_type_select').value='PICKUP'; document.getElementById('trip_type_select').dispatchEvent(new Event('change')); document.getElementById('form-trip-planning').scrollIntoView({behavior: 'smooth'});"
                                                        class="min-h-[38px] px-3 py-1.5 text-[11px] font-semibold rounded-lg bg-[#55327A] text-white cursor-pointer whitespace-nowrap">
                                                    Buat Rute Penjemputan
                                                </button>
                                            </div>
                                        @endif
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>

                <!-- New Trip Planning Form -->
                <x-card title="Form Penugasan Rute & Muatan Baru" subtitle="Pilih jenis perjalanan, armada, kurir, dan daftar sekolah tujuan." id="form-trip-planning">
                    <form method="POST" action="{{ route('head.distribution.trips.store') }}" id="create-trip-form" class="space-y-4">
                        @csrf
                        <input type="hidden" name="service_date" value="{{ $service_date }}">

                        <div>
                            <label for="trip_type_select" class="block text-xs font-semibold text-[#172923] mb-1.5">
                                Jenis Perjalanan <span class="text-[#9B3232]">*</span>
                            </label>
                            <select id="trip_type_select"
                                    name="trip_type"
                                    onchange="switchTripType(this.value)"
                                    class="w-full min-h-[44px] px-3.5 py-2 text-xs font-semibold text-[#172923] bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl">
                                <option value="DELIVERY" {{ old('trip_type', $unassigned_delivery_items->isNotEmpty() ? 'DELIVERY' : 'PICKUP') === 'DELIVERY' ? 'selected' : '' }}>
                                    Pengantaran Makanan (DELIVERY)
                                </option>
                                <option value="PICKUP" {{ old('trip_type', $unassigned_delivery_items->isEmpty() ? 'PICKUP' : 'DELIVERY') === 'PICKUP' ? 'selected' : '' }}>
                                    Penjemputan Ompreng (PICKUP)
                                </option>
                                <option value="MIXED" {{ old('trip_type') === 'MIXED' ? 'selected' : '' }}>
                                    Gabungan Pengantaran &amp; Jemput (MIXED)
                                </option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="vehicle_id" class="block text-xs font-semibold text-[#172923] mb-1.5">
                                    Armada Kendaraan <span class="text-[#9B3232]">*</span>
                                </label>
                                <select id="vehicle_id"
                                        name="vehicle_id"
                                        required
                                        class="w-full min-h-[44px] px-3.5 py-2 text-xs font-medium text-[#172923] bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl">
                                    <option value="">Pilih Kendaraan Tersedia</option>
                                    @foreach ($vehicles as $v)
                                        <option value="{{ $v->id }}" {{ $v->is_busy ? 'disabled' : '' }}>
                                            {{ $v->plate_number }} ({{ $v->vehicle_type }}) • Maks {{ $v->capacity_portions ?? '-' }} porsi {{ $v->is_busy ? '[Bertugas]' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="driver_id" class="block text-xs font-semibold text-[#172923] mb-1.5">
                                    Kurir Ditugaskan <span class="text-[#9B3232]">*</span>
                                </label>
                                <select id="driver_id"
                                        name="driver_id"
                                        required
                                        class="w-full min-h-[44px] px-3.5 py-2 text-xs font-medium text-[#172923] bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl">
                                    <option value="">Pilih Kurir Aktif</option>
                                    @foreach ($drivers as $d)
                                        <option value="{{ $d->id }}" {{ $d->is_busy ? 'disabled' : '' }}>
                                            {{ $d->name }} {{ $d->is_busy ? '[Bertugas]' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="planned_departure_at" class="block text-xs font-semibold text-[#172923] mb-1.5">
                                Waktu Rencana Keberangkatan
                            </label>
                            <input type="datetime-local"
                                   id="planned_departure_at"
                                   name="planned_departure_at"
                                   value="{{ \Carbon\Carbon::parse($service_date)->setTime(9, 30)->format('Y-m-d\TH:i') }}"
                                   class="w-full min-h-[44px] px-3.5 py-2 text-xs font-medium text-[#172923] bg-[#FAFBF9] focus:bg-white border border-[#DCE5DD] rounded-xl tabular-nums">
                        </div>

                        <!-- Delivery Stops -->
                        <div id="section-delivery-stops" class="space-y-3 pt-3 border-t border-[#E7ECE8]">
                            <div class="flex items-center justify-between">
                                <h3 class="eyebrow-label text-[#172923]">Alokasi Sekolah Siap Kirim</h3>
                                <span class="text-[11px] text-[#53645B]">{{ $unassigned_delivery_items->count() }} alokasi</span>
                            </div>

                            @if ($unassigned_delivery_items->isEmpty())
                                <div class="p-4 inset-surface text-center text-xs text-[#53645B]">
                                    Belum ada alokasi makanan berstatus lolos QC yang siap dimuat. Gunakan jenis perjalanan <strong>Penjemputan Ompreng (PICKUP)</strong> bila hanya mengambil wadah.
                                </div>
                            @else
                                <div class="space-y-2.5">
                                    @php
                                        $groupedBySchool = $unassigned_delivery_items->groupBy(fn ($item) => $item->productionAllocation->school_id);
                                        $seq = 1;
                                    @endphp

                                    @foreach ($groupedBySchool as $schoolId => $items)
                                        @php
                                            $school = $items->first()->productionAllocation->school;
                                            $totalSchoolPortions = $items->sum('released_portions');
                                        @endphp
                                        <div class="inset-surface p-3.5 delivery-stop-item space-y-2.5">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="w-5 h-5 rounded-full bg-[#1C6654] text-white text-[10px] font-bold flex items-center justify-center shrink-0">{{ $seq }}</span>
                                                    <span class="text-xs font-bold text-[#172923] truncate">{{ $school->name }}</span>
                                                </div>
                                                <span class="text-[11px] font-semibold text-[#21684B] bg-[#EDF7F2] px-2 py-0.5 rounded-md border border-[#BCE2D0] shrink-0 tabular-nums">
                                                    {{ $totalSchoolPortions }} porsi
                                                </span>
                                            </div>

                                            <input type="hidden" class="delivery-stop-input" name="stops[{{ $seq - 1 }}][school_id]" value="{{ $school->id }}">
                                            <input type="hidden" class="delivery-stop-input" name="stops[{{ $seq - 1 }}][sequence_number]" value="{{ $seq }}">
                                            <input type="hidden" class="delivery-stop-input" name="stops[{{ $seq - 1 }}][stop_type]" value="DELIVERY">

                                            <div class="space-y-2 pt-2 border-t border-[#E7ECE8]">
                                                @foreach ($items as $itemIdx => $item)
                                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                                                        <span class="text-[#53645B]">Batch <strong class="font-mono text-[#172923]">{{ $item->foodBatch->batch_code }}</strong></span>
                                                        <div class="flex items-center justify-between sm:justify-end gap-2">
                                                            <span class="text-[11px] text-[#53645B]">Muat:</span>
                                                            <input type="hidden" class="delivery-stop-input" name="stops[{{ $seq - 1 }}][items][{{ $itemIdx }}][delivery_item_id]" value="{{ $item->id }}">
                                                            <input type="number"
                                                                   class="delivery-stop-input w-24 min-h-[38px] px-2.5 py-1 text-xs font-bold text-right text-[#172923] bg-white border border-[#DCE5DD] rounded-lg tabular-nums"
                                                                   name="stops[{{ $seq - 1 }}][items][{{ $itemIdx }}][planned_portions]"
                                                                   value="{{ $item->released_portions }}"
                                                                   max="{{ $item->released_portions }}"
                                                                   min="1"
                                                                   required>
                                                            <span class="text-[11px] text-[#53645B] tabular-nums">/ {{ $item->released_portions }}</span>
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

                        <!-- Pickup Stops -->
                        <div id="section-pickup-stops" class="space-y-3 pt-3 border-t border-[#E7ECE8] hidden">
                            <h3 class="eyebrow-label text-[#172923]">Pilih Sekolah Penjemputan Ompreng</h3>
                            <div class="space-y-2">
                                @foreach ($schools as $school)
                                    @php
                                        $readyTray = $ready_tray_returns->firstWhere('school_id', $school->id);
                                        $hasReady = ($readyTray !== null);
                                        $readyCount = $readyTray?->ready_count ?? 0;
                                    @endphp
                                    <label class="p-3 rounded-xl border {{ $hasReady ? 'bg-[#F9FCFA] border-[#BCE2D0]' : 'bg-[#FAFBF9] border-[#DCE5DD]' }} flex items-center justify-between gap-3 cursor-pointer">
                                        <span class="flex items-center gap-3 min-w-0">
                                            <input type="checkbox"
                                                   class="pickup-school-checkbox w-4 h-4 rounded text-[#1C6654] border-[#DCE5DD]"
                                                   data-school-id="{{ $school->id }}"
                                                   data-school-name="{{ $school->name }}"
                                                   {{ $hasReady ? 'checked' : '' }}
                                                   onchange="rebuildPickupStops()">
                                            <span class="min-w-0">
                                                <span class="text-xs font-bold text-[#172923] block truncate">{{ $school->name }}</span>
                                                <span class="text-[11px] {{ $hasReady ? 'text-[#21684B] font-medium' : 'text-[#53645B]' }}">
                                                    {{ $hasReady ? $readyCount . ' wadah siap dijemput' : 'Belum ada laporan siap' }}
                                                </span>
                                            </span>
                                        </span>
                                        @if ($hasReady)
                                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-[#EDF7F2] text-[#21684B] border border-[#BCE2D0] shrink-0">Prioritas</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                            <div id="dynamic-pickup-stops-container"></div>
                        </div>

                        <div class="pt-3 border-t border-[#E7ECE8] flex justify-end">
                            <button type="submit"
                                    class="w-full sm:w-auto min-h-[46px] px-5 py-2.5 rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs font-semibold inline-flex items-center justify-center gap-2 cursor-pointer smooth-transition">
                                <span id="submit-btn-text">Rencanakan Penugasan Perjalanan</span>
                            </button>
                        </div>
                    </form>
                </x-card>
            </div>

            <!-- Right Column (5 Cols): Active Trips & Dispatch Gate -->
            <div class="lg:col-span-5">
                <x-card title="Monitoring Perjalanan &amp; Gerbang Keberangkatan" badge="{{ $trips->count() }} Rute">
                    @if ($trips->isEmpty())
                        <div class="p-6 inset-surface text-center text-xs text-[#53645B]">
                            Belum ada jadwal perjalanan untuk tanggal {{ \Carbon\Carbon::parse($service_date)->translatedFormat('d F Y') }}.
                        </div>
                    @else
                        @php
                            $selectedTripId = (int) request('trip_id', $trips->first()?->id);
                            $selectedTrip = $trips->firstWhere('id', $selectedTripId) ?? $trips->first();
                        @endphp

                        @if ($trips->count() > 1)
                            <div class="flex items-center gap-1.5 overflow-x-auto pb-2 mb-3">
                                @foreach ($trips as $t)
                                    <a href="{{ route('head.distribution.index', ['service_date' => $service_date, 'trip_id' => $t->id]) }}"
                                       class="min-h-[38px] px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap border inline-flex items-center {{ $t->id === $selectedTrip->id ? 'bg-[#1C6654] text-white border-[#1C6654]' : 'bg-[#FAFBF9] text-[#53645B] border-[#DCE5DD] hover:bg-white' }}">
                                        {{ $t->trip_code }}
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <!-- Map Container -->
                        <div class="mb-4">
                            <div class="flex items-center justify-between mb-1.5 text-xs">
                                <span class="font-semibold text-[#172923]">Peta Rute: <span class="font-mono">{{ $selectedTrip->trip_code }}</span></span>
                                @if ($selectedTrip->total_distance_m)
                                    <span class="text-[11px] text-[#53645B] font-mono tabular-nums">
                                        {{ round($selectedTrip->total_distance_m / 1000, 1) }} km • ~{{ round($selectedTrip->estimated_duration_s / 60) }} mnt
                                    </span>
                                @endif
                            </div>
                            <div id="dispatch-control-map" class="w-full h-56 sm:h-64 rounded-xl overflow-hidden border border-[#DCE5DD] bg-[#FAFBF9]"></div>
                        </div>

                        <div class="space-y-3.5">
                            @foreach ($trips as $trip)
                                <div class="p-4 rounded-xl {{ $trip->id === $selectedTrip->id ? 'bg-white border-2 border-[#1C6654]' : 'bg-[#FAFBF9] border border-[#DCE5DD]' }} space-y-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <span class="text-xs font-mono font-bold text-[#172923] block">{{ $trip->trip_code }}</span>
                                            <span class="text-[11px] text-[#53645B]">
                                                {{ $trip->driver->name }} • {{ $trip->vehicle->plate_number }}
                                            </span>
                                        </div>
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md border {{ $trip->status->badgeClass() }}">
                                            {{ $trip->status->label() }}
                                        </span>
                                    </div>

                                    <!-- 4-Gate Status -->
                                    <div class="inset-surface p-3 space-y-2.5 text-xs">
                                        <div class="grid grid-cols-2 gap-2 text-[11px]">
                                            <div class="flex items-center justify-between bg-white px-2.5 py-1.5 rounded-lg border border-[#E7ECE8]">
                                                <span class="text-[#53645B]">QC</span>
                                                <span class="font-semibold text-[#21684B]">Lolos</span>
                                            </div>
                                            <div class="flex items-center justify-between bg-white px-2.5 py-1.5 rounded-lg border border-[#E7ECE8]">
                                                <span class="text-[#53645B]">Muatan</span>
                                                <span class="font-semibold {{ $trip->loading_verified ? 'text-[#21684B]' : 'text-[#9B3232]' }}">{{ $trip->loading_verified ? 'Sah' : 'Belum' }}</span>
                                            </div>
                                            <div class="flex items-center justify-between bg-white px-2.5 py-1.5 rounded-lg border border-[#E7ECE8]">
                                                <span class="text-[#53645B]">Armada</span>
                                                <span class="font-semibold {{ $trip->vehicle_check_passed ? 'text-[#21684B]' : 'text-[#53645B]' }}">{{ $trip->vehicle_check_passed ? 'Laik' : 'Menunggu' }}</span>
                                            </div>
                                            <div class="flex items-center justify-between bg-white px-2.5 py-1.5 rounded-lg border border-[#E7ECE8]">
                                                <span class="text-[#53645B]">Izin</span>
                                                <span class="font-semibold {{ $trip->dispatch_approved ? 'text-[#21684B]' : 'text-[#53645B]' }}">{{ $trip->dispatch_approved ? 'Disahkan' : 'Menunggu' }}</span>
                                            </div>
                                        </div>

                                        @if (! $trip->loading_verified && in_array($trip->status->value, ['ASSIGNED', 'VEHICLE_CHECKED']))
                                            <form method="POST" action="{{ route('head.distribution.trips.verify-loading', $trip) }}" class="pt-2 border-t border-[#E7ECE8] space-y-2">
                                                @csrf
                                                <span class="text-[11px] font-semibold text-[#172923] block">Verifikasi Muatan Fisik</span>
                                                @foreach ($trip->stops as $stop)
                                                    @foreach ($stop->deliveryItems as $item)
                                                        <div class="flex items-center justify-between gap-2 text-[11px]">
                                                            <span class="truncate">{{ $stop->school->name }}</span>
                                                            <div class="flex items-center gap-1 shrink-0">
                                                                <input type="number"
                                                                       name="items[{{ $item->id }}]"
                                                                       value="{{ $item->planned_portions ?: $item->released_portions }}"
                                                                       max="{{ $item->released_portions }}"
                                                                       min="1"
                                                                       required
                                                                       class="w-16 min-h-[36px] px-2 py-1 text-xs text-right font-bold bg-white border border-[#DCE5DD] rounded-lg tabular-nums">
                                                                <span class="text-[10px] text-[#53645B]">/ {{ $item->released_portions }}</span>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @endforeach
                                                <button type="submit"
                                                        class="w-full min-h-[40px] rounded-xl bg-[#285F80] hover:bg-[#1E4861] text-white text-xs font-semibold cursor-pointer smooth-transition">
                                                    Sahkan Verifikasi Muatan Fisik
                                                </button>
                                            </form>
                                        @endif

                                        @if ($trip->loading_verified && $trip->vehicle_check_passed && ! $trip->dispatch_approved && in_array($trip->status->value, ['ASSIGNED', 'VEHICLE_CHECKED']))
                                            <form method="POST" action="{{ route('head.distribution.trips.approve-dispatch', $trip) }}" class="pt-2 border-t border-[#E7ECE8]">
                                                @csrf
                                                <button type="submit"
                                                        class="w-full min-h-[42px] rounded-xl bg-[#1C6654] hover:bg-[#164D42] text-white text-xs font-semibold cursor-pointer smooth-transition">
                                                    Sahkan Keberangkatan (READY)
                                                </button>
                                            </form>
                                        @endif
                                    </div>

                                    <!-- Stops Summary -->
                                    <div class="space-y-1 text-xs">
                                        @foreach ($trip->stops as $stop)
                                            <div class="flex items-center justify-between gap-2 py-1 border-b border-[#E7ECE8] last:border-b-0">
                                                <span class="text-[#172923] truncate">{{ $stop->sequence_number }}. {{ $stop->school->name }}</span>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded border shrink-0 {{ $stop->status->badgeClass() }}">{{ $stop->status->label() }}</span>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="pt-2 border-t border-[#E7ECE8] flex items-center justify-between gap-2">
                                        <a href="{{ route('trips.show', $trip) }}"
                                           class="min-h-[38px] text-xs font-semibold text-[#1C6654] hover:underline inline-flex items-center gap-1">
                                            <span>Detail Perjalanan</span>
                                            <span aria-hidden="true">&rarr;</span>
                                        </a>

                                        @if (in_array($trip->status->value, ['DRAFT', 'ASSIGNED', 'VEHICLE_CHECKED', 'READY']))
                                            <form method="POST" action="{{ route('head.distribution.trips.cancel', $trip) }}"
                                                  onsubmit="return confirm('Batalkan perjalanan ini? Alokasi makanan akan dikembalikan ke daftar siap muat.')">
                                                @csrf
                                                <input type="hidden" name="reason" value="Dibatalkan oleh Kepala SPPG sebelum keberangkatan">
                                                <button type="submit" class="min-h-[38px] px-2 text-xs font-semibold text-[#9B3232] hover:underline cursor-pointer">
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
                </x-card>
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
