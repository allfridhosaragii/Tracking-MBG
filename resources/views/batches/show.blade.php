<x-app-shell title="Detail &amp; Jejak Batch : {{ $batch->batch_code }}">
    <!-- Header Jejak Batch (Page C02) -->
    <header class="mb-6 pb-4 border-b border-[#DCE5DD]">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                    <x-role-badge :role="auth()->user()->role" />
                    <span class="text-xs font-mono text-[#53645B] bg-white border border-[#DCE5DD] px-2 py-0.5 rounded">
                        SPPG: {{ $plan->sppg->code }}
                    </span>
                    @php
                        $badgeStyle = match($batch->status->value) {
                            'RELEASED' => 'bg-[#EDF7F2] text-[#1B573E] border-[#BCE2D0]',
                            'ON_HOLD' => 'bg-[#FDF2F2] text-[#9B3232] border-[#F5C2C2]',
                            'QC_CHECK' => 'bg-[#FAF4EB] text-[#885D1C] border-[#E8D7BE]',
                            'PACKED' => 'bg-[#F0F5FA] text-[#285F80] border-[#BCD4E6]',
                            default => 'bg-[#EDF2ED] text-[#53645B] border-[#DCE5DD]'
                        };
                    @endphp
                    <span class="text-xs font-bold px-2 py-0.5 rounded border {{ $badgeStyle }}">
                        Status Batch: {{ $batch->status->label() }}
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">
                    Jejak Lengkap &amp; Identitas Batch: {{ $batch->batch_code }}
                </h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">
                    Menu: <strong>{{ $menu->name }} (v{{ $menu->version }})</strong> : Tanggal Layanan: <strong>{{ $plan->service_date->translatedFormat('l, d F Y') }}</strong>
                </p>
            </div>

            <!-- Aksi Kontekstual Berdasarkan Role -->
            <div class="flex flex-wrap items-center gap-2">
                @if (auth()->user()->role === \App\Enums\UserRole::ASISTEN_LAPANGAN)
                    <a href="{{ route('production.plans.execute', $plan) }}" 
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-[#DCE5DD] text-xs font-semibold text-[#172923] hover:bg-[#FAFBF9] transition-colors touch-target">
                        <svg class="w-4 h-4 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>Workspace Dapur (A02)</span>
                    </a>
                @elseif (auth()->user()->role === \App\Enums\UserRole::PENGAWAS_GIZI)
                    <a href="{{ route('nutrition.qc.show', $batch) }}" 
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-[#285F80] hover:bg-[#1E4861] text-white text-xs font-bold rounded-xl shadow-xs transition-colors touch-target">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Meja Inspeksi QC (G03)</span>
                    </a>
                @elseif (auth()->user()->role === \App\Enums\UserRole::KEPALA_SPPG)
                    <a href="{{ route('head.production.index', ['date' => $plan->service_date->toDateString()]) }}" 
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-[#DCE5DD] text-xs font-semibold text-[#172923] hover:bg-[#FAFBF9] transition-colors touch-target">
                        <svg class="w-4 h-4 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>Monitoring Perencanaan (K04)</span>
                    </a>
                @endif
            </div>
        </div>
    </header>

    <!-- Invariant 4 Angka: Target Porsi Batch vs Realisasi Masak vs Lolos QC vs Rekonsiliasi -->
    <section aria-labelledby="batch-metrics-heading" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <h2 id="batch-metrics-heading" class="sr-only">Metrik Porsi Batch</h2>

        <!-- 1. Target Rencana Batch -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-4 subtle-shadow">
            <div class="flex items-center justify-between text-[#53645B] mb-1">
                <span class="text-[10px] font-bold uppercase tracking-wider">1. Target Rencana Batch</span>
                <span class="w-2 h-2 rounded-full bg-[#1C6654]" aria-hidden="true"></span>
            </div>
            <div class="flex items-baseline gap-1 mt-1">
                <span class="text-2xl font-bold text-[#1C6654] tabular-nums">{{ number_format($batch->planned_portions) }}</span>
                <span class="text-xs text-[#53645B]">porsi</span>
            </div>
            <p class="text-[11px] text-[#53645B] mt-1">Kategori: {{ $batch->portion_category ?? 'STANDAR' }}</p>
        </div>

        <!-- 2. Realisasi Aktual Masak -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-4 subtle-shadow">
            <div class="flex items-center justify-between text-[#53645B] mb-1">
                <span class="text-[10px] font-bold uppercase tracking-wider">2. Realisasi Dapur</span>
                <span class="w-2 h-2 rounded-full bg-[#885D1C]" aria-hidden="true"></span>
            </div>
            <div class="flex items-baseline gap-1 mt-1">
                <span class="text-2xl font-bold text-[#885D1C] tabular-nums">
                    {{ $batch->actual_portions !== null ? number_format($batch->actual_portions) : '0' }}
                </span>
                <span class="text-xs text-[#53645B]">porsi</span>
            </div>
            @if ($batch->actual_portions !== null)
                @php
                    $diff = $batch->actual_portions - $batch->planned_portions;
                @endphp
                <p class="text-[11px] mt-1 font-medium {{ $diff === 0 ? 'text-[#1B573E]' : ($diff > 0 ? 'text-[#285F80]' : 'text-[#9B3232]') }}">
                    {{ $diff === 0 ? 'Tepat sesuai target' : ($diff > 0 ? "+{$diff} porsi lebih" : "{$diff} porsi kurang") }}
                </p>
            @else
                <p class="text-[11px] text-[#708177] mt-1">Belum dicatat kru dapur</p>
            @endif
        </div>

        <!-- 3. Porsi Lolos Verifikasi QC -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-4 subtle-shadow">
            <div class="flex items-center justify-between text-[#53645B] mb-1">
                <span class="text-[10px] font-bold uppercase tracking-wider">3. Porsi Lolos QC</span>
                <span class="w-2 h-2 rounded-full bg-[#1B573E]" aria-hidden="true"></span>
            </div>
            <div class="flex items-baseline gap-1 mt-1">
                <span class="text-2xl font-bold text-[#1B573E] tabular-nums">
                    {{ $batch->accepted_portions !== null ? number_format($batch->accepted_portions) : ($batch->isReleased() ? number_format($batch->actual_portions) : '0') }}
                </span>
                <span class="text-xs text-[#53645B]">porsi</span>
            </div>
            <p class="text-[11px] text-[#53645B] mt-1">
                {{ $batch->isReleased() ? 'Disetujui Pengawas Gizi' : ($batch->isOnHold() ? 'Ditahan untuk evaluasi' : 'Menunggu hasil uji QC') }}
            </p>
        </div>

        <!-- 4. Porsi Ditolak / Selisih Mutu -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-4 subtle-shadow">
            <div class="flex items-center justify-between text-[#53645B] mb-1">
                <span class="text-[10px] font-bold uppercase tracking-wider">4. Selisih / Ditolak QC</span>
                <span class="w-2 h-2 rounded-full {{ ($batch->actual_portions ?? 0) - ($batch->accepted_portions ?? 0) > 0 ? 'bg-[#9B3232]' : 'bg-[#DCE5DD]' }}" aria-hidden="true"></span>
            </div>
            @php
                $unaccepted = 0;
                if ($batch->isReleased() && $batch->actual_portions !== null && $batch->accepted_portions !== null) {
                    $unaccepted = max(0, $batch->actual_portions - $batch->accepted_portions);
                } elseif ($batch->isOnHold() && $batch->actual_portions !== null) {
                    $unaccepted = $batch->actual_portions;
                }
            @endphp
            <div class="flex items-baseline gap-1 mt-1">
                <span class="text-2xl font-bold {{ $unaccepted > 0 ? 'text-[#9B3232]' : 'text-[#53645B]' }} tabular-nums">
                    {{ number_format($unaccepted) }}
                </span>
                <span class="text-xs text-[#53645B]">porsi</span>
            </div>
            <p class="text-[11px] {{ $unaccepted > 0 ? 'text-[#9B3232] font-semibold' : 'text-[#708177]' }} mt-1">
                {{ $unaccepted > 0 ? 'Porsi ditahan / rusak' : 'Nol cacat produksi' }}
            </p>
        </div>
    </section>

    <!-- Konten Utama: 2 Kolom (Linimasa & QC di Kiri | Profil & Sekolah Tujuan di Kanan) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        
        <!-- Kolom Kiri (2 Kolom): Jejak Linimasa Produksi & Riwayat QC -->
        <div class="lg:col-span-2 space-y-6">

            <!-- 1. Linimasa Tahapan Produksi (Append-Only) -->
            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 sm:p-6 subtle-shadow space-y-5">
                <div class="pb-3 border-b border-[#EDF2ED] flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#1C6654]">Audit Trail Tahapan Dapur</span>
                        <h2 class="text-base font-bold text-[#172923] mt-0.5">
                            Linimasa Alur Produksi Makanan
                        </h2>
                    </div>
                    <span class="text-xs font-mono text-[#53645B] bg-[#FAFBF9] border border-[#DCE5DD] px-2.5 py-1 rounded-lg">
                        {{ $stageLogs->count() }} Tahapan Tercatat
                    </span>
                </div>

                @if ($stageLogs->isEmpty())
                    <div class="p-6 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] text-center text-xs text-[#708177] italic">
                        Belum ada tahapan produksi yang dimulai untuk batch ini.
                    </div>
                @else
                    <ol class="relative border-l border-[#DCE5DD] ml-3.5 space-y-6">
                        @foreach ($stageLogs as $log)
                            <li class="ml-6">
                                <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full {{ $log->isCompleted() ? 'bg-[#EDF7F2] border-2 border-[#1C6654] text-[#1C6654]' : 'bg-[#FAF4EB] border-2 border-[#885D1C] text-[#885D1C] animate-pulse' }}">
                                    @if ($log->isCompleted())
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @else
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#885D1C]"></span>
                                    @endif
                                </span>

                                <div class="p-4 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD]">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 mb-1">
                                        <h3 class="text-xs sm:text-sm font-bold text-[#172923]">
                                            {{ $log->stage->label() }}
                                        </h3>
                                        <span class="text-[11px] font-mono text-[#53645B]">
                                            Mulai: {{ $log->started_at->format('H:i:s') }} WIB
                                            @if ($log->completed_at)
                                                : Selesai: {{ $log->completed_at->format('H:i:s') }} WIB
                                            @else
                                                <span class="text-[#885D1C] font-semibold">(Sedang Berjalan)</span>
                                            @endif
                                        </span>
                                    </div>

                                    <div class="text-xs text-[#53645B] space-y-0.5 mt-2">
                                        <p>Operator: <strong>{{ $log->updater->name }}</strong> ({{ $log->updater->role->label() }})</p>
                                        @if ($log->notes)
                                            <p class="text-[#172923] italic mt-1 bg-white p-2 rounded-lg border border-[#EDF2ED]">
                                                "{{ $log->notes }}"
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            <!-- 2. Gerbang Keputusan Mutu & Keamanan Pangan (Quality Control) -->
            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 sm:p-6 subtle-shadow space-y-5">
                <div class="pb-3 border-b border-[#EDF2ED] flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#285F80]">Quality Control Gate</span>
                        <h2 class="text-base font-bold text-[#172923] mt-0.5">
                            Pemeriksaan Sensorik &amp; Keputusan Keamanan Pangan
                        </h2>
                    </div>
                    @if ($batch->latestQualityCheck())
                        <span class="text-xs font-mono font-bold px-2.5 py-1 rounded-lg border {{ $batch->latestQualityCheck()->isSafe() ? 'bg-[#EDF7F2] text-[#1B573E] border-[#BCE2D0]' : 'bg-[#FDF2F2] text-[#9B3232] border-[#F5C2C2]' }}">
                            {{ $batch->latestQualityCheck()->conclusion->label() }}
                        </span>
                    @else
                        <span class="text-xs font-mono text-[#885D1C] bg-[#FAF4EB] border border-[#E8D7BE] px-2.5 py-1 rounded-lg">
                            Menunggu Verifikasi
                        </span>
                    @endif
                </div>

                @if ($qualityChecks->isEmpty())
                    <div class="p-6 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] text-center text-xs text-[#708177] space-y-2">
                        <p class="font-medium text-[#172923]">Belum ada verifikasi mutu dari Pengawas Gizi.</p>
                        <p class="text-[11px]">Batch harus diserahkan dari dapur dan melewati pemeriksaan organoleptik sebelum dapat didistribusikan.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($qualityChecks as $qc)
                            <div class="p-4 rounded-xl border {{ $qc->isSafe() ? 'bg-[#EDF7F2] border-[#BCE2D0]' : 'bg-[#FDF2F2] border-[#F5C2C2]' }} space-y-3">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 pb-2 border-b {{ $qc->isSafe() ? 'border-[#BCE2D0]' : 'border-[#F5C2C2]' }}">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold {{ $qc->isSafe() ? 'text-[#1B573E]' : 'text-[#9B3232]' }}">
                                            Kesimpulan: {{ $qc->conclusion->label() }}
                                        </span>
                                        <span class="text-[11px] font-mono text-[#53645B]">
                                            SOP: {{ $qc->sop_reference ?? 'SOP-BGN-MUTU-01' }}
                                        </span>
                                    </div>
                                    <span class="text-[11px] font-mono text-[#53645B]">
                                        Diverifikasi pada {{ $qc->checked_at->format('d M Y, H:i') }} WIB
                                    </span>
                                </div>

                                <!-- Checklist Organoleptik -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                                    <div class="p-2 rounded-lg bg-white border border-[#DCE5DD]">
                                        <span class="text-[10px] text-[#53645B] block">Warna/Penampilan</span>
                                        <span class="font-bold {{ $qc->appearance_ok ? 'text-[#1B573E]' : 'text-[#9B3232]' }}">
                                            {{ $qc->appearance_ok ? 'Sesuai (Baik)' : 'Menyimpang' }}
                                        </span>
                                    </div>
                                    <div class="p-2 rounded-lg bg-white border border-[#DCE5DD]">
                                        <span class="text-[10px] text-[#53645B] block">Aroma</span>
                                        <span class="font-bold {{ $qc->aroma_ok ? 'text-[#1B573E]' : 'text-[#9B3232]' }}">
                                            {{ $qc->aroma_ok ? 'Sesuai (Segar)' : 'Menyimpang' }}
                                        </span>
                                    </div>
                                    <div class="p-2 rounded-lg bg-white border border-[#DCE5DD]">
                                        <span class="text-[10px] text-[#53645B] block">Tekstur</span>
                                        <span class="font-bold {{ $qc->texture_ok ? 'text-[#1B573E]' : 'text-[#9B3232]' }}">
                                            {{ $qc->texture_ok ? 'Sesuai (Matang)' : 'Menyimpang' }}
                                        </span>
                                    </div>
                                    <div class="p-2 rounded-lg bg-white border border-[#DCE5DD]">
                                        <span class="text-[10px] text-[#53645B] block">Uji Rasa</span>
                                        @if ($qc->taste_tested)
                                            <span class="font-bold {{ $qc->taste_ok ? 'text-[#1B573E]' : 'text-[#9B3232]' }}">
                                                {{ $qc->taste_ok ? 'Sesuai Resep' : 'Menyimpang' }}
                                            </span>
                                        @else
                                            <span class="font-bold text-[#885D1C]">Dilewati (Aman)</span>
                                        @endif
                                    </div>
                                </div>

                                @if (! $qc->taste_tested && $qc->taste_skipped_reason)
                                    <div class="p-2 rounded-lg bg-white border border-[#DCE5DD] text-xs">
                                        <span class="text-[10px] font-bold text-[#885D1C] block">Alasan Uji Rasa Dilewati:</span>
                                        <p class="text-[#172923] text-[11px] italic">{{ $qc->taste_skipped_reason }}</p>
                                    </div>
                                @endif

                                <!-- Parameter Suhu & Rekonsiliasi Porsi -->
                                <div class="flex flex-wrap items-center justify-between text-xs pt-1">
                                    <div class="text-[#53645B]">
                                        Pengukuran Suhu Saji: 
                                        <strong class="font-mono text-[#172923]">
                                            {{ $qc->temperature_c !== null ? number_format($qc->temperature_c, 1) . '°C' : 'Tidak dicatat' }}
                                        </strong>
                                        <span class="text-[10px] text-[#708177] ml-1">(Hot-holding min. 60°C)</span>
                                    </div>
                                    <div class="text-[#53645B]">
                                        Porsi Disetujui: 
                                        <strong class="font-mono text-[#1B573E]">
                                            {{ $qc->accepted_portions !== null ? number_format($qc->accepted_portions) : '0' }} porsi
                                        </strong>
                                    </div>
                                </div>

                                <!-- Pertimbangan Keputusan Pengawas Gizi -->
                                @if ($qc->decision_reason)
                                    <div class="p-3 rounded-lg bg-white border border-[#DCE5DD] text-xs">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#53645B] block mb-0.5">Pertimbangan Profesional Pengawas:</span>
                                        <p class="text-[#172923]">{{ $qc->decision_reason }}</p>
                                    </div>
                                @endif

                                <div class="text-[11px] text-[#53645B] flex justify-between pt-1">
                                    <span>Pengawas Gizi: <strong>{{ $qc->inspector->name }}</strong></span>
                                    @if ($qc->notes)
                                        <span class="italic text-[#172923]">Catatan: {{ $qc->notes }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- 3. Keterlacakan Alokasi ke Sekolah Penerima (PBI-07 Bidirectional Traceability) -->
            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 sm:p-6 subtle-shadow space-y-4">
                <div class="pb-3 border-b border-[#EDF2ED] flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#1C6654]">Keterlacakan Dua Arah</span>
                        <h2 class="text-base font-bold text-[#172923] mt-0.5">
                            Sekolah Penerima Alokasi Batch Ini
                        </h2>
                    </div>
                    <span class="text-xs font-mono text-[#53645B] bg-[#FAFBF9] border border-[#DCE5DD] px-2.5 py-1 rounded-lg">
                        {{ $deliveryItems->count() }} Sekolah Ditautkan
                    </span>
                </div>

                <p class="text-xs text-[#53645B] leading-relaxed">
                    Setiap batch dipetakan langsung ke alokasi resmi rencana produksi. Pengiriman fisik menuju sekolah dijadwalkan melalui armada kendaraan pada modul distribusi.
                </p>

                @if ($deliveryItems->isEmpty())
                    <div class="p-6 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] text-center text-xs text-[#708177] italic">
                        Belum ada sekolah yang ditautkan ke batch ini.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-[#FAFBF9] text-[#53645B] uppercase text-[10px] tracking-wider border-b border-[#DCE5DD]">
                                <tr>
                                    <th scope="col" class="py-2.5 px-3 font-semibold">Nama Sekolah</th>
                                    <th scope="col" class="py-2.5 px-3 font-semibold">Jenjang</th>
                                    <th scope="col" class="py-2.5 px-3 font-semibold text-right">Porsi dari Batch Ini</th>
                                    <th scope="col" class="py-2.5 px-3 font-semibold text-center">Status Distribusi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#EDF2ED]">
                                @foreach ($deliveryItems as $item)
                                    @php
                                        $school = $item->productionAllocation?->school;
                                    @endphp
                                    <tr class="hover:bg-[#FAFBF9]">
                                        <td class="py-3 px-3 font-semibold text-[#172923]">
                                            {{ $school?->name ?? 'Sekolah Terdaftar' }}
                                            <span class="text-[10px] font-mono text-[#53645B] block font-normal">
                                                NPSN/Kode: {{ $school?->school_code }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3 text-[#53645B]">
                                            {{ $school?->education_level ?? 'SD' }}
                                        </td>
                                        <td class="py-3 px-3 font-mono font-bold text-[#1C6654] text-right">
                                            {{ number_format($item->planned_portions) }} porsi
                                        </td>
                                        <td class="py-3 px-3 text-center">
                                            @if ($item->trip_stop_id)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#F0F5FA] text-[#285F80] border border-[#BCD4E6]">
                                                    Terjadwal Trip #{{ $item->trip_stop_id }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-[#FAFBF9] text-[#53645B] border border-[#DCE5DD]">
                                                    Belum dijadwalkan untuk pengiriman
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>

        <!-- Kolom Kanan (1 Kolom): Profil Menu, Bukti Foto FOOD_READY, & Integritas Data -->
        <div class="space-y-6">

            <!-- 1. Profil Menu & Resep Terpilih -->
            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 subtle-shadow space-y-4">
                <div class="pb-3 border-b border-[#EDF2ED]">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#1C6654]">Resep &amp; Komposisi Terpilih</span>
                    <h2 class="text-base font-bold text-[#172923] mt-0.5">{{ $menu->name }}</h2>
                    <span class="text-[11px] font-mono text-[#53645B]">Kode Menu: {{ $menu->menu_code }} (Versi {{ $menu->version }})</span>
                </div>

                <div class="space-y-1.5 text-xs">
                    @foreach ($menu->items as $item)
                        <div class="flex items-center justify-between p-2 rounded-lg bg-[#FAFBF9] border border-[#EDF2ED]">
                            <span class="font-medium text-[#172923]">{{ $item->item_name }}</span>
                            <span class="font-mono text-[#53645B]">{{ number_format($item->serving_weight_g) }}g</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- 2. Bukti Foto Kesiapan Makanan (FOOD_READY) -->
            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 subtle-shadow space-y-4">
                <div class="pb-3 border-b border-[#EDF2ED]">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#285F80]">Bukti Verifikasi Fisik</span>
                    <h2 class="text-base font-bold text-[#172923] mt-0.5">Bukti Foto FOOD_READY</h2>
                </div>

                @if ($photos->isNotEmpty())
                    <div class="space-y-3">
                        @foreach ($photos as $photo)
                            <div class="rounded-xl border border-[#DCE5DD] overflow-hidden bg-[#FAFBF9]">
                                <a href="{{ route('evidence-photos.show', $photo) }}" target="_blank" class="block group relative">
                                    <img src="{{ route('evidence-photos.show', $photo) }}" 
                                         alt="Bukti Makanan Siap Kemas {{ $batch->batch_code }}" 
                                         class="w-full h-44 object-cover group-hover:opacity-90 transition-opacity">
                                    <span class="absolute bottom-2 right-2 bg-black/60 text-white text-[10px] px-2 py-0.5 rounded backdrop-blur-xs">
                                        Buka Foto Asli
                                    </span>
                                </a>
                                <div class="p-2.5 text-xs text-[#53645B] space-y-0.5">
                                    <p class="font-semibold text-[#172923]">{{ $photo->notes ?? 'Foto kesiapan makanan sebelum QC dispatch' }}</p>
                                    <p class="text-[11px] font-mono">Diupload oleh: {{ $photo->uploader->name }} ({{ number_format($photo->file_size / 1024, 1) }} KB)</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif ($batch->has_evidence_exception)
                    <div class="p-4 rounded-xl bg-[#FAF4EB] border border-[#E8D7BE] text-xs text-[#885D1C] space-y-2">
                        <div class="flex items-center gap-1.5 font-bold">
                            <svg class="w-4 h-4 text-[#885D1C]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>Pengecualian Bukti Operasional Resmi</span>
                        </div>
                        <p class="text-[11px] leading-relaxed">
                            Alasan: <strong>"{{ $batch->evidence_exception_reason }}"</strong>
                        </p>
                        <p class="text-[10px] font-mono text-[#53645B]">
                            Disetujui oleh: {{ $batch->exceptionApprover?->name ?? 'Petugas Operasional' }}
                        </p>
                    </div>
                @else
                    <div class="p-4 rounded-xl bg-[#FAFBF9] border border-[#DCE5DD] text-xs text-[#708177] text-center italic">
                        Belum ada berkas foto FOOD_READY yang dilampirkan.
                    </div>
                @endif
            </div>

            <!-- 3. Integritas Data & Identitas Operasional -->
            <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 subtle-shadow space-y-3">
                <div class="pb-2 border-b border-[#EDF2ED]">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#53645B]">Integritas Data Transaksi</span>
                    <h2 class="text-xs font-bold text-[#172923] mt-0.5">Identitas &amp; Hak Akses Scoped</h2>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-[#FAFBF9]">
                        <span class="text-[#53645B]">Rencana Induk:</span>
                        <span class="font-mono text-[#172923]">PLAN-#{{ $plan->id }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-[#FAFBF9]">
                        <span class="text-[#53645B]">Dibuat Oleh:</span>
                        <span class="font-semibold text-[#172923]">{{ $batch->creator->name }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-[#FAFBF9]">
                        <span class="text-[#53645B]">Waktu Dibuat:</span>
                        <span class="font-mono text-[#172923]">{{ $batch->created_at->format('d M Y, H:i') }} WIB</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-[#FAFBF9]">
                        <span class="text-[#53645B]">Diotorisasi Rilis:</span>
                        <span class="font-semibold text-[#172923]">{{ $batch->releaser?->name ?? 'Belum dirilis' }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-[#53645B]">Waktu Rilis:</span>
                        <span class="font-mono text-[#172923]">{{ $batch->released_at?->format('d M Y, H:i') ?? '-' }} WIB</span>
                    </div>
                </div>
            </div>

        </div>

    </div>
</x-app-shell>
