<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\BulkDestroyReviewAction;
use App\Http\Requests\Admin\BulkDestroyReviewRequest;
use Illuminate\Http\RedirectResponse;

final readonly class BulkReviewController
{
    public function destroy(BulkDestroyReviewRequest $request, BulkDestroyReviewAction $action): RedirectResponse
    {
        $action->handle($request->validated('ids'));

        return back();
    }
}
