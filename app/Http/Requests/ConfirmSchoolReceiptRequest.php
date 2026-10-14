<?php

namespace App\Http\Requests;

use App\Models\TripStop;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmSchoolReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $stop = $this->route('stop');
        if (!$stop instanceof TripStop) {
            $stop = TripStop::find($stop);
        }

        if (!$stop) {
            return false;
        }

        return $this->user()->can('confirmReceipt', $stop);
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if (!$this->has('appearance_ok') && $this->has('appearance_status')) {
            $merge['appearance_ok'] = $this->input('appearance_status') === 'FRESH';
        }
        if (!$this->has('aroma_ok') && $this->has('aroma_status')) {
            $merge['aroma_ok'] = $this->input('aroma_status') === 'NORMAL';
        }
        if (!$this->has('texture_ok') && $this->has('texture_status')) {
            $merge['texture_ok'] = $this->input('texture_status') === 'NORMAL';
        }
        if (!$this->has('taste_ok') && $this->has('taste_status')) {
            $merge['taste_ok'] = $this->input('taste_status') === 'DELICIOUS_NORMAL';
        }
        if (!$this->has('taste_tested') && $this->has('taste_status')) {
            $merge['taste_tested'] = true;
        }
        if (!$this->has('conclusion') && $this->has('organoleptic_result')) {
            $merge['conclusion'] = in_array($this->input('organoleptic_result'), ['UNSAFE', 'HOLD'], true)
                ? (string) $this->input('organoleptic_result')
                : 'SAFE';
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'received_portions' => ['required', 'integer', 'min:0'],
            'damaged_portions' => ['nullable', 'integer', 'min:0', 'lte:received_portions'],
            'temperature_c' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'appearance_ok' => ['required', 'boolean'],
            'aroma_ok' => ['required', 'boolean'],
            'texture_ok' => ['required', 'boolean'],
            'taste_tested' => ['required', 'boolean'],
            'taste_ok' => ['nullable', 'boolean'],
            'taste_skipped_reason' => ['nullable', 'required_if:taste_tested,false,0', 'string', 'max:500'],
            'conclusion' => ['nullable', 'string', 'in:SAFE,UNSAFE,HOLD,safe,unsafe,hold'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'evidence_photo' => ['nullable', 'file', 'image', 'max:5120'], // Max 5MB
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'received_portions.required' => 'Jumlah porsi makanan yang diterima fisik wajib diisi.',
            'received_portions.integer' => 'Jumlah porsi diterima harus berupa bilangan bulat.',
            'received_portions.min' => 'Jumlah porsi diterima tidak boleh negatif.',
            'damaged_portions.integer' => 'Jumlah porsi rusak harus berupa bilangan bulat.',
            'damaged_portions.min' => 'Jumlah porsi rusak tidak boleh negatif.',
            'damaged_portions.lte' => 'Jumlah porsi rusak tidak boleh melebihi porsi yang diterima fisik.',
            'appearance_ok.required' => 'Hasil pemeriksaan visual/rupa makanan wajib ditentukan.',
            'aroma_ok.required' => 'Hasil pemeriksaan aroma/bau makanan wajib ditentukan.',
            'texture_ok.required' => 'Hasil pemeriksaan tekstur/konsistensi makanan wajib ditentukan.',
            'taste_tested.required' => 'Status pencicipan rasa wajib ditentukan.',
            'taste_skipped_reason.required_if' => 'Alasan wajib diisi jika uji rasa dilewati demi keselamatan pangan.',
            'evidence_photo.image' => 'Bukti foto serah terima harus berformat gambar.',
            'evidence_photo.max' => 'Ukuran berkas foto tidak boleh melebihi 5 MB.',
        ];
    }
}
