<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Game;

use App\Models\Game;
use Illuminate\Foundation\Http\FormRequest;

final class SubmitResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Game $game */
        $game = $this->route('game');

        return $this->user()?->can('submitResult', $game) === true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'your_score' => ['required', 'integer', 'min:0'],
            'opponent_score' => ['required', 'integer', 'min:0', 'different:your_score'],
            'started_at' => ['required', 'date', 'before_or_equal:now'],
            'finished_at' => ['required', 'date', 'after:started_at', 'before_or_equal:now'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'opponent_score.different' => 'A game cannot end in a tie.',
        ];
    }
}
