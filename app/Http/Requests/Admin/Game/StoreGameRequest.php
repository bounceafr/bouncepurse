<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Game;

use App\Concerns\NormalizesTimestamps;
use App\Enums\GameFormat;
use App\Enums\GameParticipant;
use App\Models\Game;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class StoreGameRequest extends FormRequest
{
    use NormalizesTimestamps;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Game::class) === true;
    }

    /**
     * @return array<string, array<int, Rule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'participant' => ['required', new Enum(GameParticipant::class)],
            'format' => ['required', 'string', new Enum(GameFormat::class)],
            'court_id' => ['nullable', 'exists:courts,id'],
            'scheduled_at' => ['nullable', $this->timestampRule(), 'after:now'],
            'played_at' => ['required_without:scheduled_at', 'nullable', 'date', 'before_or_equal:now'],
            'team_id' => ['nullable', 'required_if:participant,team', 'prohibited_unless:participant,team', 'integer', 'exists:teams,id'],
            'opponent_player_id' => ['nullable', 'required_if:participant,player', 'prohibited_unless:participant,player', 'integer', 'exists:users,id'],
            'opponent_team_id' => ['nullable', 'required_if:participant,team', 'prohibited_unless:participant,team', 'integer', 'exists:teams,id', 'different:team_id'],
            'comments' => ['nullable', 'string', 'max:500'],
            'points' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'scheduled_at.date_format' => 'The scheduled time must include a timezone offset.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('scheduled_at') && ! $this->filled('played_at')) {
                $validator->errors()->add('scheduled_at', 'A scheduled date or played date is required.');
            }

            $this->validateParticipants($validator);

            $participant = $this->input('participant');
            $format = $this->input('format');

            if (! $participant || ! $format) {
                return;
            }

            if ($participant === GameParticipant::PLAYER->value && $format !== GameFormat::ONE_ON_ONE->value) {
                $validator->errors()->add('format', 'Individual players can only play 1v1 format.');
            }

            if ($participant === GameParticipant::TEAM->value && ! in_array($format, [GameFormat::THREE_ON_THREE->value, GameFormat::FIVE_ON_FIVE->value])) {
                $validator->errors()->add('format', 'Team games must be 3v3 or 5v5 format.');
            }
        });
    }

    /** @return list<string> */
    protected function timestampFields(): array
    {
        return ['scheduled_at'];
    }

    private function validateParticipants(Validator $validator): void
    {
        /** @var User $user */
        $user = $this->user();

        if ($this->integer('opponent_player_id') === $user->id) {
            $validator->errors()->add('opponent_player_id', 'You cannot play against yourself.');
        }

        $teamId = $this->integer('team_id');

        if ($teamId !== 0 && Team::query()->find($teamId)?->hasMember($user) !== true) {
            $validator->errors()->add('team_id', 'You can only create games for a team you belong to.');
        }

        $opponentTeamId = $this->integer('opponent_team_id');

        if ($opponentTeamId !== 0 && Team::query()->find($opponentTeamId)?->hasMember($user) === true) {
            $validator->errors()->add('opponent_team_id', 'The opposing team cannot be a team you belong to.');
        }
    }
}
