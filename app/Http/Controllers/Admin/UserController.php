<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\User\DeactivateUserAction;
use App\Actions\Admin\User\DeleteAction;
use App\Actions\Admin\User\ListAction;
use App\Actions\Admin\User\ReactivateUserAction;
use App\Actions\Admin\User\StoreAction;
use App\Actions\Admin\User\UpdateAction;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\DeactivateUserRequest;
use App\Http\Requests\Admin\User\DeleteUserRequest;
use App\Http\Requests\Admin\User\StoreUserRequest;
use App\Http\Requests\Admin\User\UpdateUserRequest;
use App\Models\Game;
use App\Models\GameModeration;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class UserController extends Controller
{
    public function index(Request $request, ListAction $action): Response
    {
        $search = $request->string('search')->toString() ?: null;
        $role = $request->string('role')->toString() ?: null;

        return Inertia::render('admin/users/index', [
            'users' => $action->handle($search, $role),
            'roles' => $this->roleOptions(),
            'filters' => ['search' => $search, 'role' => $role],
        ]);
    }

    public function show(User $user): Response
    {
        $user->load(['roles', 'profile.country', 'deactivatedBy']);

        /** @var Collection<int, \Spatie\Permission\Models\Role> $userRoles */
        $userRoles = $user->roles;

        $recentGames = Game::query()->withoutGlobalScopes()
            ->where('player_id', $user->id)
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(fn (Game $game): array => [
                'id' => $game->id,
                'uuid' => $game->uuid,
                'title' => $game->title,
                'status' => $game->status->value,
                'played_at' => $game->played_at->toISOString(),
                'created_at' => $game->created_at?->toISOString() ?? '',
            ]);

        $recentModerationReviews = $user->moderationReviews()
            ->with('game')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (GameModeration $review): array => [
                'id' => $review->id,
                'game_title' => $review->game->title ?? null,
                'game_uuid' => $review->game->uuid ?? null,
                'status' => $review->status->value,
                'created_at' => $review->created_at?->toISOString() ?? '',
            ]);

        return Inertia::render('admin/users/show', [
            'user' => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toISOString() ?? '',
                'deactivated_at' => $user->deactivated_at?->toISOString(),
                'deactivation_reason' => $user->deactivation_reason,
                'deactivated_by' => $user->deactivatedBy?->name,
                'roles' => $userRoles->map(fn (\Spatie\Permission\Models\Role $r): array => ['id' => $r->id, 'name' => $r->name])->all(),
                'profile' => $user->profile ? [
                    'date_of_birth' => $user->profile->date_of_birth->format('Y-m-d'),
                    'city' => $user->profile->city,
                    'bio' => $user->profile->bio,
                    'position' => $user->profile->position,
                    'country' => $user->profile->country->name,
                ] : null,
                'recent_games' => $recentGames->all(),
                'recent_moderation_reviews' => $recentModerationReviews->all(),
            ],
            'roles' => $this->roleOptions(),
        ]);
    }

    public function store(StoreUserRequest $request, StoreAction $action): RedirectResponse
    {
        /** @var array{name: string, email: string, password: string, role: string} $data */
        $data = $request->validated();
        $action->handle($data);

        return to_route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function update(UpdateUserRequest $request, UpdateAction $action, User $user): RedirectResponse
    {
        /** @var array{name: string, email: string, role: string} $data */
        $data = $request->validated();
        $action->handle($user, $data);

        return to_route('admin.users.index');
    }

    public function destroy(DeleteUserRequest $request, DeleteAction $action, User $user): RedirectResponse
    {
        $action->handle($user);

        return to_route('admin.users.index');
    }

    public function deactivate(DeactivateUserRequest $request, DeactivateUserAction $action, User $user): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $reason = $request->validated('reason');
        /** @var string $reason */
        $action->handle($user, $reason, $admin);

        return to_route('admin.users.show', $user)->with('success', 'User deactivated.');
    }

    public function reactivate(Request $request, ReactivateUserAction $action, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return to_route('admin.users.show', $user)->withErrors(['user' => 'You cannot reactivate your own account.']);
        }

        $action->handle($user);

        return to_route('admin.users.show', $user)->with('success', 'User reactivated.');
    }

    /**
     * @return list<array{value: string, label: string, color: string}>
     */
    private function roleOptions(): array
    {
        return array_map(
            fn (Role $role): array => [
                'value' => $role->value,
                'label' => $role->label(),
                'color' => $role->color(),
            ],
            Role::cases()
        );
    }
}
