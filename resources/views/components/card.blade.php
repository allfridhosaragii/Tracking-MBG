@props([
    'title' => null,
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-[#DCE5DD] p-5 sm:p-6 subtle-shadow']) }}>
    @if ($title || $subtitle)
        <div class="mb-4 pb-3 border-b border-[#EDF2ED] flex items-center justify-between gap-4">
            <div>
                @if ($title)
                    <h2 class="text-base font-bold text-[#172923] tracking-tight leading-snug">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="text-xs text-[#53645B] mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
            @if (isset($headerAction))
                <div>
                    {{ $headerAction }}
                </div>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
