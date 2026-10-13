<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class VehicleCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active && $this->user()?->role === UserRole::KURIR;
    }

    public function rules(): array
    {
        return [
            'passed' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'passed.required' => 'Hasil verifikasi kelaikan armada wajib ditentukan.',
            'passed.boolean' => 'Hasil verifikasi kelaikan harus bernilai benar atau salah.',
            'notes.max' => 'Catatan pemeriksaan maksimal 500 karakter.',
        ];
    }
}
