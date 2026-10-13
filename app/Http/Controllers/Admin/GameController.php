<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Game\CompleteUploadAction;
use App\Actions\Admin\Game\DeleteAction;
use App\Actions\Admin\Game\InitiateVimeoUploadAction;
use App\Actions\Admin\Game\ListAction;
use App\Actions\Admin\Game\StoreAction;
use App\Actions\Admin\Game\UpdateAction;
use App\Enums\GameStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Game\CompleteUploadRequest;
use App\Http\Requests\Admin\Game\DeleteGameRequest;
use App\Http\Requests\Admin\Game\StoreGameRequest;
use App\Http\Requests\Admin\Game\UpdateGameRequest;
use App\Http\Requests\Admin\Game\UploadGameRequest;
use App\Models\Court;
use App\Models\Game;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class GameController extends Controller
{
    public function index(Request $request, ListAction $action): Response
    {
        /** @var User $user */
        $user = $request->user();
        $search = $request->string('search')->toString() ?: null;
        $filter = $request->string('filter')->toString() ?: null;

        $games = $action->handle($search, $filter)->through(fn (Game $game): array => [
            ...$game->toArray(),
            'can' => [
                'update' => $user->can('update', $game),
                'delete' => $user->can('delete', $game),
                'upload_video' => $user->can('uploadVideo', $game),
            ],
        ]);

        $myTeamIds = $user->teams()->pluck('teams.id');

        return Inertia::render('admin/games/index', [
            'games' => $games,
            'filters' => ['search' => $search, 'filter' => $filter],
            'courts' => Court::query()->select(['id', 'name', 'category'])->orderBy('name')->get(),
            'teams' => $user->teams()->select(['teams.id', 'teams.name'])->get(),
            'opponentPlayers' => User::query()
                ->active()
                ->whereKeyNot($user->id)
                ->whereHas('roles', fn (Builder $query) => $query->where('name', Role::Player->value))
                ->orderBy('name')
                ->get(['id', 'name']),
            'opponentTeams' => Team::query()
                ->whereNotIn('id', $myTeamIds)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(StoreGameRequest $request, StoreAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->handle(array_merge($request->validated(), [
            'player_id' => $user->id,
        ]));

        return to_route('admin.games.index')->with('success', 'Game created successfully.');
    }

    public function show(Request $request, Game $game): Response
    {
        /** @var User $user */
        $user = $request->user();

        Gate::authorize('view', $game);

        $game->load(['court', 'gameResult', 'player:id,name', 'team', 'opponentPlayer:id,name', 'opponentTeam']);

        $dispute = $game->disputes()->where('player_id', $user->id)->first();
        $canDispute = $game->status === GameStatus::Flagged
            && $game->gameResult?->submitter_id === $user->id
            && $dispute === null;

        return Inertia::render('admin/games/show', [
            'game' => $game,
            'canDispute' => $canDispute,
            'dispute' => $dispute,
            ...$this->resultCapabilities($user, $game),
        ]);
    }

    public function update(UpdateGameRequest $request, UpdateAction $action, Game $game): RedirectResponse
    {
        $action->handle($game, $request->validated());

        return to_route('admin.games.index');
    }

    public function destroy(DeleteGameRequest $request, DeleteAction $action, Game $game): RedirectResponse
    {
        $action->handle($game);

        return back();
    }

    public function showUpload(Request $request, Game $game): Response
    {
        /** @var User $user */
        $user = $request->user();

        Gate::authorize('uploadVideo', $game);

        $game->load(['court', 'gameResult', 'player:id,name', 'team', 'opponentPlayer:id,name', 'opponentTeam']);

        return Inertia::render('admin/games/upload', [
            'game' => $game,
            ...$this->resultCapabilities($user, $game),
        ]);
    }

    public function initiateUpload(UploadGameRequest $request, InitiateVimeoUploadAction $action, Game $game): JsonResponse
    {
        /** @var int $fileSize */
        $fileSize = $request->validated('file_size');
        $result = $action->handle($game, $fileSize);

        return response()->json($result);
    }

    public function completeUpload(CompleteUploadRequest $request, CompleteUploadAction $action, Game $game): RedirectResponse
    {
        $action->handle($game);

        return to_route('admin.games.index');
    }

    /**
     * @return array{canSubmitResult: bool, submitResultBlockedReason: ?string, canRespondToResult: bool}
     */
    private function resultCapabilities(User $user, Game $game): array
    {
        $isSubmitter = $user->can('submitResult', $game);
        $blocker = $isSubmitter ? $game->resultSubmissionBlocker() : null;

        return [
            'canSubmitResult' => $isSubmitter && $blocker === null,
            'submitResultBlockedReason' => $blocker,
            'canRespondToResult' => $game->status === GameStatus::AwaitingConfirmation
                && $user->can('respondToResult', $game),
        ];
    }
}
