<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->is_active && $user->role === UserRole::KEPALA_SPPG;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:180'],
            'email' => ['required', 'string', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => [
                'required',
                'string',
                Rule::in([
                    UserRole::PENGAWAS_GIZI->value,
                    UserRole::ASISTEN_LAPANGAN->value,
                    UserRole::KURIR->value,
                ]),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap pegawai wajib diisi.',
            'email.required' => 'Alamat email pegawai wajib diisi.',
            'email.email' => 'Format email pegawai tidak valid.',
            'email.unique' => 'Alamat email sudah terdaftar dalam sistem.',
            'role.required' => 'Role pegawai wajib dipilih.',
            'role.in' => 'Role yang dipilih tidak sah untuk pegawai internal SPPG.',
            'password.required' => 'Kata sandi awal wajib diisi.',
            'password.min' => 'Kata sandi minimal harus 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ];
    }
}
