<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Game\SubmitGameResultAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Game\SubmitResultRequest;
use App\Models\Game;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class GameResultController extends Controller
{
    public function store(SubmitResultRequest $request, SubmitGameResultAction $action, Game $game): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array{your_score: int, opponent_score: int, started_at: string, finished_at: string} $data */
        $data = $request->validated();
        $action->handle($game, $user, $data);

        return to_route('admin.games.show', $game)->with('success', 'Result submitted successfully.');
    }
}
