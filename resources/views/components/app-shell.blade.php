@props([
    'title' => 'Operasional',
    'activeRole' => null,
])

@php
    $cleanTitle = trim(preg_replace('/\s*[:|]\s*MBG-Track\s*$/i', '', (string) $title));
    if ($cleanTitle === '') {
        $cleanTitle = 'Operasional';
    }
@endphp

<!DOCTYPE html>
<html lang="id" class="h-full bg-[#F5F7F3]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>{{ $cleanTitle }} | MBG-Track</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex flex-col antialiased text-[#172923] bg-[#F5F7F3] selection:bg-[#1C6654] selection:text-white">

    <!-- Skip Link for Keyboard Accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:px-4 focus:py-2.5 focus:bg-[#1C6654] focus:text-white focus:rounded-xl focus:shadow-md touch-target inline-flex items-center">
        Lewati ke konten utama
    </a>

    <!-- Honest Operational Environment Strip -->
    <aside aria-label="Status Lingkungan Sistem" class="bg-[#172923] text-[#E7ECE8] text-[10px] sm:text-[11px] font-medium h-7 px-3 sm:px-6 flex items-center justify-center gap-2 border-b border-[#283D35] shrink-0">
        <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#B78C56] shrink-0" aria-hidden="true"></span>
        <span class="truncate tracking-wide">LINGKUNGAN PENGUJIAN • Data Simulasi Operasional MBG-Track</span>
    </aside>

    @auth
        @php
            $user = auth()->user();
            $roleEnum = $user->role;
            $unitName = $user->school?->name ?? $user->sppg?->name ?? 'Unit Operasional MBG';
            $unitCode = $user->school?->school_code ?? $user->sppg?->code ?? 'MBG';

            $primaryNav = [];
            $secondaryNav = [];

            if ($roleEnum === \App\Enums\UserRole::KEPALA_SPPG) {
                $primaryNav = [
                    ['label' => 'Briefing', 'fullLabel' => 'Briefing Operasional', 'route' => 'head.dashboard', 'active' => ['head.dashboard'], 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                    ['label' => 'Porsi', 'fullLabel' => 'Monitoring Porsi', 'route' => 'head.portions.index', 'active' => ['head.portions.*'], 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                    ['label' => 'Produksi', 'fullLabel' => 'Perencanaan Produksi', 'route' => 'head.production.index', 'active' => ['head.production.*'], 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                    ['label' => 'Distribusi', 'fullLabel' => 'Kontrol Distribusi', 'route' => 'head.distribution.index', 'active' => ['head.distribution.*'], 'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'],
                ];
                $secondaryNav = [
                    ['label' => 'Sekolah & PIC', 'fullLabel' => 'Sekolah Binaan & PIC', 'route' => 'head.schools.index', 'active' => ['head.schools.*'], 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                    ['label' => 'Pegawai', 'fullLabel' => 'Direktori Pegawai & Role', 'route' => 'head.employees.index', 'active' => ['head.employees.*'], 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                    ['label' => 'Kendala', 'fullLabel' => 'Pusat Kendala Operasional', 'route' => 'incidents.index', 'active' => ['incidents.*'], 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
                ];
            } elseif ($roleEnum === \App\Enums\UserRole::PENGAWAS_GIZI) {
                $primaryNav = [
                    ['label' => 'Desk Gizi', 'fullLabel' => 'Desk Verifikasi Gizi', 'route' => 'nutrition.dashboard', 'active' => ['nutrition.dashboard'], 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['label' => 'Menu', 'fullLabel' => 'Katalog Menu & Gizi', 'route' => 'nutrition.menus.index', 'active' => ['nutrition.menus.*'], 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                    ['label' => 'Uji QC', 'fullLabel' => 'Meja Inspeksi Mutu (QC)', 'route' => 'nutrition.qc.index', 'active' => ['nutrition.qc.*'], 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                    ['label' => 'Kendala', 'fullLabel' => 'Kendala Mutu & Pangan', 'route' => 'incidents.index', 'active' => ['incidents.*'], 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
                ];
            } elseif ($roleEnum === \App\Enums\UserRole::ASISTEN_LAPANGAN) {
                $primaryNav = [
                    ['label' => 'Dapur', 'fullLabel' => 'Runbook Eksekusi Dapur', 'route' => 'production.dashboard', 'active' => ['production.dashboard', 'production.plans.*'], 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                    ['label' => 'Ompreng', 'fullLabel' => 'Logistik & Retur Wadah', 'route' => 'production.logistics.index', 'active' => ['production.logistics.*'], 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                    ['label' => 'Kendala', 'fullLabel' => 'Kendala Operasional', 'route' => 'incidents.index', 'active' => ['incidents.*'], 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
                ];
            } elseif ($roleEnum === \App\Enums\UserRole::KURIR) {
                $primaryNav = [
                    ['label' => 'Beranda', 'fullLabel' => 'Status Misi Kurir', 'route' => 'driver.dashboard', 'active' => ['driver.dashboard'], 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ['label' => 'Rute Trip', 'fullLabel' => 'Tugas Perjalanan', 'route' => 'driver.trips.index', 'active' => ['driver.trips.*'], 'icon' => 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1'],
                    ['label' => 'Jemput', 'fullLabel' => 'Penjemputan Ompreng', 'route' => 'driver.pickups.index', 'active' => ['driver.pickups.*'], 'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
                    ['label' => 'Kendala', 'fullLabel' => 'Lapor Kendala Rute', 'route' => 'incidents.index', 'active' => ['incidents.*'], 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
                ];
            } elseif ($roleEnum === \App\Enums\UserRole::PIC_SEKOLAH) {
                $primaryNav = [
                    ['label' => 'Beranda', 'fullLabel' => 'Beranda Sekolah', 'route' => 'school.dashboard', 'active' => ['school.dashboard'], 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ['label' => 'Porsi', 'fullLabel' => 'Konfirmasi Porsi', 'route' => 'school.portions.index', 'active' => ['school.portions.*'], 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                    ['label' => 'Terima', 'fullLabel' => 'Penerimaan Makanan', 'route' => 'school.receipts.index', 'active' => ['school.receipts.*'], 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['label' => 'Bagikan', 'fullLabel' => 'Pembagian Makanan', 'route' => 'school.meal-distribution.index', 'active' => ['school.meal-distribution.*'], 'icon' => 'M12 8v13m0-13V4a2 2 0 114 0v4M12 8H8a2 2 0 00-2 2v10a1 1 0 001 1h10a1 1 0 001-1V10a2 2 0 00-2-2h-4z'],
                ];
                $secondaryNav = [
                    ['label' => 'Ompreng', 'fullLabel' => 'Kesiapan & Retur Ompreng', 'route' => 'school.trays.index', 'active' => ['school.trays.*'], 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                    ['label' => 'Kendala', 'fullLabel' => 'Lapor Kendala Sekolah', 'route' => 'incidents.index', 'active' => ['incidents.*'], 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
                ];
            }

            $secondaryActive = false;
            foreach ($secondaryNav as $secItem) {
                if (request()->routeIs(...$secItem['active'])) {
                    $secondaryActive = true;
                    break;
                }
            }
        @endphp

        <div class="flex-1 flex flex-col lg:flex-row min-h-0">
            <!-- Desktop Left Editorial Navigation Rail (>= 1024px) -->
            <aside aria-label="Navigasi Samping Desktop" class="hidden lg:flex lg:flex-col lg:w-[264px] lg:shrink-0 bg-white border-r border-[#DCE5DD] lg:sticky lg:top-7 lg:h-[calc(100vh-1.75rem)]">
                <!-- Brand & Unit Lockup -->
                <div class="px-5 py-5 border-b border-[#E7ECE8]">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 group rounded-xl p-1 -m-1">
                        <div class="w-9 h-9 rounded-xl bg-[#1C6654] text-white flex items-center justify-center font-bold text-sm tracking-tight group-hover:bg-[#164D42] smooth-transition shrink-0">
                            M
                        </div>
                        <div class="min-w-0">
                            <span class="text-[15px] font-bold tracking-tight text-[#172923] block leading-tight">MBG-Track</span>
                            <span class="text-[11px] font-medium text-[#53645B] block truncate mt-0.5" title="{{ $unitName }}">{{ $unitCode }}</span>
                        </div>
                    </a>
                </div>

                <!-- Vertical Navigation Links (Zero Text Wrapping) -->
                <nav aria-label="Navigasi Utama" class="flex-1 overflow-y-auto px-3.5 py-4 space-y-5">
                    <div>
                        <span class="px-2.5 text-[10px] font-bold uppercase tracking-widest text-[#53645B] block mb-2">Alur Utama</span>
                        <div class="space-y-1">
                            @foreach ($primaryNav as $item)
                                @php $isActive = request()->routeIs(...$item['active']); @endphp
                                <a href="{{ route($item['route']) }}"
                                   @if($isActive) aria-current="page" @endif
                                   class="min-h-[44px] px-3 py-2 rounded-xl flex items-center gap-3 text-[13px] font-semibold smooth-transition {{ $isActive ? 'bg-[#EDF7F2] text-[#1C6654] border border-[#BCE2D0]/80' : 'text-[#53645B] hover:text-[#172923] hover:bg-[#F5F7F3] border border-transparent' }}">
                                    <svg class="w-[18px] h-[18px] shrink-0 {{ $isActive ? 'text-[#1C6654]' : 'text-[#53645B]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $item['icon'] }}"/>
                                    </svg>
                                    <span class="truncate">{{ $item['fullLabel'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    @if (count($secondaryNav) > 0)
                        <div>
                            <span class="px-2.5 text-[10px] font-bold uppercase tracking-widest text-[#53645B] block mb-2">Tata Kelola &amp; Kendala</span>
                            <div class="space-y-1">
                                @foreach ($secondaryNav as $item)
                                    @php $isActive = request()->routeIs(...$item['active']); @endphp
                                    <a href="{{ route($item['route']) }}"
                                       @if($isActive) aria-current="page" @endif
                                       class="min-h-[44px] px-3 py-2 rounded-xl flex items-center gap-3 text-[13px] font-semibold smooth-transition {{ $isActive ? 'bg-[#EDF7F2] text-[#1C6654] border border-[#BCE2D0]/80' : 'text-[#53645B] hover:text-[#172923] hover:bg-[#F5F7F3] border border-transparent' }}">
                                        <svg class="w-[18px] h-[18px] shrink-0 {{ $isActive ? 'text-[#1C6654]' : 'text-[#53645B]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $item['icon'] }}"/>
                                        </svg>
                                        <span class="truncate">{{ $item['fullLabel'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </nav>

                <!-- User Identity & Clean Sign-Out -->
                <div class="p-3.5 border-t border-[#E7ECE8] bg-[#FAFBF9]">
                    <div class="px-2 py-1.5 mb-2">
                        <span class="text-xs font-bold text-[#172923] block truncate" title="{{ $user->name }}">{{ $user->name }}</span>
                        <span class="text-[11px] text-[#53645B] block truncate mt-0.5">{{ $roleEnum?->label() ?? 'Petugas' }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full min-h-[44px] px-3 py-2 rounded-xl text-xs font-semibold text-[#9B3232] bg-white hover:bg-[#FDF2F2] border border-[#DCE5DD] hover:border-[#F5C2C2] flex items-center justify-center gap-2 smooth-transition cursor-pointer">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span>Keluar Sesi</span>
                        </button>
                    </form>
                </div>
            </aside>

            <!-- Right Content Workspace -->
            <div class="flex-1 min-w-0 flex flex-col">
                <!-- Slim Mobile Top Header (< 1024px) -->
                <header class="lg:hidden bg-white/95 backdrop-blur-sm border-b border-[#DCE5DD] sticky top-0 z-30">
                    <div class="px-4 h-14 flex items-center justify-between gap-3">
                        <a href="{{ route('home') }}" class="flex items-center gap-2.5 min-w-0 py-1">
                            <div class="w-8 h-8 rounded-lg bg-[#1C6654] text-white flex items-center justify-center font-bold text-sm shrink-0">
                                M
                            </div>
                            <div class="min-w-0">
                                <span class="text-sm font-bold tracking-tight text-[#172923] block leading-tight">MBG-Track</span>
                                <span class="text-[11px] text-[#53645B] block truncate leading-tight">{{ $unitName }}</span>
                            </div>
                        </a>

                        <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                            @csrf
                            <button type="submit"
                                    class="min-h-[44px] px-3 rounded-xl text-xs font-semibold text-[#9B3232] bg-[#FDF2F2] hover:bg-[#FBE8E8] border border-[#F5C2C2] inline-flex items-center justify-center smooth-transition cursor-pointer"
                                    title="Keluar dari sesi">
                                Keluar
                            </button>
                        </form>
                    </div>
                </header>

                <!-- Calm Desktop Context Bar (>= 1024px) -->
                <header class="hidden lg:flex items-center justify-between h-14 px-8 bg-white/80 backdrop-blur-sm border-b border-[#E7ECE8]">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="text-xs font-semibold text-[#53645B]">{{ $roleEnum?->label() }}</span>
                        <span class="text-[#DCE5DD]" aria-hidden="true">/</span>
                        <span class="text-xs font-bold text-[#172923] truncate">{{ $cleanTitle }}</span>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-[#FAFBF9] border border-[#DCE5DD] text-xs font-medium text-[#172923]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#21684B]" aria-hidden="true"></span>
                            <span class="truncate max-w-[260px]">{{ $unitName }}</span>
                        </span>
                    </div>
                </header>

                <!-- Main Workspace Canvas -->
                <main id="main-content" class="flex-1 w-full max-w-[1240px] mx-auto px-4 sm:px-6 lg:px-8 py-5 sm:py-6 lg:py-8 pb-24 lg:pb-10 focus:outline-none" tabindex="-1">
                    @if (session('status'))
                        <div role="status" class="mb-5 p-3.5 sm:p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-[#1B573E] text-xs sm:text-sm flex items-start gap-3 subtle-shadow">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 mt-0.5 shrink-0 text-[#21684B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <div class="font-medium leading-relaxed">{{ session('status') }}</div>
                        </div>
                    @endif

                    @if (session('error'))
                        <div role="alert" class="mb-5 p-3.5 sm:p-4 rounded-xl bg-[#FDF2F2] border border-[#F5C2C2] text-[#9B3232] text-xs sm:text-sm flex items-start gap-3 subtle-shadow">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 mt-0.5 shrink-0 text-[#9B3232]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div class="font-medium leading-relaxed">{{ session('error') }}</div>
                        </div>
                    @endif

                    {{ $slot }}
                </main>

                <!-- Calm Institutional Desktop Footer -->
                <footer class="hidden lg:block border-t border-[#E7ECE8] bg-white/60 py-4 px-8 text-xs text-[#53645B] mt-auto">
                    <div class="max-w-[1240px] mx-auto flex items-center justify-between gap-4">
                        <span>&copy; {{ date('Y') }} MBG-Track • Sistem Koordinasi &amp; Ketertelusuran Pangan Bergizi</span>
                        <span class="text-[11px]">Akses Terisolasi per Unit Layanan</span>
                    </div>
                </footer>
            </div>
        </div>

        <!-- Mobile Bottom Navigation Bar (< 1024px) with Full Route Parity -->
        <nav aria-label="Navigasi Mobile Internal" class="lg:hidden fixed bottom-0 left-0 right-0 z-[45] bg-white border-t border-[#DCE5DD] pb-[env(safe-area-inset-bottom,0px)]">
            <div class="max-w-lg mx-auto px-1.5 flex items-center justify-around h-16">
                @foreach ($primaryNav as $item)
                    @php $isActive = request()->routeIs(...$item['active']); @endphp
                    <a href="{{ route($item['route']) }}"
                       @if($isActive) aria-current="page" @endif
                       class="flex flex-col items-center justify-center flex-1 h-full py-1 px-1 text-center touch-target smooth-transition {{ $isActive ? 'text-[#1C6654] font-bold' : 'text-[#53645B] hover:text-[#172923]' }}">
                        <svg class="w-5 h-5 mb-1 pointer-events-none {{ $isActive ? 'text-[#1C6654]' : 'text-[#53645B]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isActive ? '2.1' : '1.75' }}" d="{{ $item['icon'] }}"/>
                        </svg>
                        <span class="text-[10px] leading-none truncate max-w-[68px]">{{ $item['label'] }}</span>
                    </a>
                @endforeach

                @if (count($secondaryNav) > 0)
                    <button type="button"
                            id="mobileMoreTrigger"
                            aria-expanded="false"
                            aria-controls="mobileMoreSheet"
                            onclick="toggleMobileMoreSheet()"
                            class="flex flex-col items-center justify-center flex-1 h-full py-1 px-1 text-center touch-target smooth-transition cursor-pointer {{ $secondaryActive ? 'text-[#1C6654] font-bold' : 'text-[#53645B] hover:text-[#172923]' }}">
                        <svg class="w-5 h-5 mb-1 pointer-events-none {{ $secondaryActive ? 'text-[#1C6654]' : 'text-[#53645B]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <span class="text-[10px] leading-none">Lainnya</span>
                    </button>
                @endif
            </div>
        </nav>

        @if (count($secondaryNav) > 0)
            <!-- Mobile Slide-Up Drawer for Secondary Routes -->
            <div id="mobileMoreBackdrop"
                 onclick="toggleMobileMoreSheet(false)"
                 class="lg:hidden fixed inset-0 z-40 bg-[#172923]/35 hidden"
                 aria-hidden="true"></div>

            <div id="mobileMoreSheet"
                 role="dialog"
                 aria-modal="true"
                 aria-label="Menu Navigasi Tambahan"
                 class="lg:hidden fixed bottom-16 left-0 right-0 z-50 bg-white rounded-t-2xl border-t border-[#DCE5DD] floating-shadow p-4 pb-5 hidden">
                <div class="max-w-lg mx-auto space-y-3">
                    <div class="flex items-center justify-between pb-2.5 border-b border-[#E7ECE8]">
                        <div>
                            <span class="text-xs font-bold text-[#172923] block">{{ $user->name }}</span>
                            <span class="text-[11px] text-[#53645B] block">{{ $roleEnum?->label() }} • {{ $unitName }}</span>
                        </div>
                        <button type="button"
                                onclick="toggleMobileMoreSheet(false)"
                                class="touch-target inline-flex items-center justify-center rounded-xl text-[#53645B] hover:text-[#172923] hover:bg-[#F5F7F3] cursor-pointer"
                                aria-label="Tutup menu tambahan">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-1.5">
                        @foreach ($secondaryNav as $item)
                            @php $isActive = request()->routeIs(...$item['active']); @endphp
                            <a href="{{ route($item['route']) }}"
                               class="min-h-[48px] px-3.5 py-2.5 rounded-xl flex items-center justify-between gap-3 text-xs font-semibold smooth-transition {{ $isActive ? 'bg-[#EDF7F2] text-[#1C6654] border border-[#BCE2D0]' : 'bg-[#FAFBF9] text-[#172923] hover:bg-[#EDF2ED] border border-[#E7ECE8]' }}">
                                <span class="flex items-center gap-3">
                                    <svg class="w-4 h-4 shrink-0 text-[#1C6654]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $item['icon'] }}"/>
                                    </svg>
                                    <span>{{ $item['fullLabel'] }}</span>
                                </span>
                                <svg class="w-4 h-4 text-[#53645B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <script>
                function toggleMobileMoreSheet(forceState) {
                    const sheet = document.getElementById('mobileMoreSheet');
                    const backdrop = document.getElementById('mobileMoreBackdrop');
                    const trigger = document.getElementById('mobileMoreTrigger');
                    if (!sheet || !backdrop || !trigger) return;
                    const shouldOpen = typeof forceState === 'boolean' ? forceState : sheet.classList.contains('hidden');
                    sheet.classList.toggle('hidden', !shouldOpen);
                    backdrop.classList.toggle('hidden', !shouldOpen);
                    trigger.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
                    if (shouldOpen) {
                        const closeBtn = sheet.querySelector('button[onclick*="toggleMobileMoreSheet"]');
                        if (closeBtn) closeBtn.focus();
                    } else {
                        trigger.focus();
                    }
                }

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' || event.key === 'Esc' || event.keyCode === 27) {
                        const sheet = document.getElementById('mobileMoreSheet');
                        if (sheet && !sheet.classList.contains('hidden')) {
                            toggleMobileMoreSheet(false);
                        }
                    }
                }, true);
            </script>
        @endif
    @else
        <!-- Guest & Public Transparency Header -->
        <header class="bg-white border-b border-[#DCE5DD] sticky top-0 z-30">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-15 flex items-center justify-between gap-4">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 group rounded-xl p-1 -ml-1">
                    <div class="w-9 h-9 rounded-xl bg-[#1C6654] text-white flex items-center justify-center font-bold text-sm group-hover:bg-[#164D42] smooth-transition">
                        M
                    </div>
                    <div>
                        <span class="text-[15px] font-bold tracking-tight text-[#172923] block leading-tight">MBG-Track</span>
                        <span class="text-[11px] font-medium text-[#53645B] block leading-tight">Transparansi &amp; Operasional Gizi</span>
                    </div>
                </a>

                <div>
                    @if (! request()->routeIs('login'))
                        <a href="{{ route('login') }}"
                           class="inline-flex items-center justify-center px-4 py-2 text-xs font-semibold text-white bg-[#1C6654] hover:bg-[#164D42] rounded-xl smooth-transition touch-target">
                            Masuk Portal
                        </a>
                    @else
                        <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#FAFBF9] border border-[#DCE5DD] text-[11px] font-semibold text-[#53645B]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#1C6654]" aria-hidden="true"></span>
                            <span>Akses Terotorisasi</span>
                        </span>
                    @endif
                </div>
            </div>
        </header>

        <main id="main-content" class="flex-1 w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-9 focus:outline-none" tabindex="-1">
            @if (session('status'))
                <div role="status" class="mb-5 p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-[#1B573E] text-xs sm:text-sm flex items-start gap-3 subtle-shadow">
                    <svg class="w-5 h-5 mt-0.5 shrink-0 text-[#21684B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <div class="font-medium leading-relaxed">{{ session('status') }}</div>
                </div>
            @endif

            @if (session('error'))
                <div role="alert" class="mb-5 p-4 rounded-xl bg-[#FDF2F2] border border-[#F5C2C2] text-[#9B3232] text-xs sm:text-sm flex items-start gap-3 subtle-shadow">
                    <svg class="w-5 h-5 mt-0.5 shrink-0 text-[#9B3232]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="font-medium leading-relaxed">{{ session('error') }}</div>
                </div>
            @endif

            {{ $slot }}
        </main>

        <footer class="border-t border-[#E7ECE8] bg-white py-4 px-4 sm:px-6 text-xs text-[#53645B] mt-auto">
            <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2 text-center sm:text-left">
                <span>&copy; {{ date('Y') }} MBG-Track • Ketertelusuran Layanan Makan Bergizi Gratis</span>
                <span class="text-[11px]">Data Publik Tersanitasi &amp; Terlindungi Privasi</span>
            </div>
        </footer>
    @endauth

</body>
</html>
