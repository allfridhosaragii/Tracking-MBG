<x-app-shell title="Pusat Kendala & Insiden Operasional — MBG">
    <div class="max-w-6xl mx-auto px-4 py-6 sm:px-6 space-y-6">

        <!-- Header Halaman C01 -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-[#DCE5DD]">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-bold text-rose-700 uppercase tracking-wider bg-rose-50 border border-rose-200 px-2 py-0.5 rounded">
                        Modul Kendala & Insiden (C01)
                    </span>
                    <span class="text-xs text-[#53645B] font-medium">Exception Management</span>
                </div>
                <h1 class="text-xl font-bold text-[#172923] mt-1">Pusat Kendala Operasional</h1>
                <p class="text-xs text-[#53645B] mt-0.5">Penanganan penyimpangan logistik, selisih ompreng, mutu pangan, dan operasional lapangan.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="#lapor-kendala" 
                   class="inline-flex items-center gap-1.5 text-xs font-bold text-white bg-rose-700 hover:bg-rose-800 px-3.5 py-2 rounded-lg shadow-xs touch-target">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Lapor Kendala Baru</span>
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
                <div class="font-bold">Gagal memproses data:</div>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Filter Bar -->
        <div class="p-4 rounded-2xl bg-white border border-[#DCE5DD] shadow-xs">
            <form method="GET" action="{{ route('incidents.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                <div>
                    <label for="filter_status" class="block font-semibold text-[#172923] mb-1">Status Penanganan</label>
                    <select name="status" id="filter_status" class="w-full px-3 py-2 rounded-lg border border-[#DCE5DD] bg-[#F5F7F3] text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                        <option value="">Semua Status</option>
                        @foreach ($statuses as $st)
                            <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter_category" class="block font-semibold text-[#172923] mb-1">Kategori Kendala</label>
                    <select name="category" id="filter_category" class="w-full px-3 py-2 rounded-lg border border-[#DCE5DD] bg-[#F5F7F3] text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->value }}" {{ request('category') === $cat->value ? 'selected' : '' }}>
                                {{ $cat->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter_severity" class="block font-semibold text-[#172923] mb-1">Tingkat Keparahan</label>
                    <select name="severity" id="filter_severity" class="w-full px-3 py-2 rounded-lg border border-[#DCE5DD] bg-[#F5F7F3] text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                        <option value="">Semua Tingkat</option>
                        @foreach ($severities as $sev)
                            <option value="{{ $sev->value }}" {{ request('severity') === $sev->value ? 'selected' : '' }}>
                                {{ $sev->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 py-2 px-3 rounded-lg bg-[#1C6654] text-white font-bold text-xs hover:bg-[#154d40] transition-colors touch-target">
                        Terapkan Filter
                    </button>
                    @if (request()->hasAny(['status', 'category', 'severity']))
                        <a href="{{ route('incidents.index') }}" class="py-2 px-3 rounded-lg border border-[#DCE5DD] bg-white text-[#53645B] hover:text-[#172923] text-xs font-semibold touch-target">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Daftar Insiden -->
        <div class="space-y-3">
            <h2 class="text-xs font-bold text-[#53645B] uppercase tracking-wider">
                Daftar Kendala Lapangan ({{ $incidents->total() }})
            </h2>

            @forelse ($incidents as $inc)
                <div class="bg-white rounded-2xl border border-[#DCE5DD] p-4 sm:p-5 shadow-xs hover:border-[#1C6654]/40 transition-colors space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-[#EDF2ED]">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-bold text-[#172923] bg-[#F5F7F3] px-2 py-0.5 rounded border border-[#DCE5DD]">
                                INC-{{ str_pad($inc->id, 5, '0', STR_PAD_LEFT) }}
                            </span>
                            <span class="text-xs px-2.5 py-0.5 rounded-full font-bold border {{ $inc->status->badgeClass() }}">
                                {{ $inc->status->label() }}
                            </span>
                            <span class="text-[11px] px-2 py-0.5 rounded font-semibold border {{ $inc->severity->badgeClass() }}">
                                {{ $inc->severity->label() }}
                            </span>
                        </div>
                        <div class="text-xs text-[#53645B]">
                            Dilaporkan: <strong>{{ $inc->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i \W\I\B') }}</strong>
                        </div>
                    </div>

                    <div class="space-y-1.5 text-xs">
                        <div class="flex items-center gap-2 text-[11px] text-[#708177]">
                            <span class="font-semibold text-[#1C6654] uppercase tracking-wider">{{ $inc->category->label() }}</span>
                            @if ($inc->school)
                                <span>&bull;</span>
                                <span class="font-bold text-[#172923]">{{ $inc->school->name }}</span>
                            @endif
                            @if ($inc->trip)
                                <span>&bull;</span>
                                <span class="font-mono">Trip: {{ $inc->trip->trip_code }}</span>
                            @endif
                        </div>
                        <p class="text-[#172923] text-sm leading-relaxed">
                            {{ $inc->description }}
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pt-2 border-t border-[#EDF2ED] text-xs text-[#53645B]">
                        <div>
                            <span>Pelapor: <strong>{{ $inc->reporter?->name ?? 'Sistem / Lapangan' }}</strong></span>
                            @if ($inc->resolver)
                                <span class="ml-2">&bull; Ditangani: <strong>{{ $inc->resolver->name }}</strong></span>
                            @endif
                        </div>
                        <a href="{{ route('incidents.show', $inc) }}" 
                           class="inline-flex items-center gap-1 font-bold text-[#1C6654] hover:underline self-end sm:self-auto touch-target">
                            <span>Buka Tindak Lanjut & Audit Log</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center bg-white rounded-2xl border border-[#DCE5DD] text-xs text-[#53645B] space-y-2">
                    <svg class="w-10 h-10 text-emerald-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="font-bold text-[#172923]">Tidak Ada Kendala Operasional</p>
                    <p class="text-[#708177]">Semua pengantaran dan siklus logistik balik berjalan sesuai standar.</p>
                </div>
            @endforelse

            <div class="pt-2">
                {{ $incidents->links() }}
            </div>
        </div>

        <!-- SECTION: Form Pelaporan Kendala Baru -->
        <div id="lapor-kendala" class="p-5 sm:p-6 bg-white rounded-2xl border-2 border-rose-300 shadow-xs space-y-4">
            <div class="flex items-center gap-2 text-rose-800">
                <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <h3 class="text-base font-bold text-[#172923]">Formulir Pelaporan Kendala Operasional Baru</h3>
            </div>

            <form action="{{ route('incidents.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="category" class="block font-semibold text-[#172923] mb-1">
                            Kategori Masalah <span class="text-rose-600">*</span>
                        </label>
                        <select name="category" id="category" required class="w-full px-3 py-2 rounded-lg border border-[#DCE5DD] bg-white text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654] touch-target">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="severity" class="block font-semibold text-[#172923] mb-1">
                            Tingkat Keparahan <span class="text-rose-600">*</span>
                        </label>
                        <select name="severity" id="severity" required class="w-full px-3 py-2 rounded-lg border border-[#DCE5DD] bg-white text-[#172923] focus:outline-none focus:ring-2 focus:ring-[#1C6654] touch-target">
                            @foreach ($severities as $sev)
                                <option value="{{ $sev->value }}">{{ $sev->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="description" class="block font-semibold text-[#172923] mb-1">
                        Deskripsi Lengkap Kejadian <span class="text-rose-600">*</span>
                    </label>
                    <textarea name="description" id="description" rows="3" required 
                              placeholder="Jelaskan secara kronologis kendala yang dialami, dampak terhadap porsi/ompreng/layanan, dan pihak yang terlibat..." 
                              class="w-full px-3 py-2 rounded-lg border border-[#DCE5DD] bg-white text-[#172923] text-xs focus:outline-none focus:ring-2 focus:ring-[#1C6654]"></textarea>
                </div>

                <div>
                    <label for="photo" class="block font-semibold text-[#172923] mb-1">
                        Foto Bukti Kejadian (Opsional)
                    </label>
                    <input type="file" name="photo" id="photo" accept="image/*"
                           class="w-full text-xs text-[#53645B] file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-rose-700 file:text-white hover:file:bg-rose-800">
                </div>

                <div class="pt-2">
                    <button type="submit" 
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-rose-700 text-white font-bold text-xs hover:bg-rose-800 shadow-xs touch-target transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Kirim Laporan Kendala</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-shell>
