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
        $tripType = $this->input('trip_type', 'DELIVERY');

        $rules = [
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
        ];

        $stops = $this->input('stops', []);
        if (is_array($stops)) {
            foreach ($stops as $idx => $stop) {
                $stopType = $stop['stop_type'] ?? ($tripType === 'PICKUP' ? 'PICKUP' : 'DELIVERY');
                if ($stopType === 'DELIVERY') {
                    $rules["stops.{$idx}.items"] = ['required', 'array', 'min:1'];
                    $rules["stops.{$idx}.items.*.delivery_item_id"] = ['required', 'integer', 'exists:delivery_items,id'];
                    $rules["stops.{$idx}.items.*.planned_portions"] = ['nullable', 'integer', 'min:1'];
                    $rules["stops.{$idx}.items.*.loaded_portions"] = ['nullable', 'integer', 'min:1'];
                } else {
                    $rules["stops.{$idx}.items"] = ['nullable', 'array'];
                    $rules["stops.{$idx}.items.*.delivery_item_id"] = ['nullable', 'integer', 'exists:delivery_items,id'];
                    $rules["stops.{$idx}.items.*.planned_portions"] = ['nullable', 'integer'];
                    $rules["stops.{$idx}.items.*.loaded_portions"] = ['nullable', 'integer'];
                }
            }
        } else {
            $rules['stops.*.items'] = ['nullable', 'array'];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tripType = $this->input('trip_type', 'DELIVERY');
            $stops = $this->input('stops', []);

            if (!is_array($stops)) {
                return;
            }

            if ($tripType === 'PICKUP') {
                foreach ($stops as $idx => $stop) {
                    if (isset($stop['stop_type']) && $stop['stop_type'] === 'DELIVERY') {
                        $validator->errors()->add(
                            "stops.{$idx}.stop_type",
                            'Perjalanan penjemputan (PICKUP) tidak boleh memiliki perhentian pengantaran makanan (DELIVERY).'
                        );
                    }
                }
            } elseif ($tripType === 'DELIVERY') {
                foreach ($stops as $idx => $stop) {
                    if (isset($stop['stop_type']) && $stop['stop_type'] === 'PICKUP') {
                        $validator->errors()->add(
                            "stops.{$idx}.stop_type",
                            'Perjalanan pengantaran (DELIVERY) tidak boleh memiliki perhentian penjemputan (PICKUP). Gunakan tipe MIXED.'
                        );
                    }
                }
            } elseif ($tripType === 'MIXED') {
                $hasDelivery = false;
                $hasPickup = false;
                foreach ($stops as $stop) {
                    $type = $stop['stop_type'] ?? 'DELIVERY';
                    if ($type === 'DELIVERY') {
                        $hasDelivery = true;
                    } elseif ($type === 'PICKUP') {
                        $hasPickup = true;
                    }
                }

                if (! $hasDelivery || ! $hasPickup) {
                    $validator->errors()->add(
                        'trip_type',
                        'Perjalanan gabungan (MIXED) harus memiliki minimal satu perhentian pengantaran makanan (DELIVERY) dan satu perhentian penjemputan ompreng (PICKUP).'
                    );
                }
            }
        });
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
