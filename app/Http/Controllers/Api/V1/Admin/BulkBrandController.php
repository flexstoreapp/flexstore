<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\BulkDestroyBrandAction;
use App\Http\Requests\Admin\BulkDestroyBrandRequest;
use Symfony\Component\HttpFoundation\Response;

final readonly class BulkBrandController
{
    public function destroy(BulkDestroyBrandRequest $request, BulkDestroyBrandAction $action): Response
    {
        $action->handle(array_map(intval(...), $request->safe()->array('ids')));

        return response()->noContent();
    }
}
