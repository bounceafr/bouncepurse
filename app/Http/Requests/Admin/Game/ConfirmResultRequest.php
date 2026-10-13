<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Game;

use App\Models\Game;
use Illuminate\Foundation\Http\FormRequest;

final class ConfirmResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Game $game */
        $game = $this->route('game');

        return $this->user()?->can('respondToResult', $game) === true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
