<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\DestroyAnnouncementAction;
use App\Actions\StoreAnnouncementAction;
use App\Actions\UpdateAnnouncementAction;
use App\Http\Requests\Admin\StoreAnnouncementRequest;
use App\Http\Requests\Admin\UpdateAnnouncementRequest;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AnnouncementController
{
    public function index(): Response
    {
        return Inertia::render('admin/storefront/announcements', [
            'announcements' => Announcement::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/storefront/announcements/create');
    }

    public function store(StoreAnnouncementRequest $request, StoreAnnouncementAction $action): RedirectResponse
    {
        $announcement = $action->handle($request->toDto());

        return to_route('admin.storefront.announcements.edit', $announcement);
    }

    public function edit(Announcement $announcement): Response
    {
        return Inertia::render('admin/storefront/announcements/edit', [
            'announcement' => $announcement,
        ]);
    }

    public function update(
        UpdateAnnouncementRequest $request,
        Announcement $announcement,
        UpdateAnnouncementAction $action
    ): RedirectResponse {
        $action->handle($announcement, $request->toDto());

        return back();
    }

    public function destroy(Announcement $announcement, DestroyAnnouncementAction $action): RedirectResponse
    {
        $action->handle($announcement);

        return back();
    }
}
