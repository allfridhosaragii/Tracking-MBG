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
            'taste_tested' => ['required', 'boolean'],
            'taste_ok' => ['nullable', 'required_if:taste_tested,1,true', 'boolean'],
            'taste_skipped_reason' => ['nullable', 'required_if:taste_tested,0,false', 'string', 'min:10', 'max:500'],
            'temperature_c' => ['nullable', 'numeric', 'min:0', 'max:150'],
            'accepted_portions' => ['nullable', 'required_if:conclusion,' . QualityCheckConclusion::SAFE->value, 'integer', 'min:0'],
            'sop_reference' => ['nullable', 'string', 'max:100'],
            'decision_reason' => ['required', 'string', 'min:10', 'max:1000'],
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
            'taste_tested.required' => 'Status pelaksanaan uji rasa organoleptik wajib ditentukan.',
            'taste_ok.required_if' => 'Hasil uji rasa wajib ditentukan bila uji rasa dilaksanakan.',
            'taste_skipped_reason.required_if' => 'Alasan tidak mencicipi wajib diisi jika makanan dicurigai terkontaminasi atau rusak.',
            'decision_reason.required' => 'Penjelasan/alasan keputusan profesional Pengawas Gizi wajib dicatat.',
            'decision_reason.min' => 'Penjelasan keputusan Pengawas Gizi minimal 10 karakter.',
            'accepted_portions.required_if' => 'Jumlah porsi yang dinyatakan lolos (accepted) wajib diisi secara eksplisit saat keputusan mutu SAFE.',
            'conclusion.required' => 'Keputusan kesimpulan mutu wajib ditentukan (SAFE, HOLD, atau UNSAFE).',
        ];
    }
}
