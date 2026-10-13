<?php

namespace App\Http\Requests;

use App\Enums\TripType;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active && $this->user()?->role === UserRole::KEPALA_SPPG;
    }

    public function rules(): array
    {
        return [
            'service_date' => ['required', 'date'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'driver_id' => ['required', 'integer', 'exists:users,id'],
            'trip_type' => ['nullable', 'string', Rule::in(['DELIVERY', 'PICKUP', 'MIXED'])],
            'planned_departure_at' => ['nullable', 'date'],
            'stops' => ['required', 'array', 'min:1'],
            'stops.*.school_id' => ['required', 'integer', 'exists:schools,id'],
            'stops.*.sequence_number' => ['required', 'integer', 'min:1'],
            'stops.*.stop_type' => ['nullable', 'string', Rule::in(['DELIVERY', 'PICKUP'])],
            'stops.*.planned_arrival_at' => ['nullable', 'date'],
            'stops.*.items' => ['required', 'array', 'min:1'],
            'stops.*.items.*.delivery_item_id' => ['required', 'integer', 'exists:delivery_items,id'],
            'stops.*.items.*.planned_portions' => ['nullable', 'integer', 'min:1'],
            'stops.*.items.*.loaded_portions' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'service_date.required' => 'Tanggal layanan wajib diisi.',
            'vehicle_id.required' => 'Armada kendaraan wajib dipilih.',
            'driver_id.required' => 'Kurir pengantaran wajib ditugaskan.',
            'stops.required' => 'Minimal satu sekolah tujuan wajib disertakan dalam rute.',
            'stops.*.school_id.required' => 'Sekolah tujuan pada setiap perhentian wajib valid.',
            'stops.*.items.required' => 'Alokasi makanan wajib ditentukan untuk setiap sekolah tujuan.',
            'stops.*.items.*.delivery_item_id.required' => 'Item alokasi makanan wajib valid.',
            'stops.*.items.*.loaded_portions.min' => 'Jumlah porsi muatan harus minimal 1 porsi.',
        ];
    }
}
