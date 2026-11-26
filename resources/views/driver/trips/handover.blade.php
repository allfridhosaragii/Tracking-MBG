<x-app-shell title="Serah Terima : {{ $school->name }}">
    <div class="max-w-4xl mx-auto space-y-5">
        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 pb-4 border-b border-[#DCE5DD]">
            <div>
                <a href="{{ route('driver.trips.active', $trip) }}"
                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#1C6654] hover:underline mb-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Kembali ke Navigasi Rute</span>
                </a>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="page-title">{{ $school->name }}</h1>
                    <span class="text-xs px-2.5 py-0.5 rounded-lg font-semibold border {{ $stop->status->badgeClass() }}">
                        {{ $stop->status->label() }}
                    </span>
                </div>
                <p class="text-xs text-[#53645B] mt-1">
                    Perhentian #{{ $stop->sequence_number }} • Kode: <span class="font-mono">{{ $school->school_code }}</span> • Tiba: <strong class="tabular-nums">{{ $stop->arrived_at ? $stop->arrived_at->setTimezone('Asia/Jakarta')->format('H:i:s') . ' WIB' : 'Belum tercatat' }}</strong>
                </p>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
            <!-- Left: Photo Upload (Primary Driver Task on Mobile -> Put First!) -->
            <div class="lg:col-span-5 lg:order-2">
                <x-card title="Bukti Foto Serah Terima" subtitle="Dokumentasi kamera kurir di titik serah sekolah.">
                    @php
                        $handoverPhoto = $stop->evidencePhotos
                            ->where('evidence_type', 'SCHOOL_HANDOVER')
                            ->sortByDesc('created_at')
                            ->first();
                    @endphp

                    @if ($handoverPhoto)
                        <div class="space-y-3">
                            <div class="rounded-xl overflow-hidden border border-[#DCE5DD] bg-[#FAFBF9] aspect-video">
                                <img src="{{ route('evidence-photos.show', $handoverPhoto) }}"
                                     alt="Foto Serah Terima di {{ $school->name }}"
                                     class="w-full h-full object-cover">
                            </div>
                            <div class="inset-surface p-3 text-xs space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-[#53645B]">Diunggah</span>
                                    <span class="font-bold text-[#172923] tabular-nums">{{ $handoverPhoto->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</span>
                                </div>
                                @if ($handoverPhoto->notes)
                                    <p class="pt-1.5 border-t border-[#E7ECE8] text-[#172923]">{{ $handoverPhoto->notes }}</p>
                                @endif
                            </div>
                        </div>
                    @else
                        <form action="{{ route('driver.trips.stops.handover-evidence', [$trip, $stop]) }}"
                              method="POST"
                              enctype="multipart/form-data"
                              class="space-y-4">
                            @csrf

                            <div>
                                <label for="photo" class="block text-xs font-semibold text-[#172923] mb-1.5">
                                    Ambil Foto Serah Terima <span class="text-[#9B3232]">*</span>
                                </label>
                                <input type="file"
                                       name="photo"
                                       id="photo"
                                       accept="image/*"
                                       capture="environment"
                                       required
                                       class="block w-full text-xs text-[#172923] file:mr-3 file:py-2.5 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#1C6654] file:text-white file:cursor-pointer border border-[#DCE5DD] rounded-xl p-2 bg-[#FAFBF9]">
                                @error('photo')
                                    <span class="text-xs text-[#9B3232] font-medium block mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="notes" class="block text-xs font-semibold text-[#172923] mb-1.5">Catatan Serah Terima (Opsional)</label>
                                <textarea name="notes"
                                          id="notes"
                                          rows="2"
                                          placeholder="Contoh: Diserahkan di ruang transit sekolah"
                                          class="w-full text-xs text-[#172923] rounded-xl border border-[#DCE5DD] bg-[#FAFBF9] focus:bg-white p-3"></textarea>
                                @error('notes')
                                    <span class="text-xs text-[#9B3232] font-medium block mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <button type="submit"
                                    class="w-full min-h-[48px] py-3 px-4 rounded-xl font-bold text-xs sm:text-sm bg-[#1C6654] hover:bg-[#164D42] text-white cursor-pointer smooth-transition">
                                Unggah Bukti Serah Terima
                            </button>
                        </form>
                    @endif
                </x-card>
            </div>

            <!-- Right: Manifest & PIC Verification Status -->
            <div class="lg:col-span-7 lg:order-1 space-y-5">
                <x-card title="Manifest Muatan Dikirim" badge="{{ $stop->loaded_portions ?: $stop->planned_portions }} Porsi">
                    <div class="divide-y divide-[#E7ECE8] -my-1">
                        @forelse ($stop->deliveryItems as $item)
                            <div class="py-3 flex items-center justify-between gap-3 text-xs">
                                <div>
                                    <span class="font-bold text-[#172923] block text-sm">
                                        {{ $item->foodBatch?->menu?->name ?? $item->foodBatch?->menu?->recipe_name ?? 'Menu MBG' }}
                                    </span>
                                    <span class="text-[11px] text-[#53645B] font-mono">Batch: {{ $item->foodBatch?->batch_code ?? '-' }}</span>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="font-bold text-base text-[#172923] tabular-nums block">{{ $item->loaded_portions }}</span>
                                    <span class="text-[10px] text-[#53645B]">porsi</span>
                                </div>
                            </div>
                        @empty
                            <p class="py-4 text-center text-xs text-[#53645B]">Tidak ada item muatan terdaftar pada pemberhentian ini.</p>
                        @endforelse
                    </div>

                    <div class="pt-3 mt-3 border-t border-[#E7ECE8] flex items-center justify-between text-xs text-[#53645B]">
                        <span>Trip: <strong class="font-mono text-[#172923]">{{ $trip->trip_code }}</strong></span>
                        <span>Armada: <strong class="text-[#172923]">{{ $trip->vehicle?->plate_number ?? '-' }}</strong></span>
                    </div>
                </x-card>

                <x-card title="Status Konfirmasi PIC Sekolah">
                    @if ($serviceRecord && $serviceRecord->isConfirmed())
                        <div class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-[#1B573E] space-y-3">
                            <div>
                                <span class="font-bold text-xs uppercase tracking-wide block">{{ $serviceRecord->status->label() }}</span>
                                <p class="text-xs mt-1">
                                    Disahkan oleh <strong>{{ $serviceRecord->receiver?->name ?? 'PIC Sekolah' }}</strong> pada {{ $serviceRecord->received_at?->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB.
                                </p>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-center text-xs">
                                <div class="p-2 rounded-lg bg-white border border-[#BCE2D0]">
                                    <span class="text-[10px] text-[#53645B] block">Diterima</span>
                                    <span class="font-bold text-[#21684B] tabular-nums">{{ $serviceRecord->received_portions }}</span>
                                </div>
                                <div class="p-2 rounded-lg bg-white border border-[#BCE2D0]">
                                    <span class="text-[10px] text-[#53645B] block">Rusak</span>
                                    <span class="font-bold text-[#885D1C] tabular-nums">{{ $serviceRecord->damaged_portions }}</span>
                                </div>
                                <div class="p-2 rounded-lg bg-white border border-[#BCE2D0]">
                                    <span class="text-[10px] text-[#53645B] block">Selisih</span>
                                    <span class="font-bold {{ $serviceRecord->shortage_portions > 0 ? 'text-[#9B3232]' : 'text-[#21684B]' }} tabular-nums">{{ $serviceRecord->shortage_portions }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('driver.trips.active', $trip) }}"
                               class="w-full min-h-[46px] py-3 px-4 rounded-xl font-bold text-xs bg-[#1C6654] hover:bg-[#164D42] text-white flex items-center justify-center gap-2 smooth-transition">
                                <span>Kembali ke Navigasi Rute Aktif</span>
                                <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    @else
                        <div class="p-4 rounded-xl bg-[#FEF7EC] border border-[#F7D7A3] text-[#885D1C] text-xs space-y-1">
                            <p class="font-bold text-[#172923]">Menunggu Pemeriksaan Fisik PIC Sekolah</p>
                            <p class="leading-relaxed">Serahkan wadah makanan kepada PIC Sekolah untuk penghitungan fisik dan uji organoleptik kedatangan.</p>
                        </div>
                    @endif
                </x-card>
            </div>
        </div>
    </div>
</x-app-shell>
