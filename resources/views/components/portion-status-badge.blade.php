@props(['status'])

@php
    $label = is_string($status) ? ucfirst($status) : $status->label();
    $badgeClasses = is_string($status) ? 'bg-[#FAF4EB] text-[#885D1C] border-[#E8D7BE]' : $status->badgeClasses();
    $dotClasses = is_string($status) ? 'bg-[#885D1C]' : $status->dotClasses();
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold border ' . $badgeClasses]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $dotClasses }}" aria-hidden="true"></span>
    <span>{{ $label }}</span>
</span>
