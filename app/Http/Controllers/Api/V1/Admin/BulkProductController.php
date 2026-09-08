<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\BulkDestroyProductAction;
use App\Http\Requests\Admin\BulkDestroyProductRequest;
use Symfony\Component\HttpFoundation\Response;

final readonly class BulkProductController
{
    public function destroy(BulkDestroyProductRequest $request, BulkDestroyProductAction $action): Response
    {
        $action->handle($request->safe()->array('ids'));

        return response()->noContent();
    }
}
