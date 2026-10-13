<?php

namespace App\Http\Requests;

use App\Models\ProductionPlan;
use Illuminate\Foundation\Http\FormRequest;

class CreateFoodBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $plan = $this->route('plan');
        if (! $plan instanceof ProductionPlan) {
            $plan = ProductionPlan::findOrFail($plan);
        }

        return $this->user()->can('create', [ProductionPlan::class, $plan])
            || $this->user()->can('create', [\App\Models\FoodBatch::class, $plan]);
    }

    public function rules(): array
    {
        return [
            'planned_portions' => ['required', 'integer', 'min:1'],
            'portion_category' => ['nullable', 'string', 'max:35'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'planned_portions.required' => 'Target porsi batch wajib diisi.',
            'planned_portions.integer' => 'Target porsi harus berupa bilangan bulat.',
            'planned_portions.min' => 'Target porsi batch minimal 1 porsi.',
        ];
    }
}
