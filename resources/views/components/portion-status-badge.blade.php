@props(['status'])

@php
    $label = is_string($status) ? ucfirst($status) : $status->label();
    $badgeClasses = is_string($status) ? 'bg-[#FEF7EC] text-[#885D1C] border-[#F7D7A3]' : $status->badgeClasses();
    $dotClasses = is_string($status) ? 'bg-[#885D1C]' : $status->dotClasses();
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold border ' . $badgeClasses]) }}>
    <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $dotClasses }}" aria-hidden="true"></span>
    <span>{{ $label }}</span>
</span>
