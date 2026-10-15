<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Status Pengantaran MBG : {{ $school->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F5F7F3] text-[#172923] font-sans antialiased min-h-screen flex flex-col justify-between selection:bg-[#1C6654] selection:text-white">

    <!-- Header Publik -->
    <header class="bg-white border-b border-[#DCE5DD] px-4 py-3 sm:px-6">
        <div class="max-w-3xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-[#1C6654] text-white flex items-center justify-center font-bold text-sm tracking-wider">
                    MBG
                </span>
                <div>
                    <span class="text-xs font-bold text-[#172923] tracking-wide block">MBG-Track</span>
                    <span class="text-[10px] text-[#708177] block">Portal Informasi Publik Pengantaran</span>
                </div>
            </div>

            <a href="{{ route('public.schools.show', $school->public_slug ?: $school->school_code) }}" 
               class="text-xs font-semibold text-[#1C6654] hover:text-[#172923] border border-[#DCE5DD] px-3 py-1.5 rounded-lg bg-[#F5F7F3] hover:bg-white smooth-transition touch-target">
                Profil Sekolah
            </a>
        </div>
    </header>

    <!-- Main Content (Page U04 Minimal Projection) -->
    <main class="flex-1 max-w-3xl w-full mx-auto px-4 py-8 sm:px-6 space-y-6">

        <!-- Banner Identitas Sekolah -->
        <div class="bg-white rounded-2xl border border-[#DCE5DD] p-6 shadow-xs space-y-3">
            <div class="flex items-center gap-2">
                <span class="text-[11px] font-bold text-[#1C6654] uppercase tracking-wider bg-[#EDF2ED] px-2 py-0.5 rounded">
                    Status Distribusi Hari Ini
                </span>
                <span class="text-xs font-mono text-[#53645B]">{{ $school->school_code }}</span>
            </div>

            <div>
                <h1 class="text-2xl font-bold text-[#172923] tracking-tight">{{ $school->name }}</h1>
                <p class="text-xs text-[#53645B] mt-0.5">{{ $school->address }}</p>
            </div>

            @if ($school->sppg)
                <div class="pt-2 border-t border-[#EDF2ED] flex items-center gap-1.5 text-xs text-[#53645B]">
                    <span class="text-[#708177]">Dapur Penyedia (SPPG):</span>
                    <span class="font-semibold text-[#172923]">{{ $school->sppg->name }}</span>
                </div>
            @endif
        </div>

        <!-- Kartu Status Pengantaran Saat Ini -->
        <div class="bg-white rounded-2xl border-2 {{ $data['is_arrived'] || $data['is_received'] ? 'border-emerald-300' : 'border-[#DCE5DD]' }} p-6 shadow-xs space-y-6">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-[#EDF2ED]">
                <div>
                    <span class="text-[11px] font-semibold text-[#708177] uppercase tracking-wider block">Status Pengantaran</span>
                    <h2 class="text-lg font-bold text-[#172923] mt-0.5">
                        {{ $data['delivery_stage'] }}
                    </h2>
                </div>

                <div class="flex items-center gap-2">
                    @if ($data['is_live'])
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Data Terkini (Live)
                        </span>
                    @elseif ($data['is_stale'])
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            Sinyal Tertunda (Stale)
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-50 text-slate-700 border border-slate-200">
                            Terkonfirmasi
                        </span>
                    @endif
                </div>
            </div>

            <!-- Estimasi Kedatangan & Waktu Pembaharuan -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl bg-[#F5F7F3] border border-[#EDF2ED] space-y-1">
                    <span class="text-[11px] font-semibold text-[#708177] block">Perkiraan Waktu Tiba (ETA)</span>
                    @if ($data['eta'])
                        <span class="text-xl font-bold font-mono text-[#1C6654] block">{{ $data['eta'] }}</span>
                        <span class="text-[10px] text-[#708177]">Berdasarkan kondisi perjalanan armada aktif</span>
                    @else
                        <span class="text-sm font-semibold text-[#53645B] block">Estimasi Belum Tersedia</span>
                        <span class="text-[10px] text-[#708177]">Armada belum dalam perjalanan langsung ke sekolah</span>
                    @endif
                </div>

                <div class="p-4 rounded-xl bg-[#F5F7F3] border border-[#EDF2ED] space-y-1">
                    <span class="text-[11px] font-semibold text-[#708177] block">Pembaruan Terakhir</span>
                    <span class="text-sm font-bold font-mono text-[#172923] block">
                        {{ \Carbon\Carbon::parse($data['last_updated'])->translatedFormat('H:i:s') }} WIB
                    </span>
                    <span class="text-[10px] text-[#708177]">Waktu server MBG-Track terverifikasi</span>
                </div>
            </div>

            <!-- Informasi Kebijakan Privasi & Keamanan Publik -->
            <div class="p-4 rounded-xl bg-[#EDF2ED]/50 border border-[#DCE5DD] text-xs text-[#53645B] leading-relaxed space-y-1">
                <strong class="text-[#172923] font-bold block">Privasi dan Keamanan Pengantaran:</strong>
                <p>
                    Halaman ini hanya menampilkan status progres pengantaran sekolah aktif tanpa menampilkan identitas pribadi pengemudi, koordinat GPS mentah kurir, atau rute internal sekolah lain demi keamanan logistik dan privasi operasional.
                </p>
            </div>
        </div>

    </main>

    <!-- Footer Publik -->
    <footer class="bg-white border-t border-[#DCE5DD] py-4 px-4 text-center text-xs text-[#708177]">
        <div class="max-w-3xl mx-auto">
            MBG-Track : Sistem Pemantauan Transparansi Makanan Bergizi Gratis. Data operasional diperbarui secara berkala.
        </div>
    </footer>

</body>
</html>
