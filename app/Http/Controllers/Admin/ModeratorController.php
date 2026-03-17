<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Moderator\ListModeratorPerformanceAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ModeratorController extends Controller
{
    public function index(Request $request, ListModeratorPerformanceAction $action): Response
    {
        $from = $request->string('from')->toString() ?: null;
        $to = $request->string('to')->toString() ?: null;

        $filters = array_filter([
            'from' => $from,
            'to' => $to,
        ]);

        return Inertia::render('admin/moderators/index', [
            'moderators' => $action->handle($filters),
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }
}
