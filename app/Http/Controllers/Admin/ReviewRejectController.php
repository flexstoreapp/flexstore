<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\BulkRejectReviewAction;
use App\Http\Requests\Admin\BulkRejectReviewRequest;
use Illuminate\Http\RedirectResponse;

final readonly class ReviewRejectController
{
    public function store(BulkRejectReviewRequest $request, BulkRejectReviewAction $action): RedirectResponse
    {
        $action->handle($request->validated('ids'));

        return back();
    }
}
