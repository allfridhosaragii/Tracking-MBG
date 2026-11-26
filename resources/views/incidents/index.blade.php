<x-app-shell title="Pusat Kendala & Insiden Operasional">
    <div class="space-y-6">
        <!-- Page Header -->
        <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-5 border-b border-[#DCE5DD]">
            <div class="space-y-1.5">
                <div class="flex items-center gap-2">
                    <span class="eyebrow-label text-[#9B3232] bg-[#FDF2F2] border border-[#F5C2C2] px-2.5 py-0.5 rounded-md">
                        Modul Kendala &amp; Insiden (C01)
                    </span>
                    <span class="text-xs text-[#53645B] font-medium">Exception Management</span>
                </div>
                <h1 class="page-title">Pusat Kendala Operasional</h1>
                <p class="text-xs sm:text-sm text-[#53645B]">Penanganan penyimpangan logistik, selisih ompreng, mutu pangan, dan kendala lapangan.</p>
            </div>

            <a href="#lapor-kendala"
               class="min-h-[44px] self-start sm:self-auto inline-flex items-center gap-2 text-xs font-bold text-white bg-[#9B3232] hover:bg-[#7D2828] px-4 py-2.5 rounded-xl subtle-shadow smooth-transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Lapor Kendala Baru</span>
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
                <div class="font-bold">Gagal memproses data:</div>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Filter Bar -->
        <section aria-label="Filter Kendala" class="luxury-surface p-4 sm:p-5">
            <form method="GET" action="{{ route('incidents.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                <div>
                    <label for="filter_status" class="block font-semibold text-[#172923] mb-1.5">Status Penanganan</label>
                    <select name="status" id="filter_status" class="min-h-[44px] w-full px-3 py-2 rounded-xl border border-[#DCE5DD] bg-[#F5F7F3] text-[#172923] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                        <option value="">Semua Status</option>
                        @foreach ($statuses as $st)
                            <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter_category" class="block font-semibold text-[#172923] mb-1.5">Kategori Kendala</label>
                    <select name="category" id="filter_category" class="min-h-[44px] w-full px-3 py-2 rounded-xl border border-[#DCE5DD] bg-[#F5F7F3] text-[#172923] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->value }}" {{ request('category') === $cat->value ? 'selected' : '' }}>
                                {{ $cat->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter_severity" class="block font-semibold text-[#172923] mb-1.5">Tingkat Keparahan</label>
                    <select name="severity" id="filter_severity" class="min-h-[44px] w-full px-3 py-2 rounded-xl border border-[#DCE5DD] bg-[#F5F7F3] text-[#172923] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                        <option value="">Semua Tingkat</option>
                        @foreach ($severities as $sev)
                            <option value="{{ $sev->value }}" {{ request('severity') === $sev->value ? 'selected' : '' }}>
                                {{ $sev->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="min-h-[44px] flex-1 py-2 px-4 rounded-xl bg-[#1C6654] text-white font-bold text-xs hover:bg-[#164D42] smooth-transition cursor-pointer">
                        Terapkan Filter
                    </button>
                    @if (request()->hasAny(['status', 'category', 'severity']))
                        <a href="{{ route('incidents.index') }}" class="min-h-[44px] inline-flex items-center justify-center py-2 px-3.5 rounded-xl border border-[#DCE5DD] bg-white text-[#53645B] hover:text-[#172923] text-xs font-semibold smooth-transition">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </section>

        <!-- Incident List -->
        <section aria-labelledby="incident-list-heading" class="space-y-3.5">
            <h2 id="incident-list-heading" class="eyebrow-label">
                Daftar Kendala Lapangan ({{ $incidents->total() }})
            </h2>

            @forelse ($incidents as $inc)
                <article class="luxury-surface p-5 hover:border-[#1C6654]/40 smooth-transition space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-[#E7ECE8]">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-xs font-bold text-[#172923] bg-[#F5F7F3] px-2.5 py-1 rounded-lg border border-[#DCE5DD]">
                                INC-{{ str_pad($inc->id, 5, '0', STR_PAD_LEFT) }}
                            </span>
                            <span class="text-xs px-2.5 py-1 rounded-lg font-bold border {{ $inc->status->badgeClass() }}">
                                {{ $inc->status->label() }}
                            </span>
                            <span class="text-[11px] px-2.5 py-1 rounded-lg font-semibold border {{ $inc->severity->badgeClass() }}">
                                {{ $inc->severity->label() }}
                            </span>
                        </div>
                        <div class="text-xs text-[#53645B] tabular-nums">
                            Dilaporkan: <strong>{{ $inc->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i \W\I\B') }}</strong>
                        </div>
                    </div>

                    <div class="space-y-1.5 text-xs">
                        <div class="flex flex-wrap items-center gap-2 text-[11px] text-[#53645B]">
                            <span class="font-bold text-[#1C6654] uppercase tracking-wider">{{ $inc->category->label() }}</span>
                            @if ($inc->school)
                                <span aria-hidden="true">•</span>
                                <span class="font-bold text-[#172923]">{{ $inc->school->name }}</span>
                            @endif
                            @if ($inc->trip)
                                <span aria-hidden="true">•</span>
                                <span class="font-mono">Trip: {{ $inc->trip->trip_code }}</span>
                            @endif
                        </div>
                        <p class="text-[#172923] text-sm leading-relaxed">
                            {{ $inc->description }}
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pt-2.5 border-t border-[#E7ECE8] text-xs text-[#53645B]">
                        <div>
                            <span>Pelapor: <strong class="text-[#172923]">{{ $inc->reporter?->name ?? 'Sistem / Lapangan' }}</strong></span>
                            @if ($inc->resolver)
                                <span class="ml-2">• Ditangani: <strong class="text-[#172923]">{{ $inc->resolver->name }}</strong></span>
                            @endif
                        </div>
                        <a href="{{ route('incidents.show', $inc) }}"
                           class="min-h-[44px] inline-flex items-center gap-1.5 font-bold text-[#1C6654] hover:underline self-start sm:self-auto">
                            <span>Buka Tindak Lanjut &amp; Audit Log</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </article>
            @empty
                <div class="luxury-surface p-10 text-center text-xs text-[#53645B] space-y-2">
                    <div class="w-12 h-12 rounded-2xl bg-[#EDF7F2] text-[#21684B] flex items-center justify-center mx-auto mb-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="section-title">Tidak Ada Kendala Operasional</p>
                    <p>Seluruh pengantaran dan siklus logistik balik berjalan sesuai standar operasional.</p>
                </div>
            @endforelse

            @if ($incidents->hasPages())
                <div class="pt-2">
                    {{ $incidents->links() }}
                </div>
            @endif
        </section>

        <!-- New Incident Report Form -->
        <section id="lapor-kendala" aria-labelledby="new-incident-heading" class="luxury-surface p-5 sm:p-6 space-y-4">
            <div class="pb-3.5 border-b border-[#E7ECE8] flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-[#FDF2F2] border border-[#F5C2C2] text-[#9B3232] flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <span class="eyebrow-label text-[#9B3232] block">Tiket Eksepsi Lapangan</span>
                    <h3 id="new-incident-heading" class="section-title">Formulir Pelaporan Kendala Operasional Baru</h3>
                </div>
            </div>

            <form action="{{ route('incidents.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="category" class="block font-semibold text-[#172923] mb-1.5">
                            Kategori Masalah <span class="text-[#9B3232]">*</span>
                        </label>
                        <select name="category" id="category" required class="min-h-[44px] w-full px-3.5 py-2 rounded-xl border border-[#DCE5DD] bg-white text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="severity" class="block font-semibold text-[#172923] mb-1.5">
                            Tingkat Keparahan <span class="text-[#9B3232]">*</span>
                        </label>
                        <select name="severity" id="severity" required class="min-h-[44px] w-full px-3.5 py-2 rounded-xl border border-[#DCE5DD] bg-white text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                            @foreach ($severities as $sev)
                                <option value="{{ $sev->value }}">{{ $sev->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="description" class="block font-semibold text-[#172923] mb-1.5">
                        Deskripsi Lengkap Kejadian <span class="text-[#9B3232]">*</span>
                    </label>
                    <textarea name="description" id="description" rows="3" required
                              placeholder="Jelaskan kronologi kendala, dampak terhadap porsi/ompreng/layanan, serta pihak terkait..."
                              class="w-full px-3.5 py-2.5 rounded-xl border border-[#DCE5DD] bg-white text-[#172923] text-xs focus:outline-none focus:ring-2 focus:ring-[#1C6654]"></textarea>
                </div>

                <div>
                    <label for="photo" class="block font-semibold text-[#172923] mb-1.5">
                        Foto Bukti Kejadian (Opsional)
                    </label>
                    <input type="file" name="photo" id="photo" accept="image/*"
                           class="min-h-[44px] w-full text-xs text-[#53645B] file:mr-3 file:py-2 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#FDF2F2] file:text-[#9B3232] hover:file:bg-[#F5C2C2]">
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="min-h-[44px] w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-[#9B3232] text-white font-bold text-xs hover:bg-[#7D2828] subtle-shadow smooth-transition cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Kirim Laporan Kendala</span>
                    </button>
                </div>
            </form>
        </section>
    </div>
</x-app-shell>
