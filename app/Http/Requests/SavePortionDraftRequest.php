<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class SavePortionDraftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->is_active && $user->hasRole(UserRole::PIC_SEKOLAH) && $user->school_id !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'service_date' => ['required', 'date', 'date_format:Y-m-d'],
            'present_count' => ['nullable', 'integer', 'min:0'],
            'requested_portions' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'service_date.required' => 'Tanggal layanan wajib ditentukan.',
            'service_date.date_format' => 'Format tanggal layanan harus berupa YYYY-MM-DD.',
            'present_count.min' => 'Jumlah siswa hadir tidak boleh bernilai negatif.',
            'requested_portions.min' => 'Jumlah porsi yang diminta tidak boleh bernilai negatif.',
        ];
    }
}
