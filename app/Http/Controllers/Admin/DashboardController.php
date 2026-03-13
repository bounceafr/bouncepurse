<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\GetAdminDashboardMetricsAction;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(GetAdminDashboardMetricsAction $action): Response
    {
        return Inertia::render('admin/dashboard', [
            'metrics' => $action->handle(),
        ]);
    }
}
