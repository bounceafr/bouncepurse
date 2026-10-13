<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Game;

use App\Concerns\NormalizesTimestamps;
use App\Enums\GameParticipant;
use App\Models\Game;
use Closure;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateGameRequest extends FormRequest
{
    use NormalizesTimestamps;

    public function authorize(): bool
    {
        /** @var Game $game */
        $game = $this->route('game');

        return $this->user()?->can('update', $game) === true;
    }

    /**
     * @return array<string, array<int, Rule|array<mixed>|string|Closure>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'format' => ['required', 'string'],
            'court_id' => ['nullable', 'exists:courts,id'],
            'played_at' => ['required', $this->timestampRule(), 'before_or_equal:now'],
            'points' => ['nullable', 'integer'],
            'comments' => ['nullable', 'string', 'max:500'],
            ...$this->opponentRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'played_at.date_format' => 'The played date must include a timezone offset.',
        ];
    }

    /** @return list<string> */
    protected function timestampFields(): array
    {
        return ['played_at'];
    }

    /**
     * @return array<string, array<int, string|Closure>>
     */
    private function opponentRules(): array
    {
        /** @var Game $game */
        $game = $this->route('game');

        if ($game->participant === GameParticipant::TEAM) {
            return [
                'opponent_team_id' => ['sometimes', 'nullable', 'integer', 'exists:teams,id', function (string $attribute, mixed $value, Closure $fail) use ($game): void {
                    if ($this->integer($attribute) === $game->team_id) {
                        $fail('A team cannot play against itself.');
                    }
                }],
                'opponent_player_id' => ['prohibited'],
            ];
        }

        return [
            'opponent_player_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id', function (string $attribute, mixed $value, Closure $fail) use ($game): void {
                if ($this->integer($attribute) === $game->player_id) {
                    $fail('A player cannot play against themselves.');
                }
            }],
            'opponent_team_id' => ['prohibited'],
        ];
    }
}
