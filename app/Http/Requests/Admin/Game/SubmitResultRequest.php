<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Game;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class SubmitResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $user */
        $user = $this->user();

        /** @var Game $game */
        $game = $this->route('game');

        return $game->player_id === $user->id
            || ($game->team_id && $game->team?->hasMember($user));
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'your_score' => ['required', 'integer', 'min:0'],
            'opponent_score' => ['required', 'integer', 'min:0'],
            'started_at' => ['required', 'date'],
            'finished_at' => ['required', 'date', 'after:started_at'],
        ];
    }
}
