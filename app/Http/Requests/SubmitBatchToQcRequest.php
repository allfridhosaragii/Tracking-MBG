<?php

namespace App\Http\Requests;

use App\Models\FoodBatch;
use Illuminate\Foundation\Http\FormRequest;

class SubmitBatchToQcRequest extends FormRequest
{
    public function authorize(): bool
    {
        $batch = $this->route('batch');
        if (! $batch instanceof FoodBatch) {
            $batch = FoodBatch::findOrFail($batch);
        }

        return $this->user()->can('submitQc', $batch);
    }

    public function rules(): array
    {
        return [
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'], // max 5MB
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.image' => 'Berkas bukti harus berupa gambar (JPEG, PNG, WEBP).',
            'photo.max' => 'Ukuran berkas foto maksimal 5 MB.',
        ];
    }
}
