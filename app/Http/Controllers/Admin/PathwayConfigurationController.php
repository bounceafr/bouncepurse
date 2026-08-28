<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Pathway\UpdatePathwayConfigurationRequest;
use App\Jobs\RecalculatePathwayEligibilityJob;
use App\Models\PathwayConfiguration;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class PathwayConfigurationController extends Controller
{
    public function edit(): Response
    {
        $config = PathwayConfiguration::query()->latest()->first();

        return Inertia::render('admin/pathway/edit', [
            'config' => $config,
        ]);
    }

    public function update(UpdatePathwayConfigurationRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $config = PathwayConfiguration::query()->create([
            ...$request->validated(),
            'updated_by' => $user->id,
        ]);

        dispatch(new RecalculatePathwayEligibilityJob($config->id));

        return to_route('admin.pathway.edit')->with('success', 'Pathway configuration saved. Recalculation is queued.');
    }
}
