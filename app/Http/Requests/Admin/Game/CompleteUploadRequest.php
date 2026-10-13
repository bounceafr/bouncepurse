<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Game;

use App\Models\Game;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

final class CompleteUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Game $game */
        $game = $this->route('game');

        return $this->user()?->can('uploadVideo', $game) === true;
    }

    /**
     * @return array<string, array<int, Rule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'vimeo_uri' => ['required', 'string'],
        ];
    }
}
