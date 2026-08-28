<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GuardianRelationship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rules\Enum;

final class CompleteProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'date_of_birth' => ['required', 'date', 'before:today'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'city' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:50'],
            'bio' => ['required', 'string'],
            'position' => ['required', 'string', 'max:100'],
            'profile_image' => ['nullable', 'image', 'max:2048'],
        ];

        if ($this->isMinorDateOfBirth()) {
            $rules['guardian.full_name'] = ['required', 'string', 'max:255'];
            $rules['guardian.email'] = ['required', 'email', 'max:255'];
            $rules['guardian.phone'] = ['required', 'string', 'max:50'];
            $rules['guardian.address'] = ['required', 'string', 'max:500'];
            $rules['guardian.relationship'] = ['required', new Enum(GuardianRelationship::class)];
        }

        return $rules;
    }

    public function isMinorDateOfBirth(): bool
    {
        $dob = $this->input('date_of_birth');

        if (! $dob) {
            return false;
        }

        /** @var string $dob */
        return Date::parse($dob)->age < 18;
    }
}
