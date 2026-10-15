<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Game;

use App\Models\Game;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

final class UploadGameRequest extends FormRequest
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
            'file_size' => ['required', 'integer', 'min:1'],
            'file_name' => ['required', 'string'],
            // The browser could not finish uploading to this server; send the video to Vimeo instead.
            'failover' => ['sometimes', 'boolean'],
        ];
    }
}
