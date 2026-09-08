<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\BulkApproveReviewAction;
use App\Http\Requests\Admin\BulkApproveReviewRequest;
use Illuminate\Http\JsonResponse;

final readonly class ReviewApproveController
{
    public function store(BulkApproveReviewRequest $request, BulkApproveReviewAction $action): JsonResponse
    {
        $updated = $action->handle(array_map(intval(...), $request->safe()->array('ids')));

        return response()->json([
            'message' => __('Reviews approved.'),
            'updated' => $updated,
        ]);
    }
}
