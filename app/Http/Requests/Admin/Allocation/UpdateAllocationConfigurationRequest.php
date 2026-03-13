<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Allocation;

use App\Enums\AllocationCategory;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class UpdateAllocationConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, Rule|array<mixed>|string>>
     */
    public function rules(): array
    {
        $rules = [];

        foreach (AllocationCategory::cases() as $category) {
            $rules[$category->percentageColumn()] = ['required', 'numeric', 'min:0', 'max:100'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $keys = array_map(
                fn (AllocationCategory $category): string => $category->percentageColumn(),
                AllocationCategory::cases(),
            );

            $sum = array_sum(array_map(floatval(...), $this->only($keys)));

            if (abs($sum - 100.0) > 0.001) {
                $v->errors()->add('insurance_percentage', 'The percentages must sum to 100.');
            }
        });
    }
}
