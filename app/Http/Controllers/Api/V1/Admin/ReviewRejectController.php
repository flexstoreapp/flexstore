<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\BulkRejectReviewAction;
use App\Http\Requests\Admin\BulkRejectReviewRequest;
use Illuminate\Http\JsonResponse;

final readonly class ReviewRejectController
{
    public function store(BulkRejectReviewRequest $request, BulkRejectReviewAction $action): JsonResponse
    {
        $updated = $action->handle(array_map(intval(...), $request->safe()->array('ids')));

        return response()->json([
            'message' => __('Reviews rejected.'),
            'updated' => $updated,
        ]);
    }
}
