@props([
    'role' => null,
])

@php
    $roleName = $role instanceof \App\Enums\UserRole ? $role->value : (string) $role;
    $label = $role instanceof \App\Enums\UserRole ? $role->label() : $roleName;

    $colors = match ($roleName) {
        'KEPALA_SPPG' => 'bg-[#EDF5F2] text-[#1C6654] border-[#BCE2D0]',
        'PENGAWAS_GIZI' => 'bg-[#F0F5FA] text-[#285F80] border-[#BCD4E6]',
        'ASISTEN_LAPANGAN' => 'bg-[#FAF4EB] text-[#885D1C] border-[#E8D7BE]',
        'KURIR' => 'bg-[#F4F0F7] text-[#55327A] border-[#D8C7EA]',
        'PIC_SEKOLAH' => 'bg-[#EDF7F2] text-[#21684B] border-[#BCE2D0]',
        default => 'bg-[#EDF2ED] text-[#53645B] border-[#DCE5DD]',
    };

    $dotColor = match ($roleName) {
        'KEPALA_SPPG' => 'bg-[#1C6654]',
        'PENGAWAS_GIZI' => 'bg-[#285F80]',
        'ASISTEN_LAPANGAN' => 'bg-[#885D1C]',
        'KURIR' => 'bg-[#55327A]',
        'PIC_SEKOLAH' => 'bg-[#21684B]',
        default => 'bg-[#53645B]',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border $colors tracking-wide"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }} shrink-0" aria-hidden="true"></span>
    <span>{{ $label }}</span>
</span>
