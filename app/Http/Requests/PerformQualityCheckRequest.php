<?php

namespace App\Http\Requests;

use App\Enums\QualityCheckConclusion;
use App\Models\FoodBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PerformQualityCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        $batch = $this->route('batch');
        if (! $batch instanceof FoodBatch) {
            $batch = FoodBatch::findOrFail($batch);
        }

        return $this->user()->can('performQc', $batch);
    }

    public function rules(): array
    {
        return [
            'appearance_ok' => ['required', 'boolean'],
            'aroma_ok' => ['required', 'boolean'],
            'texture_ok' => ['required', 'boolean'],
            'taste_ok' => ['required', 'boolean'],
            'temperature_c' => ['nullable', 'numeric', 'min:0', 'max:150'],
            'conclusion' => ['required', Rule::enum(QualityCheckConclusion::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'appearance_ok.required' => 'Penilaian parameter penampilan/warna wajib diisi.',
            'aroma_ok.required' => 'Penilaian parameter aroma wajib diisi.',
            'texture_ok.required' => 'Penilaian parameter tekstur wajib diisi.',
            'taste_ok.required' => 'Penilaian parameter rasa wajib diisi.',
            'conclusion.required' => 'Keputusan kesimpulan mutu wajib ditentukan.',
        ];
    }
}
