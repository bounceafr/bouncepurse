<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Game\StoreDisputeAction;
use App\Http\Requests\StoreDisputeRequest;
use App\Models\Game;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class DisputeController extends Controller
{
    public function store(StoreDisputeRequest $request, StoreDisputeAction $action, Game $game): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var string $reason */
        $reason = $request->validated('reason');

        $action->handle($game, $user, $reason);

        return to_route('admin.games.show', $game->uuid)->with('success', 'Dispute submitted successfully.');
    }
}
