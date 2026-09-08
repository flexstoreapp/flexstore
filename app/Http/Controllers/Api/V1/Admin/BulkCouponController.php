<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\BulkDestroyCouponAction;
use App\Http\Requests\Admin\BulkDestroyCouponRequest;
use Symfony\Component\HttpFoundation\Response;

final readonly class BulkCouponController
{
    public function destroy(BulkDestroyCouponRequest $request, BulkDestroyCouponAction $action): Response
    {
        $action->handle(array_values(array_map(intval(...), $request->safe()->array('ids'))));

        return response()->noContent();
    }
}
