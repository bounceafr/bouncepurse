<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Game;

use App\Concerns\NormalizesTimestamps;
use App\Models\Game;
use Illuminate\Foundation\Http\FormRequest;

final class SubmitResultRequest extends FormRequest
{
    use NormalizesTimestamps;

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
            'started_at' => ['required', $this->timestampRule(), 'before_or_equal:now'],
            'finished_at' => ['required', $this->timestampRule(), 'after:started_at', 'before_or_equal:now'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'opponent_score.different' => 'A game cannot end in a tie.',
            'started_at.date_format' => 'The start time must include a timezone offset.',
            'finished_at.date_format' => 'The finish time must include a timezone offset.',
        ];
    }

    /** @return list<string> */
    protected function timestampFields(): array
    {
        return ['started_at', 'finished_at'];
    }
}
