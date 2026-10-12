<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreMenuRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->is_active && $user->hasRole(UserRole::PENGAWAS_GIZI);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'menu_code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9\-_]+$/'],
            'name' => ['required', 'string', 'max:180'],
            'effective_from' => ['nullable', 'date'],
            'image_path' => ['nullable', 'string', 'max:400'],
        ];
    }

    /**
     * Custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'menu_code.required' => 'Kode menu wajib diisi.',
            'menu_code.regex' => 'Kode menu hanya boleh berisi huruf, angka, tanda hubung (-), dan garis bawah (_).',
            'name.required' => 'Nama racikan menu makanan wajib diisi.',
            'effective_from.date' => 'Format tanggal mulai berlaku tidak valid.',
        ];
    }
}
