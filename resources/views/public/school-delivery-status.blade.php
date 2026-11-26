<!DOCTYPE html>
<html lang="id" class="h-full bg-[#F5F7F3]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Status Pengantaran MBG | {{ $school->name }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F5F7F3] text-[#172923] antialiased min-h-screen flex flex-col justify-between selection:bg-[#1C6654] selection:text-white">

    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:px-4 focus:py-2.5 focus:bg-[#1C6654] focus:text-white focus:rounded-xl focus:shadow-md inline-flex items-center min-h-[44px]">
        Lewati ke konten utama
    </a>

    <!-- Public Header -->
    <header class="bg-white border-b border-[#DCE5DD] px-4 py-3 sm:px-6 sticky top-0 z-30">
        <div class="max-w-3xl mx-auto flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-[#1C6654] text-white flex items-center justify-center font-bold text-sm tracking-tight shrink-0">
                    M
                </span>
                <div>
                    <span class="text-sm font-bold text-[#172923] tracking-tight block leading-tight">MBG-Track</span>
                    <span class="text-[11px] text-[#53645B] block">Portal Informasi Publik Pengantaran</span>
                </div>
            </div>

            <a href="{{ route('public.schools.show', $school->public_slug ?: $school->school_code) }}"
               class="min-h-[44px] inline-flex items-center text-xs font-bold text-[#1C6654] hover:text-[#172923] border border-[#DCE5DD] px-3.5 py-2 rounded-xl bg-[#FAFBF9] hover:bg-white smooth-transition">
                Profil Sekolah
            </a>
        </div>
    </header>

    <!-- Main Content (U04 Minimal Projection) -->
    <main id="main-content" class="flex-1 max-w-3xl w-full mx-auto px-4 py-6 sm:py-8 sm:px-6 space-y-6">
        <!-- School Identity Card -->
        <section aria-labelledby="school-status-heading" class="luxury-surface p-6 space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="eyebrow-label text-[#1C6654] bg-[#EDF7F2] border border-[#BCE2D0] px-2.5 py-0.5 rounded-md">
                    Status Distribusi Hari Ini
                </span>
                <span class="text-xs font-mono text-[#53645B]">{{ $school->school_code }}</span>
            </div>

            <div>
                <h1 id="school-status-heading" class="page-title">{{ $school->name }}</h1>
                <p class="text-xs sm:text-sm text-[#53645B] mt-0.5">{{ $school->address }}</p>
            </div>

            @if ($school->sppg)
                <div class="pt-3 border-t border-[#E7ECE8] flex flex-wrap items-center gap-1.5 text-xs text-[#53645B]">
                    <span>Dapur Penyedia (SPPG):</span>
                    <span class="font-bold text-[#172923]">{{ $school->sppg->name }}</span>
                </div>
            @endif
        </section>

        <!-- Current Delivery Stage Card -->
        <section aria-labelledby="current-stage-heading" class="luxury-surface p-6 space-y-6 {{ $data['is_arrived'] || $data['is_received'] ? 'border-[#1C6654]' : '' }}">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-[#E7ECE8]">
                <div>
                    <span class="eyebrow-label block mb-1">Status Pengantaran</span>
                    <h2 id="current-stage-heading" class="text-lg font-bold text-[#172923]">
                        {{ $data['delivery_stage'] }}
                    </h2>
                </div>

                <div class="flex items-center gap-2 self-start sm:self-auto">
                    @if ($data['is_live'])
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-[#EDF7F2] text-[#21684B] border border-[#BCE2D0]">
                            <span class="w-2 h-2 rounded-full bg-[#21684B]" aria-hidden="true"></span>
                            <span>Data Terkini (Live)</span>
                        </span>
                    @elseif ($data['is_stale'])
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-[#FEF7EC] text-[#885D1C] border border-[#F7D7A3]">
                            <span class="w-2 h-2 rounded-full bg-[#885D1C]" aria-hidden="true"></span>
                            <span>Sinyal Tertunda (Stale)</span>
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-[#FAFBF9] text-[#53645B] border border-[#DCE5DD]">
                            Terkonfirmasi
                        </span>
                    @endif
                </div>
            </div>

            <!-- ETA & Last Updated -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="inset-surface p-4 space-y-1">
                    <span class="eyebrow-label block">Perkiraan Waktu Tiba (ETA)</span>
                    @if ($data['eta'])
                        <span class="text-xl font-bold font-mono text-[#1C6654] tabular-nums block">{{ $data['eta'] }}</span>
                        <span class="text-[11px] text-[#53645B]">Berdasarkan kondisi perjalanan armada aktif</span>
                    @else
                        <span class="text-sm font-bold text-[#53645B] block">Estimasi Belum Tersedia</span>
                        <span class="text-[11px] text-[#53645B]">Armada belum dalam perjalanan langsung ke sekolah</span>
                    @endif
                </div>

                <div class="inset-surface p-4 space-y-1">
                    <span class="eyebrow-label block">Pembaruan Terakhir</span>
                    <span class="text-base font-bold font-mono text-[#172923] tabular-nums block">
                        {{ \Carbon\Carbon::parse($data['last_updated'])->translatedFormat('H:i:s') }} WIB
                    </span>
                    <span class="text-[11px] text-[#53645B]">Waktu server MBG-Track terverifikasi</span>
                </div>
            </div>

            <!-- Public Privacy Notice -->
            <div class="p-4 rounded-xl bg-[#EDF2ED] border border-[#DCE5DD] text-xs text-[#53645B] leading-relaxed space-y-1">
                <strong class="text-[#172923] font-bold block">Privasi dan Keamanan Pengantaran</strong>
                <p>
                    Halaman ini menampilkan status progres pengantaran sekolah aktif tanpa menayangkan identitas pribadi pengemudi, koordinat GPS mentah kurir, atau rute sekolah lain demi keamanan logistik dan privasi operasional.
                </p>
            </div>
        </section>
    </main>

    <!-- Public Footer -->
    <footer class="bg-white border-t border-[#DCE5DD] py-4 px-4 text-center text-xs text-[#53645B]">
        <div class="max-w-3xl mx-auto">
            MBG-Track • Sistem Pemantauan Transparansi Makanan Bergizi Gratis. Data operasional diperbarui secara berkala.
        </div>
    </footer>
</body>
</html>
