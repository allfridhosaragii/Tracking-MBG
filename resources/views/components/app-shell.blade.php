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
    <title>{{ $title }} — MBG-Track</title>

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
        <span class="text-[11px] sm:text-xs">LINGKUNGAN PENGUJIAN: DATA SIMULASI OPERASIONAL — PROTOTYPE AKADEMIK MBG-TRACK</span>
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
                            @elseif ($roleEnum === \App\Enums\UserRole::PENGAWAS_GIZI)
                                <a href="{{ route('nutrition.dashboard') }}" 
                                   class="px-3 py-2 text-xs font-semibold rounded-lg smooth-transition touch-target flex items-center {{ request()->routeIs('nutrition.dashboard') ? 'bg-[#EDF2ED] text-[#285F80]' : 'text-[#53645B] hover:text-[#172923] hover:bg-[#F5F7F3]' }}">
                                    Review Desk Gizi
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
    <main id="main-content" class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 mb-12 sm:mb-6 focus:outline-none" tabindex="-1">
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
    <footer class="bg-white border-t border-[#DCE5DD] py-4 text-center text-xs text-[#53645B] mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2.5">
            <div>
                &copy; {{ date('Y') }} MBG-Track — Calm Utility Operational Monitoring
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

</body>
</html>
