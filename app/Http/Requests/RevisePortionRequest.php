<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class RevisePortionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->is_active && ($user->hasRole(UserRole::PIC_SEKOLAH) || $user->hasRole(UserRole::KEPALA_SPPG));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'requested_portions' => ['required', 'integer', 'min:0'],
            'present_count' => ['nullable', 'integer', 'min:0'],
            'revision_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /**
     * Custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'requested_portions.required' => 'Jumlah porsi revisi wajib diisi.',
            'requested_portions.min' => 'Jumlah porsi revisi tidak boleh bernilai negatif.',
            'revision_reason.required' => 'Alasan revisi wajib dicantumkan untuk keperluan audit operasional.',
            'revision_reason.min' => 'Alasan revisi minimal 5 karakter agar informatif dan jelas.',
        ];
    }
}
