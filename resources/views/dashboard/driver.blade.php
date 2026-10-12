<x-app-shell title="Active Mission — Kurir Pengantaran">
    <!-- Driver Mission Header -->
    <div class="mb-5 pb-4 border-b border-[#DCE5DD]">
        <div class="flex items-center justify-between gap-3 mb-2">
            <x-role-badge :role="$user->role" />
            <span class="px-2.5 py-1 rounded-md bg-[#F2EDF7] text-[#5C3280] font-bold text-xs border border-[#DFCBEF]">
                KENDARAAN READY
            </span>
        </div>
        <h1 class="text-xl sm:text-2xl font-bold text-[#172923] tracking-tight">Active Mission — Pengantaran MBG</h1>
        <p class="text-xs text-[#53645B] mt-0.5">Manifest multi-stop dan konfirmasi serah terima di lokasi sekolah</p>
    </div>

    <!-- Safety Banner (Strict Operational Contract) -->
    <div class="mb-5 p-3.5 rounded-xl bg-[#FAF4EB] border border-[#E8D7BE] text-[#885D1C] text-xs flex items-center gap-3">
        <svg class="w-5 h-5 shrink-0 text-[#885D1C]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div class="font-medium">
            <span class="font-bold">Prioritas Keselamatan:</span> Jangan mengoperasikan layar saat kendaraan melaju. Lakukan pembaruan status hanya saat berhenti di titik sekolah.
        </div>
    </div>

    <!-- Mission Stats -->
    <div class="grid grid-cols-2 gap-3 mb-5">
        <div class="bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
            <span class="text-xs text-[#53645B] block mb-1">Total Titik Pengantaran</span>
            <span class="text-2xl sm:text-3xl font-bold text-[#172923] tabular-nums">{{ $totalStops }}</span>
            <span class="text-[11px] text-[#53645B] block mt-0.5">Sekolah tujuan</span>
        </div>
        <div class="bg-white rounded-xl border border-[#DCE5DD] p-4 subtle-shadow">
            <span class="text-xs text-[#53645B] block mb-1">Total Porsi Dimuat</span>
            <span class="text-2xl sm:text-3xl font-bold text-[#1C6654] tabular-nums">{{ number_format($totalPortions) }}</span>
            <span class="text-[11px] text-[#53645B] block mt-0.5">Ompreng tersegel</span>
        </div>
    </div>

    <!-- Multi-Stop Manifest -->
    <x-card title="Urutan Manifest Pengantaran (Multi-Stop)" subtitle="Daftar tujuan pengiriman bertahap SPPG">
        <div class="space-y-3">
            @foreach ($stops as $index => $stop)
                <div class="p-3.5 sm:p-4 rounded-xl border border-[#DCE5DD] bg-[#F5F7F3] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <div class="w-7 h-7 rounded-full bg-[#1C6654] text-white flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                            {{ $index + 1 }}
                        </div>
                        <div>
                            <span class="font-bold text-sm text-[#172923] block">{{ $stop->name }}</span>
                            <span class="text-xs text-[#53645B] block">{{ $stop->address }}</span>
                            <span class="text-[11px] font-mono text-[#1C6654] block mt-0.5">Kode: {{ $stop->school_code }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between sm:justify-end gap-3 pt-2 sm:pt-0 border-t sm:border-t-0 border-[#DCE5DD]/60">
                        <div class="text-left sm:text-right">
                            <span class="text-xs text-[#53645B] block">Muatan</span>
                            <span class="text-sm font-bold text-[#172923] tabular-nums">{{ number_format($stop->registered_beneficiaries) }} porsi</span>
                        </div>
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-md bg-white border border-[#DCE5DD] text-[#53645B]">
                            Terjadwal
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </x-card>
</x-app-shell>
