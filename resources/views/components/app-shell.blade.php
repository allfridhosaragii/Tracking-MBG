@props([
    'title' => 'MBG-Track Operasional',
    'activeRole' => null,
])

<!DOCTYPE html>
<html lang="id" class="h-full bg-[#F5F7F3]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>{{ $title }} : MBG-Track</title>

    <!-- Preload / Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex flex-col antialiased text-[#172923] bg-[#F5F7F3] selection:bg-[#1C6654] selection:text-white">

    <!-- Skip Link for Accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:px-4 focus:py-2.5 focus:bg-[#1C6654] focus:text-white focus:rounded-lg focus:shadow-md focus:outline-none focus:ring-2 focus:ring-[#B78C56]">
        Lewati ke konten utama
    </a>

    <!-- Prototype Operational Banner (Honest Data Contract) -->
    <aside aria-label="Status Lingkungan Sistem" class="bg-[#172923] text-[#F5F7F3] text-xs font-medium py-1.5 px-4 text-center tracking-wide flex items-center justify-center gap-2 border-b border-[#2C3E37]">
        <span class="inline-block w-2 h-2 rounded-full bg-[#B78C56]" aria-hidden="true"></span>
        <span class="text-[11px] sm:text-xs">LINGKUNGAN PENGUJIAN: DATA SIMULASI OPERASIONAL • PROTOTYPE AKADEMIK MBG-TRACK</span>
    </aside>

    <!-- Navigation Bar -->
    <header class="bg-white border-b border-[#DCE5DD] sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-4">
                <!-- Brand & Context -->
                <div class="flex items-center gap-4">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 group focus:outline-none focus:ring-2 focus:ring-[#1C6654] rounded-lg p-1 -ml-1">
                        <div class="w-9 h-9 rounded-xl bg-[#1C6654] flex items-center justify-center text-white font-bold text-base shadow-xs group-hover:bg-[#164D42] smooth-transition">
                            M
                        </div>
                        <div>
                            <span class="text-base font-bold tracking-tight text-[#172923] block leading-tight">MBG-Track</span>
                            <span class="text-[11px] font-medium text-[#53645B] block leading-tight">Satu SPPG • Multi-Sekolah</span>
                        </div>
                    </a>

                    @auth
                        @php
                            $user = auth()->user();
                            $roleEnum = $user->role;
                        @endphp

                        <!-- Desktop Role Navigation (Contextual) -->
                        <nav aria-label="Navigasi Utama" class="hidden md:flex items-center gap-1.5 pl-3 border-l border-[#DCE5DD]">
                            @if ($roleEnum === \App\Enums\UserRole::KEPALA_SPPG)
                                <a href="{{ route('head.dashboard') }}" 
                                   class="px-3 py-2 text-xs font-semibold rounded-lg smooth-transition touch-target flex items-center {{ request()->routeIs('head.dashboard') ? 'bg-[#EDF2ED] text-[#1C6654]' : 'text-[#53645B] hover:text-[#172923] hover:bg-[#F5F7F3]' }}">
                                    Briefing Operasional
                                </a>
                                <a href="{{ route('head.schools.index') }}" 
                                   class="px-3 py-2 text-xs font-semibold rounded-lg smooth-transition touch-target flex items-center {{ request()->routeIs('head.schools.*') ? 'bg-[#EDF2ED] text-[#1C6654]' : 'text-[#53645B] hover:text-[#172923] hover:bg-[#F5F7F3]' }}">
                                    Sekolah &amp; PIC
                                </a>
                                <a href="{{ route('head.employees.index') }}" 
                                   class="px-3 py-2 text-xs font-semibold rounded-lg smooth-transition touch-target flex items-center {{ request()->routeIs('head.employees.*') ? 'bg-[#EDF2ED] text-[#1C6654]' : 'text-[#53645B] hover:text-[#172923] hover:bg-[#F5F7F3]' }}">
                                    Pegawai &amp; Role
                                </a>
                                <a href="{{ route('head.portions.index') }}" 
                                   class="px-3 py-2 text-xs font-semibold rounded-lg smooth-transition touch-target flex items-center {{ request()->routeIs('head.portions.*') ? 'bg-[#EDF2ED] text-[#1C6654]' : 'text-[#53645B] hover:text-[#172923] hover:bg-[#F5F7F3]' }}">
                                    Monitoring Porsi
                                </a>
                            @elseif ($roleEnum === \App\Enums\UserRole::PENGAWAS_GIZI)
                                <a href="{{ route('nutrition.dashboard') }}" 
                                   class="px-3 py-2 text-xs font-semibold rounded-lg smooth-transition touch-target flex items-center {{ request()->routeIs('nutrition.dashboard') ? 'bg-[#EDF2ED] text-[#285F80]' : 'text-[#53645B] hover:text-[#172923] hover:bg-[#F5F7F3]' }}">
                                    Review Desk Gizi
                                </a>
                                <a href="{{ route('nutrition.menus.index') }}" 
                                   class="px-3 py-2 text-xs font-semibold rounded-lg smooth-transition touch-target flex items-center {{ request()->routeIs('nutrition.menus.*') ? 'bg-[#EDF2ED] text-[#285F80]' : 'text-[#53645B] hover:text-[#172923] hover:bg-[#F5F7F3]' }}">
                                    Menu &amp; Gizi
                                </a>
                                <a href="{{ route('nutrition.qc.index') }}" 
                                   class="px-3 py-2 text-xs font-semibold rounded-lg smooth-transition touch-target flex items-center {{ request()->routeIs('nutrition.qc.*') ? 'bg-[#EDF2ED] text-[#285F80]' : 'text-[#53645B] hover:text-[#172923] hover:bg-[#F5F7F3]' }}">
                                    Uji QC Mutu
                                </a>
                            @elseif ($roleEnum === \App\Enums\UserRole::ASISTEN_LAPANGAN)
                                <a href="{{ route('production.dashboard') }}" 
                                   class="px-3 py-2 text-xs font-semibold rounded-lg smooth-transition touch-target flex items-center {{ request()->routeIs('production.dashboard') ? 'bg-[#EDF2ED] text-[#885D1C]' : 'text-[#53645B] hover:text-[#172923] hover:bg-[#F5F7F3]' }}">
                                    Runbook Produksi
                                </a>
                            @elseif ($roleEnum === \App\Enums\UserRole::KURIR)
                                <a href="{{ route('driver.dashboard') }}" 
                                   class="px-3 py-2 text-xs font-semibold rounded-lg smooth-transition touch-target flex items-center {{ request()->routeIs('driver.dashboard') ? 'bg-[#EDF2ED] text-[#55327A]' : 'text-[#53645B] hover:text-[#172923] hover:bg-[#F5F7F3]' }}">
                                    Mission Kurir
                                </a>
                            @elseif ($roleEnum === \App\Enums\UserRole::PIC_SEKOLAH)
                                <a href="{{ route('school.dashboard') }}" 
                                   class="px-3 py-2 text-xs font-semibold rounded-lg smooth-transition touch-target flex items-center {{ request()->routeIs('school.dashboard') ? 'bg-[#EDF2ED] text-[#21684B]' : 'text-[#53645B] hover:text-[#172923] hover:bg-[#F5F7F3]' }}">
                                    Penerimaan Sekolah
                                </a>
                                <a href="{{ route('school.portions.index') }}" 
                                   class="px-3 py-2 text-xs font-semibold rounded-lg smooth-transition touch-target flex items-center {{ request()->routeIs('school.portions.*') ? 'bg-[#EDF2ED] text-[#21684B]' : 'text-[#53645B] hover:text-[#172923] hover:bg-[#F5F7F3]' }}">
                                    Konfirmasi Porsi
                                </a>
                            @endif
                        </nav>
                    @endauth
                </div>

                <!-- User & Action Profile -->
                <div class="flex items-center gap-2.5">
                    @auth
                        <div class="hidden sm:flex flex-col text-right">
                            <span class="text-xs font-semibold text-[#172923] leading-snug">{{ auth()->user()->name }}</span>
                            <span class="text-[11px] text-[#53645B] leading-tight">{{ auth()->user()->role?->label() ?? 'Internal' }}</span>
                        </div>

                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="inline-flex items-center justify-center px-3.5 py-2 text-xs font-semibold text-[#9B3232] bg-[#FDF2F2] hover:bg-[#FBE8E8] border border-[#F5C2C2] rounded-xl smooth-transition cursor-pointer touch-target focus:outline-none focus:ring-2 focus:ring-[#9B3232]"
                                    title="Keluar dari sesi ini">
                                <span>Keluar</span>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" 
                           class="inline-flex items-center justify-center px-4 py-2 text-xs font-semibold text-white bg-[#1C6654] hover:bg-[#164D42] rounded-xl shadow-xs smooth-transition touch-target focus:outline-none focus:ring-2 focus:ring-[#1C6654]">
                            Masuk Portal
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main id="main-content" class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 mb-24 md:mb-8 focus:outline-none" tabindex="-1">
        @if (session('status'))
            <div role="status" class="mb-6 p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-[#1B573E] text-xs sm:text-sm flex items-start gap-3 subtle-shadow">
                <svg class="w-5 h-5 mt-0.5 shrink-0 text-[#21684B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <div class="font-medium leading-relaxed">{{ session('status') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div role="alert" class="mb-6 p-4 rounded-xl bg-[#FDF2F2] border border-[#F5C2C2] text-[#9B3232] text-xs sm:text-sm flex items-start gap-3 subtle-shadow">
                <svg class="w-5 h-5 mt-0.5 shrink-0 text-[#9B3232]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="font-medium leading-relaxed">{{ session('error') }}</div>
            </div>
        @endif

        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-[#DCE5DD] py-4 text-center text-xs text-[#53645B] mt-auto hidden md:block">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2.5">
            <div>
                &copy; {{ date('Y') }} MBG-Track : Calm Utility Operational Monitoring
            </div>
            <div class="flex flex-wrap items-center justify-center gap-3 text-[11px] text-[#53645B]">
                <span>Arsitektur Modular Monolith</span>
                <span aria-hidden="true">•</span>
                <span>WCAG 2.2 AA</span>
                <span aria-hidden="true">•</span>
                <span>Mobile-First 390px</span>
            </div>
        </div>
    </footer>

    @auth
        <!-- Mobile Role-Specific Bottom Navigation (Viewport < 768px) -->
        <nav aria-label="Navigasi Mobile Internal" 
             class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-[#DCE5DD] shadow-lg pb-[env(safe-area-inset-bottom,0px)]">
            <div class="max-w-md mx-auto px-2 flex items-center justify-around h-16">
                @if ($roleEnum === \App\Enums\UserRole::KEPALA_SPPG)
                    <!-- Briefing Operasional -->
                    <a href="{{ route('head.dashboard') }}" 
                       @if(request()->routeIs('head.dashboard')) aria-current="page" @endif
                       class="flex flex-col items-center justify-center flex-1 h-full py-1 text-center touch-target smooth-transition {{ request()->routeIs('head.dashboard') ? 'text-[#1C6654] font-bold' : 'text-[#53645B] hover:text-[#172923]' }}">
                        <svg class="w-5 h-5 mb-0.5 {{ request()->routeIs('head.dashboard') ? 'text-[#1C6654]' : 'text-[#708177]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span class="text-[10px] leading-tight">Briefing</span>
                        @if(request()->routeIs('head.dashboard'))
                            <span class="w-1.5 h-1.5 rounded-full bg-[#1C6654] mt-0.5" aria-hidden="true"></span>
                        @endif
                    </a>

                    <!-- Sekolah & PIC -->
                    <a href="{{ route('head.schools.index') }}" 
                       @if(request()->routeIs('head.schools.*')) aria-current="page" @endif
                       class="flex flex-col items-center justify-center flex-1 h-full py-1 text-center touch-target smooth-transition {{ request()->routeIs('head.schools.*') ? 'text-[#1C6654] font-bold' : 'text-[#53645B] hover:text-[#172923]' }}">
                        <svg class="w-5 h-5 mb-0.5 {{ request()->routeIs('head.schools.*') ? 'text-[#1C6654]' : 'text-[#708177]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span class="text-[10px] leading-tight">Sekolah</span>
                        @if(request()->routeIs('head.schools.*'))
                            <span class="w-1.5 h-1.5 rounded-full bg-[#1C6654] mt-0.5" aria-hidden="true"></span>
                        @endif
                    </a>

                    <!-- Pegawai & Role (K03) -->
                    <a href="{{ route('head.employees.index') }}" 
                       @if(request()->routeIs('head.employees.*')) aria-current="page" @endif
                       class="flex flex-col items-center justify-center flex-1 h-full py-1 text-center touch-target smooth-transition {{ request()->routeIs('head.employees.*') ? 'text-[#1C6654] font-bold' : 'text-[#53645B] hover:text-[#172923]' }}">
                        <svg class="w-5 h-5 mb-0.5 {{ request()->routeIs('head.employees.*') ? 'text-[#1C6654]' : 'text-[#708177]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span class="text-[10px] leading-tight">Pegawai</span>
                        @if(request()->routeIs('head.employees.*'))
                            <span class="w-1.5 h-1.5 rounded-full bg-[#1C6654] mt-0.5" aria-hidden="true"></span>
                        @endif
                    </a>

                    <!-- Monitoring Porsi (PBI-04) -->
                    <a href="{{ route('head.portions.index') }}" 
                       @if(request()->routeIs('head.portions.*')) aria-current="page" @endif
                       class="flex flex-col items-center justify-center flex-1 h-full py-1 text-center touch-target smooth-transition {{ request()->routeIs('head.portions.*') ? 'text-[#1C6654] font-bold' : 'text-[#53645B] hover:text-[#172923]' }}">
                        <svg class="w-5 h-5 mb-0.5 {{ request()->routeIs('head.portions.*') ? 'text-[#1C6654]' : 'text-[#708177]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        <span class="text-[10px] leading-tight">Porsi</span>
                        @if(request()->routeIs('head.portions.*'))
                            <span class="w-1.5 h-1.5 rounded-full bg-[#1C6654] mt-0.5" aria-hidden="true"></span>
                        @endif
                    </a>
                @elseif ($roleEnum === \App\Enums\UserRole::PENGAWAS_GIZI)
                    <a href="{{ route('nutrition.dashboard') }}" 
                       @if(request()->routeIs('nutrition.dashboard')) aria-current="page" @endif
                       class="flex flex-col items-center justify-center flex-1 h-full py-1 text-center touch-target smooth-transition {{ request()->routeIs('nutrition.dashboard') ? 'text-[#285F80] font-bold' : 'text-[#53645B] hover:text-[#172923]' }}">
                        <svg class="w-5 h-5 mb-0.5 {{ request()->routeIs('nutrition.dashboard') ? 'text-[#285F80]' : 'text-[#708177]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-[10px] leading-tight">Desk Gizi</span>
                        @if(request()->routeIs('nutrition.dashboard'))
                            <span class="w-1.5 h-1.5 rounded-full bg-[#285F80] mt-0.5" aria-hidden="true"></span>
                        @endif
                    </a>

                    <a href="{{ route('nutrition.menus.index') }}" 
                       @if(request()->routeIs('nutrition.menus.*')) aria-current="page" @endif
                       class="flex flex-col items-center justify-center flex-1 h-full py-1 text-center touch-target smooth-transition {{ request()->routeIs('nutrition.menus.*') ? 'text-[#285F80] font-bold' : 'text-[#53645B] hover:text-[#172923]' }}">
                        <svg class="w-5 h-5 mb-0.5 {{ request()->routeIs('nutrition.menus.*') ? 'text-[#285F80]' : 'text-[#708177]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <span class="text-[10px] leading-tight">Menu &amp; Gizi</span>
                        @if(request()->routeIs('nutrition.menus.*'))
                            <span class="w-1.5 h-1.5 rounded-full bg-[#285F80] mt-0.5" aria-hidden="true"></span>
                        @endif
                    </a>

                    <a href="{{ route('nutrition.qc.index') }}" 
                       @if(request()->routeIs('nutrition.qc.*')) aria-current="page" @endif
                       class="flex flex-col items-center justify-center flex-1 h-full py-1 text-center touch-target smooth-transition {{ request()->routeIs('nutrition.qc.*') ? 'text-[#285F80] font-bold' : 'text-[#53645B] hover:text-[#172923]' }}">
                        <svg class="w-5 h-5 mb-0.5 {{ request()->routeIs('nutrition.qc.*') ? 'text-[#285F80]' : 'text-[#708177]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span class="text-[10px] leading-tight">QC Mutu</span>
                        @if(request()->routeIs('nutrition.qc.*'))
                            <span class="w-1.5 h-1.5 rounded-full bg-[#285F80] mt-0.5" aria-hidden="true"></span>
                        @endif
                    </a>
                @elseif ($roleEnum === \App\Enums\UserRole::ASISTEN_LAPANGAN)
                    <a href="{{ route('production.dashboard') }}" 
                       @if(request()->routeIs('production.dashboard')) aria-current="page" @endif
                       class="flex flex-col items-center justify-center flex-1 h-full py-1 text-center touch-target smooth-transition text-[#885D1C] font-bold">
                        <svg class="w-5 h-5 mb-0.5 text-[#885D1C]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        <span class="text-[10px] leading-tight">Runbook Produksi</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-[#885D1C] mt-0.5" aria-hidden="true"></span>
                    </a>
                @elseif ($roleEnum === \App\Enums\UserRole::KURIR)
                    <a href="{{ route('driver.dashboard') }}" 
                       @if(request()->routeIs('driver.dashboard')) aria-current="page" @endif
                       class="flex flex-col items-center justify-center flex-1 h-full py-1 text-center touch-target smooth-transition text-[#55327A] font-bold">
                        <svg class="w-5 h-5 mb-0.5 text-[#55327A]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span class="text-[10px] leading-tight">Mission Kurir</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-[#55327A] mt-0.5" aria-hidden="true"></span>
                    </a>
                @elseif ($roleEnum === \App\Enums\UserRole::PIC_SEKOLAH)
                    <a href="{{ route('school.dashboard') }}" 
                       @if(request()->routeIs('school.dashboard')) aria-current="page" @endif
                       class="flex flex-col items-center justify-center flex-1 h-full py-1 text-center touch-target smooth-transition {{ request()->routeIs('school.dashboard') ? 'text-[#21684B] font-bold' : 'text-[#53645B] hover:text-[#172923]' }}">
                        <svg class="w-5 h-5 mb-0.5 {{ request()->routeIs('school.dashboard') ? 'text-[#21684B]' : 'text-[#708177]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-[10px] leading-tight">Penerimaan</span>
                        @if(request()->routeIs('school.dashboard'))
                            <span class="w-1.5 h-1.5 rounded-full bg-[#21684B] mt-0.5" aria-hidden="true"></span>
                        @endif
                    </a>

                    <a href="{{ route('school.portions.index') }}" 
                       @if(request()->routeIs('school.portions.*')) aria-current="page" @endif
                       class="flex flex-col items-center justify-center flex-1 h-full py-1 text-center touch-target smooth-transition {{ request()->routeIs('school.portions.*') ? 'text-[#21684B] font-bold' : 'text-[#53645B] hover:text-[#172923]' }}">
                        <svg class="w-5 h-5 mb-0.5 {{ request()->routeIs('school.portions.*') ? 'text-[#21684B]' : 'text-[#708177]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        <span class="text-[10px] leading-tight">Porsi MBG</span>
                        @if(request()->routeIs('school.portions.*'))
                            <span class="w-1.5 h-1.5 rounded-full bg-[#21684B] mt-0.5" aria-hidden="true"></span>
                        @endif
                    </a>
                @endif
            </div>
        </nav>
    @endauth

</body>
</html>
