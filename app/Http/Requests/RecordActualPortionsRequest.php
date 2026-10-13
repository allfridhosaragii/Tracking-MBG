<?php

namespace App\Http\Requests;

use App\Models\FoodBatch;
use Illuminate\Foundation\Http\FormRequest;

class RecordActualPortionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $batch = $this->route('batch');
        if (! $batch instanceof FoodBatch) {
            $batch = FoodBatch::findOrFail($batch);
        }

        return $this->user()->can('recordActual', $batch);
    }

    public function rules(): array
    {
        return [
            'actual_portions' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'actual_portions.required' => 'Realisasi jumlah porsi aktual wajib diisi.',
            'actual_portions.integer' => 'Realisasi porsi harus berupa bilangan bulat.',
            'actual_portions.min' => 'Realisasi porsi tidak boleh bernilai negatif.',
        ];
    }
}
