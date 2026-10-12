<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolRequest extends FormRequest
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
            'school_code' => ['required', 'string', 'max:40', 'unique:schools,school_code'],
            'name' => ['required', 'string', 'max:180'],
            'education_level' => ['required', 'string', 'in:SD,SMP,SMA,SMK,MI,MTs,MA,PAUD,Lainnya'],
            'address' => ['required', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'registered_beneficiaries' => ['nullable', 'integer', 'min:0'],
            'public_slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:schools,public_slug'],
            'pic_user_id' => ['nullable', 'exists:users,id'],
        ];
    }

    /**
     * Custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'school_code.unique' => 'Kode sekolah (NPSN) sudah terdaftar dalam sistem.',
            'public_slug.unique' => 'Slug publik sekolah sudah digunakan oleh sekolah lain.',
            'public_slug.regex' => 'Format slug publik harus berupa huruf kecil dan tanda hubung (contoh: sdn-01-surabaya).',
            'latitude.between' => 'Titik koordinat lintang (latitude) harus berada dalam rentang -90 hingga 90 derajat.',
            'longitude.between' => 'Titik koordinat bujur (longitude) harus berada dalam rentang -180 hingga 180 derajat.',
            'registered_beneficiaries.min' => 'Jumlah siswa penerima terdaftar tidak boleh bernilai negatif.',
        ];
    }
}
