<x-app-shell title="Active Mission : Kurir Pengantaran">
    <!-- Driver Mission Header: Glanceable, High-Contrast & Zero Slop -->
    <header class="mb-5 pb-4 border-b border-[#DCE5DD]">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
            <div class="flex items-center gap-2">
                <x-role-badge :role="$user->role" />
                <span class="text-xs font-mono text-[#53645B]">{{ $sppg->code }} • {{ $sppg->name }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#F5F0FB] text-[#55327A] font-bold text-xs border border-[#DFD1F0]">
                    <span class="w-2 h-2 rounded-full bg-[#55327A]" aria-hidden="true"></span>
                    <span>PANGKALAN STANDBY</span>
                </span>
                <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-[#F5F7F3] text-[#53645B] border border-[#DCE5DD]">
                    KODE SPPG: {{ $sppg->code }}
                </span>
            </div>
        </div>
        <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">Mission Kurir : Siaga Distribusi MBG</h1>
        <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">Daftar titik sasaran pelayanan sekolah binaan dan informasi kesiapan armada pengantaran.</p>
    </header>

    <!-- Safety Banner -->
    <section aria-labelledby="driver-safety-heading" class="mb-5 p-4 rounded-xl bg-[#FAF4EB] border border-[#E8D7BE] text-[#885D1C] text-xs">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 shrink-0 text-[#885D1C] mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="space-y-1">
                <h2 id="driver-safety-heading" class="font-bold text-xs text-[#885D1C] uppercase tracking-wider">Prioritas Keselamatan Berkendara</h2>
                <p class="leading-relaxed">
                    <strong>Jangan mengoperasikan perangkat saat kendaraan melaju.</strong> Informasi pada antarmuka ini dirancang ringkas untuk pandangan sekilas. Interaksi konfirmasi dan pelaporan hanya boleh dilakukan saat armada telah berhenti sempurna di titik lokasi aman.
                </p>
            </div>
        </div>
    </section>

    <!-- Focal Point: Pangkalan Standby & Operational Status Notice (FINDING-05 & FINDING-06) -->
    <section aria-labelledby="driver-status-heading" class="mb-6 bg-[#FAF8FD] rounded-2xl border border-[#DFD1F0] p-5 sm:p-6 subtle-shadow">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 mb-4 border-b border-[#EFE7F8]">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#55327A] block mb-1">Status Penugasan Kurir</span>
                <h2 id="driver-status-heading" class="text-base sm:text-lg font-bold text-[#172923]">
                    Armada Siaga di Pangkalan SPPG
                </h2>
                <p class="text-xs text-[#53645B] mt-0.5">
                    Petugas: <strong>{{ $user->name }}</strong> • Telepon: {{ $user->phone ?? 'Belum terdaftar' }}
                </p>
            </div>
            <div class="flex items-center gap-2 self-start md:self-auto">
                <span class="px-3 py-1.5 rounded-xl bg-white border border-[#DFD1F0] text-xs font-semibold text-[#55327A]">
                    Wilayah: {{ $sppg->name }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="bg-white rounded-xl border border-[#DFD1F0] p-3.5 space-y-1">
                <span class="text-[11px] text-[#53645B] block font-medium">Titik Sasaran Wilayah</span>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-bold text-[#172923] tabular-nums">{{ $totalStops }}</span>
                    <span class="text-xs text-[#53645B]">sekolah binaan</span>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-[#DFD1F0] p-3.5 space-y-1">
                <span class="text-[11px] text-[#53645B] block font-medium">Total Siswa Terdaftar</span>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-bold text-[#55327A] tabular-nums">{{ number_format($totalPortions) }}</span>
                    <span class="text-xs text-[#53645B]">porsi master</span>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-[#DFD1F0] p-3.5 space-y-1">
                <span class="text-[11px] text-[#53645B] block font-medium">Status Modul Eksekusi</span>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-xs font-bold text-[#55327A]">Tahap Persiapan PBI-07</span>
                </div>
            </div>
        </div>

        <!-- Honest Operational Information Box (FINDING-06: replacing dummy standby button) -->
        <div class="mt-4 p-3.5 rounded-xl bg-white border border-[#DFD1F0] text-xs text-[#53645B] flex items-start gap-3">
            <svg class="w-5 h-5 text-[#55327A] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="space-y-1">
                <h3 class="font-bold text-[#172923] text-xs">Informasi Alur Pengiriman &amp; Pelacakan</h3>
                <p class="leading-relaxed">
                    Fitur pelacakan rute perjalanan, verifikasi kedatangan di sekolah, dan pencatatan serah terima wadah ompreng secara digital dijadwalkan pada implementasi <strong>PBI-07 (Pengiriman Makanan Bergizi)</strong> dan <strong>PBI-08 (Verifikasi Penerimaan)</strong>. Selama masa persiapan ini, kurir berada dalam status siaga operasional dan berkoordinasi langsung dengan Asisten Lapangan di dapur SPPG.
                </p>
            </div>
        </div>
    </section>

    <!-- Master Route Targets (FINDING-03: Honest empty state handling) -->
    <x-card title="Daftar Sekolah Sasaran Wilayah Pelayanan" subtitle="Titik lokasi sekolah dalam binaan SPPG {{ $sppg->code }}">
        @if ($stops->isEmpty())
            <!-- Honest Empty State -->
            <div class="py-12 px-4 text-center">
                <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-[#F5F0FB] text-[#55327A] flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-[#172923]">Belum Ada Titik Sekolah Terdaftar</h3>
                <p class="text-xs text-[#53645B] mt-1 max-w-sm mx-auto leading-relaxed">
                    Belum ada sekolah binaan aktif yang terdaftar dalam jangkauan unit SPPG {{ $sppg->code }}. Titik tujuan rute akan muncul otomatis setelah Kepala SPPG mendaftarkan sekolah binaan.
                </p>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($stops as $index => $stop)
                    <div class="p-4 rounded-xl border {{ $index === 0 ? 'border-[#55327A]/30 bg-[#FAF8FD]' : 'border-[#DCE5DD] bg-white' }} flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 smooth-transition">
                        <div class="flex items-start gap-3.5">
                            <div class="w-8 h-8 rounded-full {{ $index === 0 ? 'bg-[#55327A] text-white ring-2 ring-[#55327A]/20' : 'bg-[#EDF2ED] text-[#172923]' }} flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                                {{ $index + 1 }}
                            </div>
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-sm text-[#172923]">{{ $stop->name }}</span>
                                    @if ($index === 0)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#55327A] text-white">Titik Utama</span>
                                    @endif
                                </div>
                                <span class="text-xs text-[#53645B] block">{{ $stop->address }}</span>
                                <span class="text-[11px] font-mono text-[#55327A] block">NPSN: {{ $stop->school_code }} • Jenjang: {{ $stop->education_level }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between sm:justify-end gap-4 pt-2 sm:pt-0 border-t sm:border-t-0 border-[#DCE5DD]/60">
                            <div class="text-left sm:text-right">
                                <span class="text-[11px] text-[#53645B] block">Data Siswa Pokok</span>
                                <span class="text-sm font-bold text-[#172923] tabular-nums">{{ number_format($stop->registered_beneficiaries) }} anak</span>
                            </div>
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-md bg-[#F5F7F3] border border-[#DCE5DD] text-[#53645B]">
                                Wilayah Binaan
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Protocol Distinction Note -->
            <div class="mt-4 pt-4 border-t border-[#EDF2ED] text-xs text-[#53645B] flex items-start gap-2.5">
                <svg class="w-4 h-4 text-[#55327A] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p>
                    <strong>Protokol Serah Terima Dua Sesi:</strong> Sesi pagi adalah pengiriman makanan hangat bertutup wadah higienis. Sesi siang adalah penjemputan wadah ompreng kosong yang telah dikonsolidasikan oleh PIC Sekolah di titik drop point.
                </p>
            </div>
        @endif
    </x-card>
</x-app-shell>
