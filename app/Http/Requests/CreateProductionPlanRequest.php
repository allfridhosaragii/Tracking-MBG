<?php

namespace App\Http\Requests;

use App\Models\ProductionPlan;
use Illuminate\Foundation\Http\FormRequest;

class CreateProductionPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', ProductionPlan::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'service_date' => ['required', 'date_format:Y-m-d'],
            'menu_id' => ['required', 'integer', 'exists:menus,id'],
            'planned_start_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'service_date.required' => 'Tanggal layanan wajib diisi.',
            'service_date.date_format' => 'Format tanggal layanan tidak valid (YYYY-MM-DD).',
            'menu_id.required' => 'Menu hidangan wajib dipilih.',
            'menu_id.exists' => 'Menu yang dipilih tidak terdaftar dalam basis data.',
        ];
    }
}
