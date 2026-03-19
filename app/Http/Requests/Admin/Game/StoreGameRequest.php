<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Game;

use App\Enums\GameFormat;
use App\Enums\GameParticipant;
use App\Enums\ResultStatus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class StoreGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'participant' => ['nullable', new Enum(GameParticipant::class)],
            'format' => ['required', 'string', new Enum(GameFormat::class)],
            'court_id' => ['nullable', 'exists:courts,id'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'played_at' => ['required_without:scheduled_at', 'nullable', 'date'],
            'team_id' => ['nullable', 'required_if:participant,team', 'exists:teams,id'],
            'result' => ['nullable', Rule::enum(ResultStatus::class)],
            'comments' => ['nullable', 'string', 'max:500'],
            'points' => ['nullable', 'integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
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
}
