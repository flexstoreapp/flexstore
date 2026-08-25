<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\BulkApproveReviewAction;
use App\Http\Requests\Admin\BulkApproveReviewRequest;
use Illuminate\Http\RedirectResponse;

final readonly class ReviewApproveController
{
    public function store(BulkApproveReviewRequest $request, BulkApproveReviewAction $action): RedirectResponse
    {
        $action->handle($request->validated('ids'));

        return back();
    }
}
