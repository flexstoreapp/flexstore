<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\BulkDestroyCustomerAction;
use App\Http\Requests\Admin\BulkDestroyCustomerRequest;
use Symfony\Component\HttpFoundation\Response;

final readonly class BulkCustomerController
{
    public function destroy(BulkDestroyCustomerRequest $request, BulkDestroyCustomerAction $action): Response
    {
        $action->handle(array_map(intval(...), $request->safe()->array('ids')));

        return response()->noContent();
    }
}
