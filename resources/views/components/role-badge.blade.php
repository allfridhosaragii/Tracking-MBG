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
        'KURIR' => 'bg-[#F2EDF7] text-[#5C3280] border-[#DFCBEF]',
        'PIC_SEKOLAH' => 'bg-[#EDF7F2] text-[#21684B] border-[#BCE2D0]',
        default => 'bg-[#EDF2ED] text-[#53645B] border-[#DCE5DD]',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold border $colors tracking-wide"]) }}>
    {{ $label }}
</span>
