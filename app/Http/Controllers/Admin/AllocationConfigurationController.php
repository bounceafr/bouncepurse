<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Allocation\UpdateAllocationConfiguration;
use App\Enums\AllocationCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Allocation\UpdateAllocationConfigurationRequest;
use App\Models\AllocationConfiguration;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class AllocationConfigurationController extends Controller
{
    public function edit(): Response
    {
        $config = AllocationConfiguration::query()->latest()->firstOrFail();

        return Inertia::render('admin/allocation-configuration/edit', [
            'config' => $config,
            'categories' => AllocationCategory::toArray(),
        ]);
    }

    public function update(UpdateAllocationConfigurationRequest $request, UpdateAllocationConfiguration $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        $percentages = [];

        foreach (AllocationCategory::cases() as $category) {
            $column = $category->percentageColumn();
            $percentages[$column] = (float) $validated[$column];
        }

        $action->handle($percentages, $user);

        return to_route('admin.allocation-configuration.edit')->with('success', 'Allocation configuration saved.');
    }
}
