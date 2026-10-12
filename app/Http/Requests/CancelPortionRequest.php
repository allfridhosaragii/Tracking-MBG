<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class CancelPortionRequest extends FormRequest
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
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /**
     * Custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan pembatalan layanan sekolah wajib dicantumkan.',
            'reason.min' => 'Alasan pembatalan minimal 5 karakter.',
        ];
    }
}
