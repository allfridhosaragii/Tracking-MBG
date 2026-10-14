<x-app-shell title="Pemeriksaan & Penerimaan Makanan — {{ $school->name }}">
    <div class="max-w-5xl mx-auto px-4 py-6 sm:px-6 space-y-6">

        <!-- Navigasi & Breadcrumb -->
        <div class="flex items-center justify-between">
            <a href="{{ route('school.receipts.index') }}" 
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#53645B] hover:text-[#172923] touch-target">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>Daftar Penerimaan Makanan (P04)</span>
            </a>
            <div class="flex items-center gap-2">
                <span class="text-[11px] font-bold text-[#53645B] uppercase tracking-wider">Halaman P04</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full font-bold {{ $stop->status->badgeClass() }}">
                    {{ $stop->status->label() }}
                </span>
            </div>
        </div>

        <!-- Banner Header Kedatangan -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-[#EDF2ED]">
                <div class="flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-full bg-[#1C6654] text-white flex items-center justify-center text-sm font-bold shrink-0 mt-0.5">
                        {{ $stop->sequence_number }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-bold text-[#172923]">{{ $school->name }}</h1>
                            <span class="text-xs text-[#53645B] bg-[#F5F7F3] px-2 py-0.5 rounded font-medium">NPSN: {{ $school->npsn }}</span>
                        </div>
                        <p class="text-xs text-[#53645B] mt-0.5 leading-relaxed">{{ $school->address }}</p>
                    </div>
                </div>
                <div class="text-left sm:text-right shrink-0">
                    <span class="text-[11px] text-[#53645B] block font-medium">Waktu Kedatangan Armada</span>
                    <span class="text-sm font-bold text-[#172923] tabular-nums">
                        {{ $stop->arrived_at ? $stop->arrived_at->setTimezone('Asia/Jakarta')->format('H:i:s \W\I\B') : 'Belum tiba' }}
                    </span>
                    <span class="text-[11px] text-[#708177] block font-mono">Trip: {{ $trip->trip_code }}</span>
                </div>
            </div>

            <!-- Detail Kurir & Kendaraan -->
            <div class="mt-4 pt-1 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs text-[#53645B]">
                <div>
                    <span class="text-[#708177] block text-[10px]">Kurir Pengantar</span>
                    <span class="font-bold text-[#172923]">{{ $trip->driver?->name ?? 'Kurir SPPG' }}</span>
                </div>
                <div>
                    <span class="text-[#708177] block text-[10px]">Nomor Plat Armada</span>
                    <span class="font-bold text-[#172923]">{{ $trip->vehicle?->plate_number ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-[#708177] block text-[10px]">Menu Makanan</span>
                    <span class="font-bold text-[#172923]">{{ $primaryMenu?->recipe_name ?? 'Menu Standar MBG' }}</span>
                </div>
                <div>
                    <span class="text-[#708177] block text-[10px]">Kode Batch Produksi</span>
                    <span class="font-bold font-mono text-[#1C6654]">{{ $primaryBatch?->batch_code ?? '-' }}</span>
                </div>
            </div>
        </div>

        @php
            $isConfirmed = $serviceRecord && $serviceRecord->isConfirmed();
            $arrivalQc = $stop->qualityChecks->where('check_stage', \App\Enums\QualityCheckStage::ON_ARRIVAL)->sortByDesc('created_at')->first();
            $driverPhoto = $stop->evidencePhotos->where('evidence_type', 'SCHOOL_HANDOVER')->sortByDesc('created_at')->first();
            $expectedPortions = $serviceRecord?->expected_portions ?: ((int) ($stop->deliveryItems->sum('loaded_portions') ?: $stop->loaded_portions));
        @endphp

        <!-- Tampilan Sertifikat / Berita Acara Penerimaan (Read-only jika sudah dikonfirmasi) -->
        @if ($isConfirmed)
            <div class="bg-white rounded-2xl border-2 {{ $serviceRecord->status === \App\Enums\SchoolReceiptStatus::REJECTED || $serviceRecord->status === \App\Enums\SchoolReceiptStatus::HELD ? 'border-rose-400' : 'border-emerald-500' }} p-6 shadow-xs space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-[#EDF2ED]">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full {{ $serviceRecord->status === \App\Enums\SchoolReceiptStatus::REJECTED || $serviceRecord->status === \App\Enums\SchoolReceiptStatus::HELD ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }} flex items-center justify-center font-bold">
                            @if ($serviceRecord->status === \App\Enums\SchoolReceiptStatus::REJECTED || $serviceRecord->status === \App\Enums\SchoolReceiptStatus::HELD)
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            @else
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @endif
                        </div>
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-[#53645B] block">Berita Acara Serah Terima</span>
                            <h2 class="text-lg font-bold text-[#172923]">{{ $serviceRecord->status->label() }}</h2>
                        </div>
                    </div>
                    <div class="text-left sm:text-right">
                        <span class="text-xs px-3 py-1 rounded-full font-bold {{ $serviceRecord->status->badgeClass() }}">
                            Status: {{ $serviceRecord->status->value }}
                        </span>
                        <span class="text-[11px] text-[#53645B] block mt-1 tabular-nums">
                            Disahkan: {{ $serviceRecord->received_at?->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s \W\I\B') }}
                        </span>
                    </div>
                </div>

                @if ($serviceRecord->status === \App\Enums\SchoolReceiptStatus::HELD || $serviceRecord->status === \App\Enums\SchoolReceiptStatus::REJECTED)
                    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs space-y-1">
                        <strong class="font-bold flex items-center gap-1.5 text-rose-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            PERINGATAN KESELAMATAN PANGAN &mdash; DISTRIBUSI DITAHAN
                        </strong>
                        <p class="leading-relaxed">
                            Hasil pemeriksaan organoleptik kedatangan menunjukkan ketidaklayakan pangan. Makanan TIDAK BOLEH didistribusikan kepada peserta didik. Insiden otomatis diteruskan ke Pengawas Gizi dan Kepala SPPG.
                        </p>
                    </div>
                @endif

                <!-- Ringkasan Rekonsiliasi Porsi Fisik -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                    <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD]">
                        <span class="text-[10px] text-[#53645B] block font-medium">Target / Dikirim</span>
                        <span class="text-base font-bold text-[#172923] tabular-nums mt-0.5 block">{{ $serviceRecord->expected_portions }}</span>
                        <span class="text-[10px] text-[#708177]">Porsi</span>
                    </div>
                    <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200">
                        <span class="text-[10px] text-emerald-800 block font-medium">Fisik Diterima</span>
                        <span class="text-base font-bold text-emerald-900 tabular-nums mt-0.5 block">{{ $serviceRecord->received_portions }}</span>
                        <span class="text-[10px] text-emerald-700">Porsi</span>
                    </div>
                    <div class="p-3 rounded-xl bg-amber-50 border border-amber-200">
                        <span class="text-[10px] text-amber-800 block font-medium">Porsi Rusak</span>
                        <span class="text-base font-bold text-amber-900 tabular-nums mt-0.5 block">{{ $serviceRecord->damaged_portions }}</span>
                        <span class="text-[10px] text-amber-700">Porsi</span>
                    </div>
                    <div class="p-3 rounded-xl {{ $serviceRecord->shortage_portions > 0 ? 'bg-rose-50 border-rose-200 text-rose-900' : 'bg-[#F5F7F3] border-[#DCE5DD] text-[#172923]' }} border">
                        <span class="text-[10px] block font-medium">Selisih Kurang</span>
                        <span class="text-base font-bold tabular-nums mt-0.5 block">{{ $serviceRecord->shortage_portions }}</span>
                        <span class="text-[10px]">Porsi</span>
                    </div>
                </div>

                <!-- Hasil Pemeriksaan Organoleptik Kedatangan -->
                @if ($arrivalQc)
                    <div class="p-4 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD] space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-[#DCE5DD]">
                            <h3 class="text-xs font-bold text-[#172923] uppercase tracking-wider">Hasil Uji Organoleptik Kedatangan</h3>
                            <span class="text-xs font-bold px-2 py-0.5 rounded {{ $arrivalQc->status->badgeClass() }}">
                                {{ $arrivalQc->status->label() }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-xs">
                            <div class="p-2 rounded bg-white border border-[#DCE5DD]">
                                <span class="text-[10px] text-[#708177] block">Tampilan</span>
                                <span class="font-bold text-[#172923]">{{ $arrivalQc->appearance_status ?? '-' }}</span>
                            </div>
                            <div class="p-2 rounded bg-white border border-[#DCE5DD]">
                                <span class="text-[10px] text-[#708177] block">Aroma</span>
                                <span class="font-bold text-[#172923]">{{ $arrivalQc->aroma_status ?? '-' }}</span>
                            </div>
                            <div class="p-2 rounded bg-white border border-[#DCE5DD]">
                                <span class="text-[10px] text-[#708177] block">Tekstur</span>
                                <span class="font-bold text-[#172923]">{{ $arrivalQc->texture_status ?? '-' }}</span>
                            </div>
                            <div class="p-2 rounded bg-white border border-[#DCE5DD]">
                                <span class="text-[10px] text-[#708177] block">Uji Rasa</span>
                                <span class="font-bold text-[#172923]">{{ $arrivalQc->taste_status ?? ($arrivalQc->taste_tested ? 'Normal' : 'Dilewati') }}</span>
                            </div>
                            <div class="p-2 rounded bg-white border border-[#DCE5DD]">
                                <span class="text-[10px] text-[#708177] block">Suhu Kedatangan</span>
                                <span class="font-bold text-[#172923] tabular-nums">{{ $arrivalQc->temperature_c ? "{$arrivalQc->temperature_c} °C" : '-' }}</span>
                            </div>
                        </div>
                        @if ($arrivalQc->taste_skipped_reason)
                            <div class="text-[11px] text-amber-800 bg-amber-50 p-2 rounded border border-amber-200">
                                <strong>Alasan Uji Rasa Dilewati:</strong> {{ $arrivalQc->taste_skipped_reason }}
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Informasi PIC Penerima & Catatan -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-[#53645B] pt-2 border-t border-[#EDF2ED]">
                    <div>
                        <span class="text-[#708177]">Petugas Penerima:</span>
                        <strong class="text-[#172923] ml-1">{{ $serviceRecord->receiver?->name ?? 'PIC Sekolah' }}</strong>
                    </div>
                    @if ($serviceRecord->notes)
                        <div>
                            <span class="text-[#708177]">Catatan:</span>
                            <span class="text-[#172923] ml-1">{{ $serviceRecord->notes }}</span>
                        </div>
                    @endif
                </div>
            </div>

        @else

            <!-- Form Pemeriksaan Fisik & Konfirmasi Penerimaan (P04) -->
            <form action="{{ route('school.receipts.confirm', $stop) }}" 
                  method="POST" 
                  enctype="multipart/form-data" 
                  class="space-y-6"
                  id="receipt-confirmation-form">
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    <!-- Kolom Kiri (7 Kolom): Manifest Porsi & Organoleptik -->
                    <div class="lg:col-span-7 space-y-6">

                        @if ($expectedPortions <= 0)
                            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
                                <strong class="font-bold flex items-center gap-1.5 text-rose-900">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    MANIFEST MUATAN TIDAK VALID / BELUM DIMUAT
                                </strong>
                                <p>Jumlah porsi fisik termuat (loaded portions) pada armada bernilai 0. Tanda terima fisik tidak dapat diproses tanpa verifikasi muatan dari SPPG.</p>
                            </div>
                        @endif

                        @if ($stop->deliveryItems->count() > 1)
                            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-4 shadow-xs space-y-3">
                                <div class="flex items-center justify-between pb-2 border-b border-[#EDF2ED]">
                                    <h3 class="text-xs font-bold text-[#172923] uppercase tracking-wider flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-[#1C6654]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                        </svg>
                                        Rincian Multi-Batch Pengiriman ({{ $stop->deliveryItems->count() }} Batch)
                                    </h3>
                                    <span class="text-xs font-bold text-[#1C6654] tabular-nums">Total: {{ $expectedPortions }} Porsi</span>
                                </div>
                                <div class="space-y-2">
                                    @foreach ($stop->deliveryItems as $item)
                                        <div class="p-2.5 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD] flex items-center justify-between text-xs">
                                            <div>
                                                <span class="font-mono font-bold text-[#1C6654]">{{ $item->foodBatch?->batch_code ?? 'BATCH-' . $item->id }}</span>
                                                <span class="text-[11px] text-[#53645B] block">{{ $item->foodBatch?->menu?->recipe_name ?? 'Menu MBG' }}</span>
                                            </div>
                                            <div class="text-right">
                                                <span class="font-bold text-[#172923] tabular-nums">{{ $item->loaded_portions ?? 0 }} Porsi</span>
                                                <span class="text-[10px] text-[#708177] block">Dimuat di SPPG</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- 1. Rekonsiliasi Jumlah Porsi Fisik -->
                        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 shadow-xs space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-[#EDF2ED]">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-[#1C6654] text-white text-xs font-bold flex items-center justify-center">1</span>
                                    <h2 class="text-sm font-bold text-[#172923]">Penghitungan Porsi Fisik</h2>
                                </div>
                                <span class="text-xs font-bold text-[#1C6654] tabular-nums">
                                    Dikirim: {{ $expectedPortions }} Porsi
                                </span>
                            </div>

                            <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD] text-xs text-[#53645B] flex items-center justify-between">
                                <span>Rumus Selisih:</span>
                                <span class="font-mono text-[#172923] font-bold">Selisih = max(0, Dikirim - Diterima)</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="received_portions" class="block text-xs font-bold text-[#172923] mb-1.5">
                                        Porsi Fisik Diterima <span class="text-rose-600">*</span>
                                    </label>
                                    <input type="number" 
                                           name="received_portions" 
                                           id="received_portions" 
                                           min="0" 
                                           max="5000" 
                                           value="{{ old('received_portions', $expectedPortions) }}" 
                                           required
                                           class="w-full text-sm font-bold text-[#172923] rounded-xl border border-[#DCE5DD] p-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] tabular-nums"
                                           oninput="calculateShortage()">
                                    <span class="text-[10px] text-[#708177] mt-1 block">Total ompreng yang diserahkan kurir.</span>
                                    @error('received_portions')
                                        <span class="text-xs text-rose-600 font-medium block mt-1">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label for="damaged_portions" class="block text-xs font-bold text-[#172923] mb-1.5">
                                        Porsi Rusak / Tumpah (Bagian Diterima)
                                    </label>
                                    <input type="number" 
                                           name="damaged_portions" 
                                           id="damaged_portions" 
                                           min="0" 
                                           max="5000" 
                                           value="{{ old('damaged_portions', 0) }}" 
                                           class="w-full text-sm font-bold text-[#172923] rounded-xl border border-[#DCE5DD] p-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] tabular-nums"
                                           oninput="calculateShortage()">
                                    <span class="text-[10px] text-[#708177] mt-1 block">Wadah pecah/isi tercemar saat perjalanan.</span>
                                    @error('damaged_portions')
                                        <span class="text-xs text-rose-600 font-medium block mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Live Derived Shortage Preview -->
                            <div class="p-3.5 rounded-xl border border-[#DCE5DD] bg-white flex items-center justify-between text-xs">
                                <div>
                                    <span class="font-bold text-[#172923] block">Kalkulasi Selisih Otomatis:</span>
                                    <span class="text-[11px] text-[#53645B]" id="shortage-desc">Tidak ada kekurangan porsi.</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-lg font-bold tabular-nums text-[#1C6654]" id="shortage-value">0</span>
                                    <span class="text-[10px] text-[#708177] block">Porsi Kurang</span>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Pemeriksaan Organoleptik & Keamanan Pangan -->
                        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 shadow-xs space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-[#EDF2ED]">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-[#1C6654] text-white text-xs font-bold flex items-center justify-center">2</span>
                                    <h2 class="text-sm font-bold text-[#172923]">Uji Organoleptik Kedatangan</h2>
                                </div>
                                <span class="text-[11px] text-[#53645B] font-medium">Standar Gizi & Keamanan</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Tampilan Fisik -->
                                <div>
                                    <label for="appearance_status" class="block text-xs font-bold text-[#172923] mb-1.5">
                                        Tampilan Fisik <span class="text-rose-600">*</span>
                                    </label>
                                    <select name="appearance_status" 
                                            id="appearance_status" 
                                            required
                                            class="w-full text-xs text-[#172923] rounded-xl border border-[#DCE5DD] p-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                        <option value="FRESH" {{ old('appearance_status') === 'FRESH' ? 'selected' : '' }}>Segar & Bersih (Sesuai)</option>
                                        <option value="ABNORMAL" {{ old('appearance_status') === 'ABNORMAL' ? 'selected' : '' }}>Abnormal / Berubah Warna</option>
                                        <option value="SPOILED" {{ old('appearance_status') === 'SPOILED' ? 'selected' : '' }}>Basi / Berlendir / Rusak Parah</option>
                                    </select>
                                    @error('appearance_status')
                                        <span class="text-xs text-rose-600 font-medium block mt-1">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Aroma -->
                                <div>
                                    <label for="aroma_status" class="block text-xs font-bold text-[#172923] mb-1.5">
                                        Aroma / Bau <span class="text-rose-600">*</span>
                                    </label>
                                    <select name="aroma_status" 
                                            id="aroma_status" 
                                            required
                                            class="w-full text-xs text-[#172923] rounded-xl border border-[#DCE5DD] p-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                        <option value="NORMAL" {{ old('aroma_status') === 'NORMAL' ? 'selected' : '' }}>Harum Masakan Normal (Segar)</option>
                                        <option value="SOUR" {{ old('aroma_status') === 'SOUR' ? 'selected' : '' }}>Asam / Menyengat</option>
                                        <option value="RANCID" {{ old('aroma_status') === 'RANCID' ? 'selected' : '' }}>Tengik / Basi</option>
                                        <option value="CHEMICAL" {{ old('aroma_status') === 'CHEMICAL' ? 'selected' : '' }}>Bau Kimia / Asing</option>
                                    </select>
                                    @error('aroma_status')
                                        <span class="text-xs text-rose-600 font-medium block mt-1">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Tekstur -->
                                <div>
                                    <label for="texture_status" class="block text-xs font-bold text-[#172923] mb-1.5">
                                        Tekstur <span class="text-rose-600">*</span>
                                    </label>
                                    <select name="texture_status" 
                                            id="texture_status" 
                                            required
                                            class="w-full text-xs text-[#172923] rounded-xl border border-[#DCE5DD] p-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                        <option value="NORMAL" {{ old('texture_status') === 'NORMAL' ? 'selected' : '' }}>Matang Normal & Empuk</option>
                                        <option value="SLIMY" {{ old('texture_status') === 'SLIMY' ? 'selected' : '' }}>Berlendir / Lengket</option>
                                        <option value="DRY" {{ old('texture_status') === 'DRY' ? 'selected' : '' }}>Terlalu Kering / Keras</option>
                                        <option value="SOGGY" {{ old('texture_status') === 'SOGGY' ? 'selected' : '' }}>Lembek Berair / Hancur</option>
                                    </select>
                                    @error('texture_status')
                                        <span class="text-xs text-rose-600 font-medium block mt-1">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Suhu Kedatangan (°C) -->
                                <div>
                                    <label for="temperature_c" class="block text-xs font-bold text-[#172923] mb-1.5">
                                        Suhu Kedatangan (°C)
                                    </label>
                                    <input type="number" 
                                           step="0.1" 
                                           name="temperature_c" 
                                           id="temperature_c" 
                                           placeholder="Contoh: 60.5" 
                                           value="{{ old('temperature_c', 60.0) }}" 
                                           class="w-full text-xs text-[#172923] rounded-xl border border-[#DCE5DD] p-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654] tabular-nums">
                                    <span class="text-[10px] text-[#708177] mt-1 block">Standar makanan hangat >= 60°C.</span>
                                    @error('temperature_c')
                                        <span class="text-xs text-rose-600 font-medium block mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Uji Rasa dengan Perlindungan Safety First -->
                            <div class="pt-2 border-t border-[#EDF2ED] space-y-3">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" 
                                           name="taste_tested" 
                                           id="taste_tested" 
                                           value="1" 
                                           {{ old('taste_tested', '1') ? 'checked' : '' }} 
                                           onchange="toggleTasteTest()"
                                           class="w-4 h-4 rounded text-[#1C6654] focus:ring-[#1C6654]">
                                    <label for="taste_tested" class="text-xs font-bold text-[#172923]">
                                        Uji Rasa Dilakukan (Cicipi Sampel)
                                    </label>
                                </div>
                                <span class="text-[10px] text-[#53645B] block -mt-1 leading-relaxed">
                                    Jangan mencicipi jika makanan berbau basi, tengik, atau berlendir demi keselamatan pangan!
                                </span>

                                <div id="taste-section-enabled" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="taste_status" class="block text-xs font-bold text-[#172923] mb-1.5">
                                            Kualitas Rasa
                                        </label>
                                        <select name="taste_status" 
                                                id="taste_status" 
                                                class="w-full text-xs text-[#172923] rounded-xl border border-[#DCE5DD] p-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                            <option value="DELICIOUS_NORMAL" {{ old('taste_status') === 'DELICIOUS_NORMAL' ? 'selected' : '' }}>Enak & Bumbu Pas (Normal)</option>
                                            <option value="OFF_TASTE" {{ old('taste_status') === 'OFF_TASTE' ? 'selected' : '' }}>Rasa Aneh / Asing</option>
                                            <option value="BITTER_SOUR" {{ old('taste_status') === 'BITTER_SOUR' ? 'selected' : '' }}>Pahit / Asam / Basi</option>
                                        </select>
                                    </div>
                                </div>

                                <div id="taste-section-disabled" class="hidden">
                                    <label for="taste_skipped_reason" class="block text-xs font-bold text-amber-800 mb-1.5">
                                        Alasan Uji Rasa Dilewati <span class="text-rose-600">*</span>
                                    </label>
                                    <input type="text" 
                                           name="taste_skipped_reason" 
                                           id="taste_skipped_reason" 
                                           placeholder="Contoh: Makanan tercium asam/basi, berisiko racun jika dicicipi"
                                           value="{{ old('taste_skipped_reason') }}" 
                                           class="w-full text-xs text-[#172923] rounded-xl border border-amber-300 bg-amber-50 p-2.5 focus:outline-none focus:ring-2 focus:ring-amber-500">
                                    @error('taste_skipped_reason')
                                        <span class="text-xs text-rose-600 font-medium block mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Keputusan Akhir Organoleptik -->
                            <div class="pt-3 border-t border-[#EDF2ED] space-y-2">
                                <label for="organoleptic_result" class="block text-xs font-bold text-[#172923]">
                                    Keputusan Kelayakan Pangan <span class="text-rose-600">*</span>
                                </label>
                                <select name="organoleptic_result" 
                                        id="organoleptic_result" 
                                        required
                                        onchange="updateSafetyWarning()"
                                        class="w-full text-xs font-bold text-[#172923] rounded-xl border border-[#DCE5DD] p-3 bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                    <option value="SAFE" {{ old('organoleptic_result') === 'SAFE' ? 'selected' : '' }}>
                                        LAYAK & AMAN (Boleh Disajikan ke Siswa)
                                    </option>
                                    <option value="WARNING" {{ old('organoleptic_result') === 'WARNING' ? 'selected' : '' }}>
                                        DENGAN CATATAN (Aman, namun ada catatan kemasan/suhu)
                                    </option>
                                    <option value="UNSAFE" {{ old('organoleptic_result') === 'UNSAFE' ? 'selected' : '' }}>
                                        TIDAK LAYAK / BERBAHAYA (TAHAN & BATALKAN DISTRIBUSI)
                                    </option>
                                </select>
                                <div id="safety-alert" class="hidden p-3 rounded-xl bg-rose-50 border border-rose-300 text-rose-900 text-xs">
                                    <strong>Peringatan Tegas:</strong> Memilih "TIDAK LAYAK / BERBAHAYA" akan membekukan batch makanan sekolah ini dalam status HELD/REJECTED. Makanan tidak akan dapat didistribusikan ke siswa.
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Kolom Kanan (5 Kolom): Foto Driver, Bukti Masalah & Submit -->
                    <div class="lg:col-span-5 space-y-6">

                        <!-- Foto Serah Terima Kurir (Referensi) -->
                        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 shadow-xs space-y-3">
                            <h2 class="text-sm font-bold text-[#172923]">Foto Serah Terima Kurir</h2>

                            @if ($driverPhoto)
                                <div class="relative rounded-xl overflow-hidden border border-[#DCE5DD] bg-slate-100 aspect-video flex items-center justify-center">
                                    <img src="{{ route('evidence.show', $driverPhoto) }}" 
                                         alt="Bukti Serah Terima Kurir" 
                                         class="w-full h-full object-cover">
                                </div>
                                <div class="text-[11px] text-[#53645B] space-y-0.5">
                                    <div class="flex justify-between">
                                        <span>Diunggah pada:</span>
                                        <span class="font-bold text-[#172923] tabular-nums">
                                            {{ $driverPhoto->created_at->setTimezone('Asia/Jakarta')->format('H:i:s \W\I\B') }}
                                        </span>
                                    </div>
                                    @if ($driverPhoto->notes)
                                        <span class="block text-[#172923] italic">"{{ $driverPhoto->notes }}"</span>
                                    @endif
                                </div>
                            @else
                                <div class="p-4 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD] text-center text-xs text-[#53645B]">
                                    Kurir belum mengunggah foto serah terima. Anda tetap dapat melanjutkan konfirmasi fisik.
                                </div>
                            @endif
                        </div>

                        <!-- Bukti Foto Selisih / Kerusakan (PIC) -->
                        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 shadow-xs space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-[#EDF2ED]">
                                <h2 class="text-sm font-bold text-[#172923]">Bukti Foto PIC Sekolah</h2>
                                <span class="text-[10px] text-[#708177]">Wajib bila ada selisih</span>
                            </div>

                            <div>
                                <label for="evidence_photo" class="block text-xs font-bold text-[#172923] mb-1.5">
                                    Unggah Foto Wadah / Selisih / Kondisi Makanan
                                </label>
                                <input type="file" 
                                       name="evidence_photo" 
                                       id="evidence_photo" 
                                       accept="image/*" 
                                       capture="environment" 
                                       class="block w-full text-xs text-[#172923] file:mr-3 file:py-2.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#1C6654] file:text-white hover:file:bg-[#154E40] file:cursor-pointer border border-[#DCE5DD] rounded-xl p-2 bg-[#F5F7F3] focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                                <span class="text-[10px] text-[#708177] mt-1 block">
                                    Diperlukan sebagai bukti audit jika ada porsi rusak atau selisih kuantitas.
                                </span>
                                @error('evidence_photo')
                                    <span class="text-xs text-rose-600 font-medium block mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="notes" class="block text-xs font-bold text-[#172923] mb-1.5">
                                    Catatan Berita Acara Penerimaan
                                </label>
                                <textarea name="notes" 
                                          id="notes" 
                                          rows="3" 
                                          placeholder="Tuliskan keterangan bila ada porsi rusak, selisih jumlah, atau kondisi khusus..."
                                          class="w-full text-xs text-[#172923] rounded-xl border border-[#DCE5DD] p-2.5 focus:outline-none focus:ring-2 focus:ring-[#1C6654]">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <span class="text-xs text-rose-600 font-medium block mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Tombol Aksi Konfirmasi Utama -->
                            <div class="pt-2">
                                <button type="submit" 
                                        id="submit-receipt-btn"
                                        class="w-full py-3.5 px-4 rounded-xl font-bold text-sm bg-[#1C6654] text-white hover:bg-[#154E40] transition-colors shadow-sm flex items-center justify-center gap-2 touch-target">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Sahkan Penerimaan Makanan (P04)</span>
                                </button>
                                <span class="text-[10px] text-[#708177] text-center block mt-2">
                                    Tindakan ini mengesahkan serah terima dan mengaktifkan rute armada ke sekolah berikutnya.
                                </span>
                            </div>
                        </div>

                    </div>

                </div>
            </form>

            <script>
                function calculateShortage() {
                    const expected = {{ (int) $expectedPortions }};
                    const receivedInput = document.getElementById('received_portions');
                    const damagedInput = document.getElementById('damaged_portions');
                    const shortageVal = document.getElementById('shortage-value');
                    const shortageDesc = document.getElementById('shortage-desc');

                    const received = parseInt(receivedInput.value) || 0;
                    const damaged = parseInt(damagedInput.value) || 0;
                    const shortage = Math.max(0, expected - received);

                    shortageVal.innerText = shortage;
                    if (shortage > 0 || damaged > 0) {
                        shortageVal.className = 'text-lg font-bold tabular-nums text-rose-600';
                        shortageDesc.innerText = `Terjadi selisih kurang ${shortage} porsi dan ${damaged} rusak.`;
                    } else {
                        shortageVal.className = 'text-lg font-bold tabular-nums text-[#1C6654]';
                        shortageDesc.innerText = 'Jumlah porsi sesuai dengan muatan dikirim.';
                    }
                }

                function toggleTasteTest() {
                    const checkbox = document.getElementById('taste_tested');
                    const enabledSection = document.getElementById('taste-section-enabled');
                    const disabledSection = document.getElementById('taste-section-disabled');
                    const tasteStatus = document.getElementById('taste_status');
                    const skipReason = document.getElementById('taste_skipped_reason');

                    if (checkbox.checked) {
                        enabledSection.classList.remove('hidden');
                        disabledSection.classList.add('hidden');
                        if (skipReason) skipReason.required = false;
                    } else {
                        enabledSection.classList.add('hidden');
                        disabledSection.classList.remove('hidden');
                        if (skipReason) skipReason.required = true;
                    }
                }

                function updateSafetyWarning() {
                    const select = document.getElementById('organoleptic_result');
                    const alertBox = document.getElementById('safety-alert');
                    const submitBtn = document.getElementById('submit-receipt-btn');

                    if (select.value === 'UNSAFE') {
                        alertBox.classList.remove('hidden');
                        submitBtn.classList.remove('bg-[#1C6654]', 'hover:bg-[#154E40]');
                        submitBtn.classList.add('bg-rose-600', 'hover:bg-rose-700');
                        submitBtn.innerText = 'TAHAN & BATALKAN DISTRIBUSI (UNSAFE)';
                    } else {
                        alertBox.classList.add('hidden');
                        submitBtn.classList.remove('bg-rose-600', 'hover:bg-rose-700');
                        submitBtn.classList.add('bg-[#1C6654]', 'hover:bg-[#154E40]');
                        submitBtn.innerText = 'Sahkan Penerimaan Makanan (P04)';
                    }
                }

                // Inisialisasi awal kalkulasi
                document.addEventListener('DOMContentLoaded', function () {
                    calculateShortage();
                    toggleTasteTest();
                    updateSafetyWarning();
                });
            </script>
        @endif

    </div>
</x-app-shell>
