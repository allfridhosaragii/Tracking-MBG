<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmPortionRequest extends FormRequest
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
            'service_date' => ['sometimes', 'date_format:Y-m-d'],
            'requested_portions' => ['required', 'integer', 'min:0'],
            'present_count' => ['nullable', 'integer', 'min:0'],
            'revision_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'requested_portions.required' => 'Kebutuhan porsi yang dikonfirmasi wajib diisi.',
            'requested_portions.integer' => 'Kebutuhan porsi harus berupa bilangan bulat.',
            'requested_portions.min' => 'Kebutuhan porsi tidak boleh bernilai negatif.',
            'present_count.min' => 'Jumlah siswa hadir tidak boleh bernilai negatif.',
        ];
    }
}
