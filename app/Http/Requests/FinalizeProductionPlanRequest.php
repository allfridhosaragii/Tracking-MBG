<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinalizeProductionPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $plan = $this->route('productionPlan');
        return $plan ? $this->user()?->can('finalize', $plan) : false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.school_id' => ['required', 'integer', 'exists:schools,id'],
            'allocations.*.portion_request_id' => ['required', 'integer', 'exists:daily_portion_requests,id'],
            'allocations.*.allocated_portions' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'allocations.required' => 'Daftar alokasi porsi sekolah wajib disertakan.',
            'allocations.min' => 'Rencana produksi harus memiliki setidaknya satu alokasi sekolah.',
            'allocations.*.school_id.required' => 'ID sekolah wajib valid.',
            'allocations.*.portion_request_id.required' => 'ID permintaan porsi wajib valid.',
            'allocations.*.allocated_portions.required' => 'Jumlah porsi wajib diisi.',
            'allocations.*.allocated_portions.min' => 'Jumlah porsi dialokasikan harus bernilai minimal 1 porsi.',
        ];
    }
}
