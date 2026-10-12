@props([
    'title' => null,
    'subtitle' => null,
    'badge' => null,
])

<section {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-[#DCE5DD] p-4 sm:p-6 subtle-shadow']) }}>
    @if ($title || $subtitle || isset($headerAction))
        <div class="mb-4 pb-3 border-b border-[#EDF2ED] flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    @if ($title)
                        <h2 class="text-sm sm:text-base font-bold text-[#172923] tracking-tight leading-snug truncate">{{ $title }}</h2>
                    @endif
                    @if ($badge)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-[#EDF2ED] text-[#1C6654] border border-[#DCE5DD]">
                            {{ $badge }}
                        </span>
                    @endif
                </div>
                @if ($subtitle)
                    <p class="text-xs text-[#53645B] mt-0.5 leading-relaxed">{{ $subtitle }}</p>
                @endif
            </div>
            @if (isset($headerAction))
                <div class="shrink-0">
                    {{ $headerAction }}
                </div>
            @endif
        </div>
    @endif

    {{ $slot }}
</section>
