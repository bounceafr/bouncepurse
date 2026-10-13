<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use App\Support\VersionedAsset;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Middleware;
use Override;

final class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    #[Override]
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'roles' => $request->user()?->getRoleNames() ?? collect(),
                'permissions' => $request->user()?->hasRole(Role::SuperAdmin)
                    ? Permission::values()
                    : ($request->user()?->getAllPermissions()->pluck('name') ?? collect()),
            ],
            'assets' => [
                'logo' => VersionedAsset::url('bounce_logo.png'),
                'logoDark' => VersionedAsset::url('bounce-logo.png'),
            ],
            'flash' => [
                'success' => fn (): mixed => $request->session()->get('success'),
                'error' => fn (): mixed => $request->session()->get('error'),
            ],
            'notifications' => fn (): ?array => $this->notifications($request->user()),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return array{unread_count: int, recent: list<array{id: string, title: string, message: string, url: ?string, read_at: ?string, created_at: ?string}>}|null
     */
    private function notifications(?User $user): ?array
    {
        if (! $user instanceof User) {
            return null;
        }

        $recent = $user->notifications()->latest()->limit(8)->get()
            ->map(fn (DatabaseNotification $notification): array => [
                'id' => $notification->id,
                'title' => is_string($notification->data['title'] ?? null) ? $notification->data['title'] : '',
                'message' => is_string($notification->data['message'] ?? null) ? $notification->data['message'] : '',
                'url' => is_string($notification->data['url'] ?? null) ? $notification->data['url'] : null,
                'read_at' => $notification->read_at?->toISOString(),
                'created_at' => $notification->created_at?->toISOString(),
            ]);

        return [
            'unread_count' => $user->unreadNotifications()->count(),
            'recent' => array_values($recent->all()),
        ];
    }
}
