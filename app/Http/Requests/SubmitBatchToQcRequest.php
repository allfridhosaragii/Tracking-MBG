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
            'photo' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120', // max 5MB
                'required_without:has_evidence_exception',
            ],
            'has_evidence_exception' => ['nullable', 'boolean'],
            'evidence_exception_reason' => [
                'nullable',
                'required_if:has_evidence_exception,1,true',
                'string',
                'min:15',
                'max:1000',
            ],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required_without' => 'Foto bukti kesiapan makanan (FOOD_READY) wajib diunggah pada akhir pengemasan, atau centang prosedur pengecualian jika terdapat kendala fisik.',
            'photo.image' => 'Berkas bukti harus berupa gambar asli (JPEG, PNG, WEBP).',
            'photo.mimes' => 'Format berkas foto hanya diizinkan JPEG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran berkas foto maksimal 5 MB.',
            'evidence_exception_reason.required_if' => 'Alasan pengecualian bukti foto wajib diisi minimal 15 karakter untuk audit operasional.',
            'evidence_exception_reason.min' => 'Alasan pengecualian bukti foto harus memuat penjelasan operasional minimal 15 karakter.',
        ];
    }
}
