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
            'verification_mode' => ['nullable', 'string', 'in:PER_BATCH,AGGREGATE,SINGLE_BATCH,per_batch,aggregate,single_batch'],
            'items' => ['nullable', 'array'],
            'items.*.received_portions' => ['required_with:items', 'integer', 'min:0'],
            'items.*.damaged_portions' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $stop = $this->route('stop');
            if (!$stop instanceof TripStop) {
                $stop = TripStop::with('deliveryItems')->find($stop);
            }

            if ($stop) {
                $loadedPortions = (int) ($stop->deliveryItems->isNotEmpty()
                    ? $stop->deliveryItems->sum('loaded_portions')
                    : $stop->loaded_portions);

                if ($loadedPortions <= 0) {
                    $validator->errors()->add(
                        'loaded_portions',
                        'Muatan fisik stop ini bernilai 0 atau belum diverifikasi saat pemuatan. Tidak dapat membuat tanda terima tanpa manifest fisik sah.'
                    );
                }

                $received = filter_var($this->input('received_portions'), FILTER_VALIDATE_INT);
                $damaged = filter_var($this->input('damaged_portions', 0), FILTER_VALIDATE_INT) ?: 0;
                $notes = trim((string) $this->input('notes', ''));

                if ($received !== false && $loadedPortions > 0) {
                    $hasDiscrepancy = ($received !== $loadedPortions) || ($damaged > 0);
                    if ($hasDiscrepancy && empty($notes)) {
                        $validator->errors()->add(
                            'notes',
                            'Catatan keterangan wajib diisi jika terdapat selisih jumlah porsi (kurang/lebih) atau porsi makanan rusak.'
                        );
                    }
                }

                // GATE 1 & 4: Per-item multi-batch validation
                $verificationMode = strtoupper((string) $this->input('verification_mode', ''));
                $itemsInput = $this->input('items');

                if ($verificationMode === 'AGGREGATE') {
                    if (!empty($itemsInput)) {
                        $validator->errors()->add('items', 'Mode verifikasi total fisik gabungan (Mode B) tidak boleh menyertakan input rincian per batch.');
                    }
                    $isPerBatch = false;
                } else {
                    $isPerBatch = ($verificationMode === 'PER_BATCH') || (!empty($itemsInput) && is_array($itemsInput));
                }

                if ($isPerBatch) {
                    if (empty($itemsInput) || !is_array($itemsInput)) {
                        $validator->errors()->add('items', 'Rincian fisik per-batch wajib dikirimkan secara lengkap dalam mode verifikasi per batch (Mode A).');
                    } else {
                        $validItemIds = $stop->deliveryItems->pluck('id')->all();
                        $submittedItemIds = array_map('intval', array_keys($itemsInput));

                        // 1. All stop delivery items must be present (no partial items allowed!)
                        $missingItemIds = array_diff($validItemIds, $submittedItemIds);
                        if (!empty($missingItemIds)) {
                            $validator->errors()->add(
                                'items',
                                'Seluruh item batch pengiriman wajib diverifikasi secara lengkap. Item berikut belum memiliki data verifikasi: #' . implode(', #', $missingItemIds) . '.'
                            );
                        }

                        // 2. Reject foreign item IDs
                        $foreignItemIds = array_diff($submittedItemIds, $validItemIds);
                        if (!empty($foreignItemIds)) {
                            foreach ($foreignItemIds as $fId) {
                                $validator->errors()->add("items.{$fId}", "Item pengiriman #{$fId} tidak terdaftar pada pemberhentian sekolah ini.");
                            }
                        }

                        $itemsReceivedSum = 0;
                        $itemsDamagedSum = 0;
                        $hasPerBatchDiscrepancy = false;

                        foreach ($itemsInput as $itemId => $itemRow) {
                            if (!in_array((int) $itemId, $validItemIds, true)) {
                                continue;
                            }

                            if (!is_array($itemRow)) {
                                $validator->errors()->add("items.{$itemId}", "Data item #{$itemId} harus berupa array.");
                                continue;
                            }

                            if (!isset($itemRow['received_portions']) || $itemRow['received_portions'] === null || $itemRow['received_portions'] === '') {
                                $validator->errors()->add("items.{$itemId}.received_portions", "Kuantitas diterima fisik item #{$itemId} wajib diisi eksplisit.");
                                $iRec = false;
                            } else {
                                $iRec = filter_var($itemRow['received_portions'], FILTER_VALIDATE_INT);
                                if ($iRec === false || $iRec < 0) {
                                    $validator->errors()->add("items.{$itemId}.received_portions", "Kuantitas diterima fisik item #{$itemId} harus berupa bilangan bulat >= 0.");
                                }
                            }

                            if (!isset($itemRow['damaged_portions']) || $itemRow['damaged_portions'] === null || $itemRow['damaged_portions'] === '') {
                                $validator->errors()->add("items.{$itemId}.damaged_portions", "Kuantitas rusak fisik item #{$itemId} wajib diisi eksplisit (0 jika tidak ada kerusakan).");
                                $iDmg = false;
                            } else {
                                $iDmg = filter_var($itemRow['damaged_portions'], FILTER_VALIDATE_INT);
                                if ($iDmg === false || $iDmg < 0) {
                                    $validator->errors()->add("items.{$itemId}.damaged_portions", "Kuantitas rusak item #{$itemId} harus berupa bilangan bulat >= 0.");
                                } elseif ($iRec !== false && $iDmg > $iRec) {
                                    $validator->errors()->add("items.{$itemId}.damaged_portions", "Porsi rusak item #{$itemId} ({$iDmg}) tidak boleh melebihi porsi fisik yang diterima ({$iRec}).");
                                }
                            }

                            if ($iRec !== false) {
                                $itemsReceivedSum += $iRec;
                            }
                            if ($iDmg !== false) {
                                $itemsDamagedSum += $iDmg;
                            }

                            // Gate 3: Track per-batch discrepancies (overage, shortage, damage)
                            $deliveryItem = $stop->deliveryItems->firstWhere('id', (int) $itemId);
                            if ($deliveryItem && $iRec !== false) {
                                $loaded = (int) ($deliveryItem->loaded_portions ?? 0);
                                if ($iRec !== $loaded || ($iDmg !== false && $iDmg > 0)) {
                                    $hasPerBatchDiscrepancy = true;
                                }
                            }
                        }

                        if ($received !== false && $itemsReceivedSum !== $received) {
                            $validator->errors()->add(
                                'received_portions',
                                "Total porsi per-batch ({$itemsReceivedSum}) harus sama dengan total fisik diterima ({$received})."
                            );
                        }

                        if ($itemsDamagedSum !== $damaged) {
                            $validator->errors()->add(
                                'damaged_portions',
                                "Total porsi rusak per-batch ({$itemsDamagedSum}) harus sama dengan total rusak ({$damaged})."
                            );
                        }

                        if ($hasPerBatchDiscrepancy && empty($notes)) {
                            $validator->errors()->add(
                                'notes',
                                'Catatan keterangan wajib diisi jika terdapat selisih jumlah porsi (kurang/lebih) atau porsi makanan rusak pada salah satu batch.'
                            );
                        }
                    }
                }
            }


            // Food Safety: conclusion=SAFE CANNOT ignore negative sensory checks
            $appearanceOk = filter_var($this->input('appearance_ok'), FILTER_VALIDATE_BOOLEAN);
            $aromaOk = filter_var($this->input('aroma_ok'), FILTER_VALIDATE_BOOLEAN);
            $textureOk = filter_var($this->input('texture_ok'), FILTER_VALIDATE_BOOLEAN);
            $tasteTested = filter_var($this->input('taste_tested'), FILTER_VALIDATE_BOOLEAN);
            $tasteOk = $this->has('taste_ok') ? filter_var($this->input('taste_ok'), FILTER_VALIDATE_BOOLEAN) : null;
            $conclusion = strtoupper((string) $this->input('conclusion', 'SAFE'));

            if ($conclusion === 'SAFE') {
                if (!$appearanceOk || !$aromaOk || !$textureOk) {
                    $validator->errors()->add(
                        'conclusion',
                        'Kesimpulan Lolos QC (SAFE) ditolak: Makanan dengan rupa, aroma, atau tekstur tidak wajar tidak dapat dinyatakan aman.'
                    );
                }

                if ($tasteTested && $tasteOk === false) {
                    $validator->errors()->add(
                        'conclusion',
                        'Kesimpulan Lolos QC (SAFE) ditolak: Hasil pencicipan rasa terindikasi tidak layak/gagal.'
                    );
                }
            }
        });
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
