<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\BulkDestroyCouponAction;
use App\Http\Requests\Admin\BulkDestroyCouponRequest;
use Illuminate\Http\RedirectResponse;

final readonly class BulkCouponController
{
    public function destroy(BulkDestroyCouponRequest $request, BulkDestroyCouponAction $action): RedirectResponse
    {
        $action->handle($request->validated('ids'));

        return back();
    }
}
