<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreMenuItemRequest extends FormRequest
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
            'item_name' => ['required', 'string', 'max:180'],
            'serving_weight_g' => ['required', 'numeric', 'gt:0'],
            'energy_kcal' => ['nullable', 'numeric', 'min:0'],
            'protein_g' => ['nullable', 'numeric', 'min:0'],
            'carbohydrate_g' => ['nullable', 'numeric', 'min:0'],
            'fat_g' => ['nullable', 'numeric', 'min:0'],
            'fiber_g' => ['nullable', 'numeric', 'min:0'],
            'nutrition_source' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'item_name.required' => 'Nama komponen bahan/makanan wajib diisi.',
            'serving_weight_g.required' => 'Berat porsi saji wajib diisi.',
            'serving_weight_g.gt' => 'Berat porsi saji harus lebih besar dari 0 gram.',
            'energy_kcal.min' => 'Nilai energi (kkal) tidak boleh bernilai negatif.',
            'protein_g.min' => 'Kandungan protein tidak boleh bernilai negatif.',
            'carbohydrate_g.min' => 'Kandungan karbohidrat tidak boleh bernilai negatif.',
            'fat_g.min' => 'Kandungan lemak tidak boleh bernilai negatif.',
            'fiber_g.min' => 'Kandungan serat tidak boleh bernilai negatif.',
        ];
    }
}
