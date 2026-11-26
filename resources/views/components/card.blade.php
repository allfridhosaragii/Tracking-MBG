@props([
    'title' => null,
    'subtitle' => null,
    'badge' => null,
    'compact' => false,
])

<section {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-[#DCE5DD] ' . ($compact ? 'p-4 sm:p-5' : 'p-4 sm:p-6') . ' subtle-shadow']) }}>
    @if ($title || $subtitle || isset($headerAction))
        <div class="mb-4 pb-3.5 border-b border-[#E7ECE8] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($title)
                        <h2 class="section-title">{{ $title }}</h2>
                    @endif
                    @if ($badge)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-[#EDF7F2] text-[#1C6654] border border-[#BCE2D0]">
                            {{ $badge }}
                        </span>
                    @endif
                </div>
                @if ($subtitle)
                    <p class="text-xs text-[#53645B] mt-0.5 leading-relaxed">{{ $subtitle }}</p>
                @endif
            </div>
            @if (isset($headerAction))
                <div class="shrink-0 flex flex-wrap items-center gap-2">
                    {{ $headerAction }}
                </div>
            @endif
        </div>
    @endif

    {{ $slot }}
</section>
