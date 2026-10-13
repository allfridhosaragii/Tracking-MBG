<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class VerifyLoadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || ! $user->is_active) {
            return false;
        }

        return in_array($user->role, [UserRole::ASISTEN_LAPANGAN, UserRole::KEPALA_SPPG], true);
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.delivery_item_id' => ['required', 'integer', 'exists:delivery_items,id'],
            'items.*.loaded_portions' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Daftar item muatan fisik wajib disertakan.',
            'items.min' => 'Minimal satu item muatan harus diverifikasi.',
            'items.*.delivery_item_id.required' => 'ID item alokasi muatan wajib valid.',
            'items.*.loaded_portions.required' => 'Kuantitas fisik muatan wajib diisi.',
            'items.*.loaded_portions.min' => 'Kuantitas fisik muatan harus minimal 1 porsi.',
        ];
    }
}
