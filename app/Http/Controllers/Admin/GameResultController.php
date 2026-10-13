<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Game\ConfirmGameResultAction;
use App\Actions\Admin\Game\RejectGameResultAction;
use App\Actions\Admin\Game\SubmitGameResultAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Game\ConfirmResultRequest;
use App\Http\Requests\Admin\Game\RejectResultRequest;
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

        return to_route('admin.games.show', $game)->with('success', 'Result submitted. It will be final once your opponent confirms it.');
    }

    public function confirm(ConfirmResultRequest $request, ConfirmGameResultAction $action, Game $game): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->handle($game, $user);

        return to_route('admin.games.show', $game)->with('success', 'Result confirmed. It has been sent for moderation.');
    }

    public function reject(RejectResultRequest $request, RejectGameResultAction $action, Game $game): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var string $reason */
        $reason = $request->validated('reason');
        $action->handle($game, $user, $reason);

        return to_route('admin.games.show', $game)->with('success', 'Result rejected. A moderator will review the game.');
    }
}
