<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\ReorderAnnouncementsAction;
use App\Http\Requests\Admin\ReorderAnnouncementsRequest;
use Illuminate\Http\RedirectResponse;

final readonly class AnnouncementReorderController
{
    public function __invoke(ReorderAnnouncementsRequest $request, ReorderAnnouncementsAction $action): RedirectResponse
    {
        $action->handle($request->validated('ordered_ids'));

        return back();
    }
}
