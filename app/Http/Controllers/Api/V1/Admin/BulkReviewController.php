<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\BulkDestroyReviewAction;
use App\Http\Requests\Admin\BulkDestroyReviewRequest;
use Symfony\Component\HttpFoundation\Response;

final readonly class BulkReviewController
{
    public function destroy(BulkDestroyReviewRequest $request, BulkDestroyReviewAction $action): Response
    {
        $action->handle(array_map(intval(...), $request->safe()->array('ids')));

        return response()->noContent();
    }
}
