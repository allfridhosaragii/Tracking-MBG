<x-app-shell title="Detail Insiden INC-{{ str_pad($incident->id, 5, '0', STR_PAD_LEFT) }}">
    <div class="max-w-5xl mx-auto space-y-6">
        <!-- Header -->
        <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-5 border-b border-[#DCE5DD]">
            <div class="space-y-1.5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="eyebrow-label text-[#9B3232] bg-[#FDF2F2] border border-[#F5C2C2] px-2.5 py-0.5 rounded-md">
                        Modul Kendala (C01)
                    </span>
                    <span class="font-mono text-xs text-[#172923] font-bold">
                        INC-{{ str_pad($incident->id, 5, '0', STR_PAD_LEFT) }}
                    </span>
                </div>
                <h1 class="page-title">{{ $incident->category->label() }}</h1>
                <p class="text-xs sm:text-sm text-[#53645B] tabular-nums">Dilaporkan pada: {{ $incident->created_at->setTimezone('Asia/Jakarta')->format('d F Y, H:i:s \W\I\B') }}</p>
            </div>

            <a href="{{ route('incidents.index') }}"
               class="min-h-[44px] self-start sm:self-auto inline-flex items-center gap-2 text-xs font-bold text-[#172923] px-4 py-2 rounded-xl border border-[#DCE5DD] bg-white hover:bg-[#FAFBF9] subtle-shadow smooth-transition">
                <svg class="w-4 h-4 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Kembali ke Daftar Insiden</span>
            </a>
        </header>

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="p-4 rounded-2xl bg-[#EDF7F2] border border-[#BCE2D0] text-xs font-semibold text-[#21684B] flex items-center gap-2.5">
                <svg class="w-4 h-4 text-[#21684B] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-2xl bg-[#FDF2F2] border border-[#F5C2C2] text-xs text-[#9B3232] space-y-1">
                <div class="font-bold">Gagal memperbarui status insiden:</div>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Incident Summary & Metadata -->
        <section aria-label="Ringkasan Insiden" class="luxury-surface p-5 sm:p-6 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 pb-3.5 border-b border-[#E7ECE8]">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs px-3 py-1 rounded-lg font-bold border {{ $incident->status->badgeClass() }}">
                        Status: {{ $incident->status->label() }}
                    </span>
                    <span class="text-xs px-2.5 py-1 rounded-lg font-semibold border {{ $incident->severity->badgeClass() }}">
                        Keparahan: {{ $incident->severity->label() }}
                    </span>
                </div>
                <div class="text-xs text-[#53645B]">
                    Unit SPPG: <strong class="text-[#172923]">{{ $incident->sppg?->name ?? 'Pusat' }}</strong>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                <div class="inset-surface p-3.5">
                    <span class="eyebrow-label block mb-1">Sekolah Terkait</span>
                    <span class="font-bold text-[#172923] block">{{ $incident->school?->name ?? 'Non-Sekolah / SPPG' }}</span>
                    <span class="text-[11px] font-mono text-[#53645B] mt-0.5 block">{{ $incident->school?->school_code ?? '-' }}</span>
                </div>
                <div class="inset-surface p-3.5">
                    <span class="eyebrow-label block mb-1">Pelapor</span>
                    <span class="font-bold text-[#172923] block">{{ $incident->reporter?->name ?? 'Sistem Lapangan' }}</span>
                    <span class="text-[11px] text-[#53645B] mt-0.5 block">{{ $incident->reporter?->role?->label() ?? '-' }}</span>
                </div>
                <div class="inset-surface p-3.5">
                    <span class="eyebrow-label block mb-1">Pihak Penyelesai</span>
                    <span class="font-bold text-[#172923] block">{{ $incident->resolver?->name ?? 'Belum Ditugaskan' }}</span>
                    <span class="text-[11px] text-[#53645B] tabular-nums mt-0.5 block">{{ $incident->resolved_at ? $incident->resolved_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') : 'Dalam proses' }}</span>
                </div>
            </div>

            <div class="space-y-1.5 pt-1">
                <span class="eyebrow-label block">Kronologis / Uraian Masalah</span>
                <div class="inset-surface p-4 text-xs sm:text-sm text-[#172923] leading-relaxed whitespace-pre-wrap">{{ $incident->description }}</div>
            </div>

            @if ($incident->resolution_notes)
                <div class="space-y-1.5 pt-1">
                    <span class="eyebrow-label text-[#21684B] block">Catatan Penyelesaian</span>
                    <div class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-xs text-[#172923] leading-relaxed whitespace-pre-wrap">{{ $incident->resolution_notes }}</div>
                </div>
            @endif

            @if ($incident->evidencePhotos->isNotEmpty())
                <div class="space-y-2 pt-3 border-t border-[#E7ECE8]">
                    <span class="eyebrow-label block">Foto Bukti Fisik Terlampir</span>
                    <div class="flex flex-wrap gap-2.5">
                        @foreach ($incident->evidencePhotos as $photo)
                            <a href="{{ route('evidence-photos.show', $photo) }}" target="_blank" rel="noopener"
                               class="group relative w-20 h-20 rounded-xl overflow-hidden border border-[#DCE5DD] bg-[#FAFBF9] flex items-center justify-center hover:ring-2 hover:ring-[#1C6654]">
                                <img src="{{ route('evidence-photos.show', $photo) }}" alt="Bukti Insiden" class="w-full h-full object-cover">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        <!-- State Machine Action Panel -->
        <section aria-labelledby="incident-actions-heading" class="luxury-surface p-5 sm:p-6 space-y-4">
            <h2 id="incident-actions-heading" class="eyebrow-label">
                Panel Tindak Lanjut &amp; Perubahan Status
            </h2>

            @if ($incident->status === \App\Enums\IncidentStatus::OPEN)
                <div class="flex flex-wrap gap-2.5">
                    <form action="{{ route('incidents.transition', [$incident, 'acknowledge']) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#885D1C] text-white font-bold text-xs hover:bg-[#6E4A15] subtle-shadow smooth-transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Terima &amp; Verifikasi Laporan (ACKNOWLEDGE)</span>
                        </button>
                    </form>
                    <form action="{{ route('incidents.transition', [$incident, 'start_progress']) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#285F80] text-white font-bold text-xs hover:bg-[#1E4861] subtle-shadow smooth-transition cursor-pointer">
                            <span>Mulai Investigasi (IN_PROGRESS)</span>
                        </button>
                    </form>
                </div>
            @elseif ($incident->status === \App\Enums\IncidentStatus::ACKNOWLEDGED)
                <div class="flex flex-wrap gap-2.5">
                    <form action="{{ route('incidents.transition', [$incident, 'start_progress']) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#285F80] text-white font-bold text-xs hover:bg-[#1E4861] subtle-shadow smooth-transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span>Mulai Penanganan Lapangan (IN_PROGRESS)</span>
                        </button>
                    </form>
                </div>
            @endif

            @if (in_array($incident->status, [\App\Enums\IncidentStatus::OPEN, \App\Enums\IncidentStatus::ACKNOWLEDGED, \App\Enums\IncidentStatus::IN_PROGRESS, \App\Enums\IncidentStatus::REOPENED], true))
                <div class="pt-3.5 border-t border-[#E7ECE8] space-y-3">
                    <h3 class="text-xs font-bold text-[#172923]">Selesaikan Masalah Lapangan (RESOLVE)</h3>
                    <form action="{{ route('incidents.transition', [$incident, 'resolve']) }}" method="POST" class="space-y-3 text-xs">
                        @csrf
                        <div>
                            <label for="resolution_notes" class="block font-semibold text-[#172923] mb-1.5">
                                Tindakan Korektif &amp; Solusi yang Dilakukan <span class="text-[#9B3232]">*</span>
                            </label>
                            <textarea name="resolution_notes" id="resolution_notes" rows="2" required
                                      placeholder="Contoh: Ompreng tertinggal telah dijemput menggunakan armada pendukung dan fisik lengkap."
                                      class="w-full px-3.5 py-2.5 rounded-xl border border-[#DCE5DD] bg-white text-[#172923] text-xs focus:outline-none focus:ring-2 focus:ring-[#1C6654]"></textarea>
                        </div>
                        <button type="submit"
                                class="min-h-[44px] inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#1C6654] text-white font-bold text-xs hover:bg-[#164D42] subtle-shadow smooth-transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Tandai Terselesaikan (RESOLVED)</span>
                        </button>
                    </form>
                </div>
            @endif

            @if ($incident->status === \App\Enums\IncidentStatus::RESOLVED)
                <div class="p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] space-y-3 text-xs">
                    <div class="font-bold text-[#21684B] flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#21684B] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Insiden berstatus RESOLVED (Menunggu Otorisasi Penutupan Kepala SPPG)</span>
                    </div>

                    @can('close', $incident)
                        <div class="pt-3 border-t border-[#BCE2D0] flex flex-col sm:flex-row sm:items-center gap-3">
                            <form action="{{ route('incidents.transition', [$incident, 'close']) }}" method="POST">
                                @csrf
                                <input type="hidden" name="resolution_notes" value="{{ $incident->resolution_notes }}">
                                <button type="submit"
                                        class="min-h-[44px] w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-[#172923] text-white font-bold text-xs hover:bg-[#123B33] subtle-shadow smooth-transition cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    <span>Setujui &amp; Tutup Insiden (CLOSED)</span>
                                </button>
                            </form>

                            <form action="{{ route('incidents.transition', [$incident, 'reopen']) }}" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 flex-1">
                                @csrf
                                <input type="text" name="reason" placeholder="Alasan buka kembali..." required
                                       class="min-h-[44px] flex-1 px-3.5 py-2 rounded-xl border border-[#F5C2C2] bg-white text-xs text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#9B3232]">
                                <button type="submit"
                                        class="min-h-[44px] inline-flex items-center justify-center px-4 py-2 rounded-xl bg-[#9B3232] text-white font-bold text-xs hover:bg-[#7D2828] subtle-shadow smooth-transition cursor-pointer">
                                    <span>Buka Kembali (REOPEN)</span>
                                </button>
                            </form>
                        </div>
                    @else
                        <p class="text-[11px] text-[#53645B]">
                            Hanya Kepala SPPG yang memiliki otoritas menutup insiden secara resmi (CLOSED) atau membukanya kembali.
                        </p>
                    @endcan
                </div>
            @elseif ($incident->status === \App\Enums\IncidentStatus::CLOSED)
                <div class="inset-surface p-3.5 text-xs text-[#53645B] flex items-center gap-2.5 font-medium">
                    <svg class="w-4 h-4 text-[#53645B] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span>Insiden ini telah DITUTUP secara permanen dan diarsipkan dalam riwayat audit mutu.</span>
                </div>
            @endif
        </section>

        <!-- Immutable Audit Log -->
        <section aria-labelledby="incident-audit-heading" class="luxury-surface p-5 sm:p-6 space-y-3.5">
            <h2 id="incident-audit-heading" class="eyebrow-label">
                Jejak Audit &amp; Histori Tindakan ({{ count($auditLogs) }})
            </h2>

            <div class="space-y-2.5">
                @forelse ($auditLogs as $log)
                    <div class="inset-surface p-3.5 text-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5">
                        <div>
                            <span class="font-bold text-[#172923]">{{ $log->action }}</span>
                            <span class="text-[#53645B] ml-1">oleh <strong class="text-[#172923]">{{ $log->actor?->name ?? 'Sistem' }}</strong> ({{ $log->actor?->role?->label() ?? 'System' }})</span>
                            @if (!empty($log->metadata['resolution_notes']))
                                <p class="text-[11px] text-[#53645B] mt-1 italic">"{{ $log->metadata['resolution_notes'] }}"</p>
                            @elseif (!empty($log->metadata['reason']))
                                <p class="text-[11px] text-[#53645B] mt-1 italic">"{{ $log->metadata['reason'] }}"</p>
                            @endif
                        </div>
                        <span class="text-[11px] font-mono text-[#53645B] tabular-nums shrink-0">
                            {{ $log->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s \W\I\B') }}
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-[#53645B] py-3 text-center">Belum ada catatan aktivitas tambahan.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-shell>
