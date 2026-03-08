<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Pathway;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdatePathwayConfigurationRequest extends FormRequest
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
        return [
            'min_approved_games' => ['required', 'integer', 'min:1'],
            'max_rank' => ['required', 'integer', 'min:1'],
            'max_conduct_flags' => ['required', 'integer', 'min:0'],
        ];
    }
}
