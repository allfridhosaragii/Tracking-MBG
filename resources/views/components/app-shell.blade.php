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
<body class="h-full flex flex-col antialiased text-[#172923] bg-[#F5F7F3] selection:bg-[#1C6654] selection:text-white">

    <!-- Prototype Operational Banner (Honest Data Contract) -->
    <div class="bg-[#172923] text-[#F5F7F3] text-xs font-medium py-1.5 px-4 text-center tracking-wide flex items-center justify-center gap-2 border-b border-[#2C3E37]">
        <span class="inline-block w-2 h-2 rounded-full bg-[#B78C56] animate-pulse"></span>
        <span>LINGKUNGAN PENGUJIAN: DATA SIMULASI OPERASIONAL — BUKAN SISTEM RESMI BGN/SIPGN</span>
    </div>

    <!-- Navigation Bar -->
    <header class="bg-white border-b border-[#DCE5DD] sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand & Context -->
                <div class="flex items-center gap-3">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                        <div class="w-9 h-9 rounded-lg bg-[#1C6654] flex items-center justify-center text-white font-semibold text-lg shadow-sm group-hover:bg-[#164D42] transition-colors">
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
                        <span class="hidden sm:inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#EDF2ED] text-[#1C6654] border border-[#DCE5DD]">
                            {{ $roleEnum?->label() ?? 'Internal' }}
                        </span>
                    @endauth
                </div>

                <!-- User & Action Profile -->
                <div class="flex items-center gap-3">
                    @auth
                        <div class="hidden md:flex flex-col text-right">
                            <span class="text-xs font-semibold text-[#172923] leading-snug">{{ auth()->user()->name }}</span>
                            <span class="text-[11px] text-[#53645B] leading-tight">{{ auth()->user()->email }}</span>
                        </div>

                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium text-[#9B3232] bg-[#FDF2F2] hover:bg-[#FBE8E8] border border-[#F5C2C2] rounded-lg transition-colors cursor-pointer min-h-[36px]"
                                    title="Keluar dari sesi ini">
                                <span>Keluar</span>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" 
                           class="inline-flex items-center justify-center px-3.5 py-2 text-xs font-semibold text-white bg-[#1C6654] hover:bg-[#164D42] rounded-lg shadow-xs transition-colors">
                            Masuk
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 mb-12 sm:mb-6">
        @if (session('status'))
            <div class="mb-6 p-4 rounded-xl bg-[#EDF7F2] border border-[#BCE2D0] text-[#1B573E] text-sm flex items-start gap-3">
                <svg class="w-5 h-5 mt-0.5 shrink-0 text-[#21684B]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <div class="font-medium">{{ session('status') }}</div>
            </div>
        @endif

        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-[#DCE5DD] py-4 text-center text-xs text-[#53645B]">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <div>
                &copy; {{ date('Y') }} MBG-Track Prototype — Sprint 0 Technical Foundation
            </div>
            <div class="flex items-center gap-4 text-[11px]">
                <span>Versi Arsitektur 2.0</span>
                <span>•</span>
                <span>WCAG 2.2 AA Compliant</span>
                <span>•</span>
                <span>Mobile-First 390px</span>
            </div>
        </div>
    </footer>

</body>
</html>
