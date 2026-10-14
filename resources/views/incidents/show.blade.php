<x-app-shell title="Detail Insiden INC-{{ str_pad($incident->id, 5, '0', STR_PAD_LEFT) }} — MBG">
    <div class="max-w-5xl mx-auto px-4 py-6 sm:px-6 space-y-6">

        <!-- Header Detail C01 -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-bold text-rose-700 uppercase tracking-wider bg-rose-50 border border-rose-200 px-2 py-0.5 rounded">
                        Modul Kendala (C01)
                    </span>
                    <span class="font-mono text-xs text-[#53645B] font-bold">
                        INC-{{ str_pad($incident->id, 5, '0', STR_PAD_LEFT) }}
                    </span>
                </div>
                <h1 class="text-xl font-bold text-[#172923] mt-1">{{ $incident->category->label() }}</h1>
                <p class="text-xs text-[#53645B] mt-0.5">Dilaporkan pada: {{ $incident->created_at->setTimezone('Asia/Jakarta')->format('d F Y, H:i:s \W\I\B') }}</p>
            </div>
            <div>
                <a href="{{ route('incidents.index') }}" 
                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#53645B] hover:text-[#172923] px-3 py-2 rounded-lg border border-[#DCE5DD] bg-white hover:bg-[#F5F7F3] touch-target">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Kembali ke Daftar Insiden</span>
                </a>
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 space-y-1">
                <div class="font-bold">Gagal memperbarui status insiden:</div>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Kartu Ringkasan Status & Metadata -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 shadow-xs space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-[#EDF2ED]">
                <div class="flex items-center gap-2">
                    <span class="text-xs px-3 py-1 rounded-full font-bold border {{ $incident->status->badgeClass() }}">
                        Status: {{ $incident->status->label() }}
                    </span>
                    <span class="text-xs px-2.5 py-1 rounded font-semibold border {{ $incident->severity->badgeClass() }}">
                        Keparahan: {{ $incident->severity->label() }}
                    </span>
                </div>
                <div class="text-xs text-[#53645B]">
                    Unit SPPG: <strong>{{ $incident->sppg?->name ?? 'Pusat' }}</strong>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD]">
                    <span class="text-[10px] text-[#708177] block">Sekolah Terkait</span>
                    <span class="font-bold text-[#172923] block mt-0.5">{{ $incident->school?->name ?? 'Non-Sekolah / SPPG' }}</span>
                    <span class="text-[11px] text-[#53645B]">{{ $incident->school?->npsn ?? '-' }}</span>
                </div>
                <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD]">
                    <span class="text-[10px] text-[#708177] block">Pelapor</span>
                    <span class="font-bold text-[#172923] block mt-0.5">{{ $incident->reporter?->name ?? 'Sistem Lapangan' }}</span>
                    <span class="text-[11px] text-[#53645B]">{{ $incident->reporter?->role?->label() ?? '-' }}</span>
                </div>
                <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD]">
                    <span class="text-[10px] text-[#708177] block">Pihak Penyelesai</span>
                    <span class="font-bold text-[#172923] block mt-0.5">{{ $incident->resolver?->name ?? 'Belum Ditugaskan' }}</span>
                    <span class="text-[11px] text-[#53645B]">{{ $incident->resolved_at ? $incident->resolved_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') : 'Dalam proses' }}</span>
                </div>
            </div>

            <div class="space-y-1.5 pt-2">
                <span class="text-[11px] font-bold text-[#53645B] uppercase tracking-wider block">Kronologis / Uraian Masalah:</span>
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-[#172923] leading-relaxed whitespace-pre-wrap">
                    {{ $incident->description }}
                </div>
            </div>

            @if ($incident->resolution_notes)
                <div class="space-y-1.5 pt-2">
                    <span class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider block">Catatan Penyelesaian:</span>
                    <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-950 leading-relaxed whitespace-pre-wrap">
                        {{ $incident->resolution_notes }}
                    </div>
                </div>
            @endif

            <!-- Foto Bukti Kejadian -->
            @if ($incident->evidencePhotos->isNotEmpty())
                <div class="space-y-2 pt-2 border-t border-[#EDF2ED]">
                    <span class="text-[11px] font-bold text-[#53645B] uppercase tracking-wider block">Foto Bukti Fisik Terlampir:</span>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($incident->evidencePhotos as $photo)
                            <a href="{{ route('evidence-photos.show', $photo) }}" target="_blank" 
                               class="group relative w-20 h-20 rounded-xl overflow-hidden border border-[#DCE5DD] bg-slate-100 flex items-center justify-center hover:ring-2 hover:ring-[#1C6654]">
                                <img src="{{ route('evidence-photos.show', $photo) }}" alt="Bukti" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-[10px]">
                                    Lihat
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- PANEL TINDAK LANJUT OPERASIONAL (State Machine Actions) -->
        <div class="p-5 bg-white rounded-2xl border border-[#DCE5DD] shadow-xs space-y-4">
            <h2 class="text-xs font-bold text-[#53645B] uppercase tracking-wider">
                Panel Tindak Lanjut & Perubahan Status
            </h2>

            @if ($incident->status === \App\Enums\IncidentStatus::OPEN)
                <div class="flex flex-wrap gap-2">
                    <form action="{{ route('incidents.transition', [$incident, 'acknowledge']) }}" method="POST">
                        @csrf
                        <button type="submit" 
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-amber-600 text-white font-bold text-xs hover:bg-amber-700 shadow-xs touch-target">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Terima & Verifikasi Laporan (ACKNOWLEDGE)</span>
                        </button>
                    </form>
                    <form action="{{ route('incidents.transition', [$incident, 'start_progress']) }}" method="POST">
                        @csrf
                        <button type="submit" 
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-blue-600 text-white font-bold text-xs hover:bg-blue-700 shadow-xs touch-target">
                            <span>Mulai Investigasi (IN_PROGRESS)</span>
                        </button>
                    </form>
                </div>
            @elseif ($incident->status === \App\Enums\IncidentStatus::ACKNOWLEDGED)
                <div class="flex flex-wrap gap-2">
                    <form action="{{ route('incidents.transition', [$incident, 'start_progress']) }}" method="POST">
                        @csrf
                        <button type="submit" 
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-blue-600 text-white font-bold text-xs hover:bg-blue-700 shadow-xs touch-target">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span>Mulai Penanganan Lapangan (IN_PROGRESS)</span>
                        </button>
                    </form>
                </div>
            @endif

            <!-- Form Penyelesaian Masalah (Resolve) -->
            @if (in_array($incident->status, [\App\Enums\IncidentStatus::OPEN, \App\Enums\IncidentStatus::ACKNOWLEDGED, \App\Enums\IncidentStatus::IN_PROGRESS, \App\Enums\IncidentStatus::REOPENED], true))
                <div class="pt-3 border-t border-[#EDF2ED] space-y-3">
                    <h3 class="text-xs font-bold text-[#172923]">Selesaikan Masalah Lapangan (RESOLVE)</h3>
                    <form action="{{ route('incidents.transition', [$incident, 'resolve']) }}" method="POST" class="space-y-3 text-xs">
                        @csrf
                        <div>
                            <label for="resolution_notes" class="block font-semibold text-[#172923] mb-1">
                                Tindakan Korektif & Solusi yang Dilakukan <span class="text-rose-600">*</span>
                            </label>
                            <textarea name="resolution_notes" id="resolution_notes" rows="2" required 
                                      placeholder="Contoh: Ompreng yang tertinggal telah dijemput menggunakan kendaraan pendukung, dan seluruh fisik telah lengkap." 
                                      class="w-full px-3 py-2 rounded-lg border border-[#DCE5DD] bg-white text-[#172923] text-xs focus:outline-none focus:ring-2 focus:ring-[#1C6654]"></textarea>
                        </div>
                        <button type="submit" 
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-700 text-white font-bold text-xs hover:bg-emerald-800 shadow-xs touch-target">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Tandai Terselesaikan (RESOLVED)</span>
                        </button>
                    </form>
                </div>
            @endif

            <!-- Otoritas Kepala SPPG: Tutup Permanen (CLOSE) atau Buka Kembali (REOPEN) -->
            @if ($incident->status === \App\Enums\IncidentStatus::RESOLVED)
                <div class="p-4 rounded-xl bg-emerald-50/70 border border-emerald-300 space-y-3 text-xs">
                    <div class="font-bold text-emerald-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Insiden telah berstatus RESOLVED (Menunggu Otorisasi Penutupan Kepala SPPG)</span>
                    </div>

                    @can('close', $incident)
                        <div class="pt-2 border-t border-emerald-200 flex flex-wrap gap-3">
                            <form action="{{ route('incidents.transition', [$incident, 'close']) }}" method="POST">
                                @csrf
                                <input type="hidden" name="resolution_notes" value="{{ $incident->resolution_notes }}">
                                <button type="submit" 
                                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-slate-800 text-white font-bold text-xs hover:bg-black shadow-xs touch-target">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    <span>Setujui & Tutup Insiden (CLOSED)</span>
                                </button>
                            </form>

                            <form action="{{ route('incidents.transition', [$incident, 'reopen']) }}" method="POST" class="inline-flex items-center gap-2">
                                @csrf
                                <input type="text" name="reason" placeholder="Alasan buka kembali..." required 
                                       class="px-3 py-1.5 rounded-lg border border-rose-300 bg-white text-xs text-[#172923]">
                                <button type="submit" 
                                        class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-rose-700 text-white font-bold text-xs hover:bg-rose-800 shadow-xs touch-target">
                                    <span>Buka Kembali (REOPEN)</span>
                                </button>
                            </form>
                        </div>
                    @else
                        <p class="text-[11px] text-emerald-800">
                            Hanya Kepala SPPG yang memiliki otoritas untuk menutup insiden secara resmi (CLOSED) atau membukanya kembali.
                        </p>
                    @endcan
                </div>
            @elseif ($incident->status === \App\Enums\IncidentStatus::CLOSED)
                <div class="p-3.5 rounded-xl bg-slate-100 border border-slate-300 text-xs text-slate-700 flex items-center gap-2 font-medium">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span>Insiden ini telah DITUTUP secara permanen dan diarsipkan dalam riwayat audit mutu.</span>
                </div>
            @endif
        </div>

        <!-- RIWAYAT AUDIT LOG (Immutable Activity History) -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-5 shadow-xs space-y-3">
            <h2 class="text-xs font-bold text-[#53645B] uppercase tracking-wider">
                Jejak Audit & Histori Tindakan ({{ count($auditLogs) }})
            </h2>

            <div class="space-y-3">
                @forelse ($auditLogs as $log)
                    <div class="p-3 rounded-xl bg-[#F5F7F3] border border-[#DCE5DD] text-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                        <div>
                            <span class="font-bold text-[#172923]">{{ $log->action }}</span>
                            <span class="text-[#53645B] ml-1">oleh <strong>{{ $log->actor?->name ?? 'Sistem' }}</strong> ({{ $log->actor?->role?->label() ?? 'System' }})</span>
                            @if (!empty($log->metadata['resolution_notes']))
                                <p class="text-[11px] text-[#708177] mt-0.5 italic">"{{ $log->metadata['resolution_notes'] }}"</p>
                            @elseif (!empty($log->metadata['reason']))
                                <p class="text-[11px] text-[#708177] mt-0.5 italic">"{{ $log->metadata['reason'] }}"</p>
                            @endif
                        </div>
                        <span class="text-[10px] text-[#708177] tabular-nums shrink-0">
                            {{ $log->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s \W\I\B') }}
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-[#708177] italic py-2 text-center">Belum ada catatan aktivitas tambahan.</p>
                @endforelse
            </div>
        </div>

    </div>
</x-app-shell>
