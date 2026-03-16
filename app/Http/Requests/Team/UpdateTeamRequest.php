<?php

declare(strict_types=1);

namespace App\Http\Requests\Team;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->id === $this->user()->ownedTeam?->user_id;
    }

    /** @return array<string, array<int, ValidationRule|array<mixed>|string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'url', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
        ];
    }
}
