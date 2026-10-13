<?php

namespace App\Http\Requests;

use App\Enums\ProductionStage;
use App\Models\FoodBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdvanceBatchStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $batch = $this->route('batch');
        if (! $batch instanceof FoodBatch) {
            $batch = FoodBatch::findOrFail($batch);
        }

        return $this->user()->can('advanceStage', $batch);
    }

    public function rules(): array
    {
        return [
            'stage' => ['required', Rule::enum(ProductionStage::class)],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'stage.required' => 'Tahapan target produksi wajib dipilih.',
        ];
    }
}
