<x-app-shell title="Active Mission — Kurir Pengantaran">
    <!-- Driver Mission Header -->
    <header class="mb-5 pb-4 border-b border-[#DCE5DD]">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
            <div class="flex items-center gap-2">
                <x-role-badge :role="$user->role" />
                <span class="text-xs font-mono text-[#53645B]">{{ $sppg->code }} • {{ $sppg->name }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#EDF7F2] text-[#1B573E] font-bold text-xs border border-[#BCE2D0]">
                    <span class="w-2 h-2 rounded-full bg-[#21684B]" aria-hidden="true"></span>
                    <span>ARMADA STANDBY</span>
                </span>
                <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-[#F5F7F3] text-[#53645B] border border-[#DCE5DD]">
                    TRIP ID: SIM-{{ date('Ymd') }}-01
                </span>
            </div>
        </div>
        <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">Active Mission — Pengantaran MBG</h1>
        <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">Manifest multi-stop perjalanan dan verifikasi serah terima ompreng di lokasi sekolah binaan.</p>
    </header>

    <!-- Safety Banner (Strict Operational Contract & Anti-Slop) -->
    <section aria-labelledby="driver-safety-heading" class="mb-5 p-4 rounded-xl bg-[#FAF4EB] border border-[#E8D7BE] text-[#885D1C] text-xs">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 shrink-0 text-[#885D1C] mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="space-y-1">
                <h2 id="driver-safety-heading" class="font-bold text-xs text-[#885D1C] uppercase tracking-wider">Prioritas Keselamatan Berkendara</h2>
                <p class="leading-relaxed">
                    <strong>Jangan mengoperasikan layar saat kendaraan melaju.</strong> Informasi pada halaman ini dioptimalkan untuk pandangan sekilas (<em>glanceable</em>). Konfirmasi tiba dan pencatatan serah terima hanya boleh dilakukan saat armada telah berhenti sempurna di area parkir sekolah.
                </p>
            </div>
        </div>
    </section>

    <!-- Mission Overview Cards -->
    <section aria-label="Ringkasan Muatan Trip" class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
        <div class="bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
            <span class="text-xs text-[#53645B] block mb-1">Total Titik Pengantaran</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-bold text-[#172923] tabular-nums">{{ $totalStops }}</span>
                <span class="text-xs text-[#53645B]">sekolah tujuan</span>
            </div>
            <span class="text-[11px] text-[#53645B] block mt-1">Wilayah operasional SPPG</span>
        </div>

        <div class="bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
            <span class="text-xs text-[#53645B] block mb-1">Total Porsi Dimuat</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-bold text-[#1C6654] tabular-nums">{{ number_format($totalPortions) }}</span>
                <span class="text-xs text-[#53645B]">ompreng</span>
            </div>
            <span class="text-[11px] text-[#21684B] font-medium block mt-1">Wadah tersegel higienis</span>
        </div>

        <div class="bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
            <span class="text-xs text-[#53645B] block mb-1">Status Muatan & Suhu</span>
            <div class="flex items-baseline gap-2">
                <span class="text-lg font-bold text-[#172923]">Suhu Terjaga (&gt;60°C)</span>
            </div>
            <span class="text-[11px] text-[#53645B] block mt-1">Insulasi thermal aktif</span>
        </div>
    </section>

    <!-- Current Active Leg (Primary Focus) -->
    @if ($stops->isNotEmpty())
        @php $currentStop = $stops->first(); @endphp
        <section aria-labelledby="current-stop-heading" class="mb-6 bg-white rounded-2xl border-2 border-[#1C6654] p-5 sm:p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 pb-4 mb-4 border-b border-[#EDF2ED]">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded bg-[#1C6654] text-white font-bold text-xs uppercase tracking-wider">
                        Tujuan Pertama
                    </span>
                    <span class="text-xs font-mono text-[#53645B]">Kode: {{ $currentStop->school_code }}</span>
                </div>
                <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full bg-[#EDF7F2] text-[#1B573E] border border-[#BCE2D0]">
                    <span class="w-2 h-2 rounded-full bg-[#21684B] animate-pulse" aria-hidden="true"></span>
                    <span>Siap Berangkat</span>
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="md:col-span-2 space-y-2">
                    <h2 id="current-stop-heading" class="text-lg sm:text-xl font-bold text-[#172923] tracking-tight">
                        {{ $currentStop->name }}
                    </h2>
                    <p class="text-xs sm:text-sm text-[#53645B] leading-relaxed">
                        {{ $currentStop->address }}
                    </p>
                    <div class="flex flex-wrap items-center gap-3 pt-2 text-xs text-[#53645B]">
                        <span class="inline-flex items-center gap-1 font-semibold text-[#172923]">
                            <span>Jenjang:</span>
                            <span class="px-2 py-0.5 rounded bg-[#F5F7F3] border border-[#DCE5DD] text-xs">{{ $currentStop->education_level }}</span>
                        </span>
                        <span>•</span>
                        <span>Alokasi Muatan: <strong class="text-[#1C6654] font-bold tabular-nums">{{ number_format($currentStop->registered_beneficiaries) }} porsi</strong></span>
                    </div>
                </div>

                <div class="bg-[#FAFBF9] rounded-xl border border-[#DCE5DD] p-4 flex flex-col justify-between gap-3">
                    <div>
                        <span class="text-xs text-[#53645B] block mb-0.5">Telemetri & GPS</span>
                        <div class="flex items-center gap-2 text-xs font-medium text-[#172923]">
                            <svg class="w-4 h-4 text-[#1C6654]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>Standby di Pangkalan</span>
                        </div>
                        <span class="text-[11px] text-[#53645B] block mt-1">Koordinat dikirim otomatis per 30 detik saat perjalanan aktif</span>
                    </div>

                    <button type="button" 
                            disabled
                            title="Tersedia pada implementasi trip dispatch aktif (PBI-05)"
                            class="w-full min-h-[44px] px-4 py-2.5 rounded-xl bg-[#EDF2ED] text-[#53645B] font-bold text-xs border border-[#DCE5DD] cursor-not-allowed text-center transition-colors">
                        Konfirmasi Tiba di Lokasi (Standby)
                    </button>
                </div>
            </div>
        </section>
    @endif

    <!-- Full Multi-Stop Manifest -->
    <x-card title="Urutan Manifest Pengantaran (Multi-Stop)" subtitle="Daftar seluruh titik singgah armada dalam rute pengantaran hari ini">
        <div class="space-y-3">
            @foreach ($stops as $index => $stop)
                <div class="p-4 rounded-xl border {{ $index === 0 ? 'border-[#1C6654] bg-[#F4F8F6]' : 'border-[#DCE5DD] bg-[#FAFBF9]' }} flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 transition-colors">
                    <div class="flex items-start gap-3.5">
                        <div class="w-8 h-8 rounded-full {{ $index === 0 ? 'bg-[#1C6654] text-white ring-2 ring-[#1C6654]/20' : 'bg-[#E5ECE7] text-[#172923]' }} flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                            {{ $index + 1 }}
                        </div>
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-sm text-[#172923]">{{ $stop->name }}</span>
                                @if ($index === 0)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#1C6654] text-white">Stop 1</span>
                                @endif
                            </div>
                            <span class="text-xs text-[#53645B] block">{{ $stop->address }}</span>
                            <span class="text-[11px] font-mono text-[#1C6654] block">Kode: {{ $stop->school_code }} • Jenjang: {{ $stop->education_level }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between sm:justify-end gap-4 pt-2 sm:pt-0 border-t sm:border-t-0 border-[#DCE5DD]/60">
                        <div class="text-left sm:text-right">
                            <span class="text-[11px] text-[#53645B] block">Alokasi Muatan</span>
                            <span class="text-sm font-bold text-[#172923] tabular-nums">{{ number_format($stop->registered_beneficiaries) }} porsi</span>
                        </div>
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-md {{ $index === 0 ? 'bg-[#EDF7F2] text-[#1B573E] border border-[#BCE2D0]' : 'bg-white border border-[#DCE5DD] text-[#53645B]' }}">
                            {{ $index === 0 ? 'Siap Antar' : 'Terjadwal' }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Protocol Distinction Note -->
        <div class="mt-4 pt-4 border-t border-[#EDF2ED] text-xs text-[#53645B] flex items-start gap-2.5">
            <svg class="w-4 h-4 text-[#1C6654] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p>
                <strong>Protokol Serah Terima Dua Sesi:</strong> Sesi pagi adalah pengiriman makanan hangat bertutup segel sanitasi. Sesi siang/sore adalah penjemputan wadah ompreng kosong yang telah dicuci bersih dan dikonsolidasikan oleh PIC Sekolah.
            </p>
        </div>
    </x-card>
</x-app-shell>
