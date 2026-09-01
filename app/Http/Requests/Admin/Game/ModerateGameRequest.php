<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Game;

use App\Enums\GameStatus;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class ModerateGameRequest extends FormRequest
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
            'status' => ['required', new Enum(GameStatus::class)],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
