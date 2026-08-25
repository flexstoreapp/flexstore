<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\DestroyStorefrontSectionAction;
use App\Actions\StoreStorefrontSectionAction;
use App\Actions\UpdateStorefrontSectionAction;
use App\Http\Requests\Admin\StoreStorefrontSectionRequest;
use App\Http\Requests\Admin\UpdateStorefrontSectionRequest;
use App\Models\StorefrontSection;
use App\Queries\ResolveSectionMediaQuery;
use App\Queries\ResolveSectionSettingsQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class StorefrontSectionController
{
    public function create(): Response
    {
        return Inertia::render('admin/storefront/sections/create');
    }

    public function store(StoreStorefrontSectionRequest $request, StoreStorefrontSectionAction $action): RedirectResponse
    {
        $section = $action->handle($request->toDto());

        return to_route('admin.storefront.homepage.sections.edit', $section);
    }

    public function edit(
        StorefrontSection $section,
        ResolveSectionSettingsQuery $query,
        ResolveSectionMediaQuery $mediaQuery,
    ): Response {
        $media = $mediaQuery->preload(collect([$section]));

        return Inertia::render('admin/storefront/sections/edit', [
            'section' => [
                ...$section->toArray(),
                'settings' => $query->execute($section, $media),
            ],
        ]);
    }

    public function update(
        UpdateStorefrontSectionRequest $request,
        StorefrontSection $section,
        UpdateStorefrontSectionAction $action
    ): RedirectResponse {
        $action->handle($section, $request->toDto());

        return back();
    }

    public function destroy(StorefrontSection $section, DestroyStorefrontSectionAction $action): RedirectResponse
    {
        $action->handle($section);

        return back();
    }
}
