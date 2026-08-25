<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\BulkDestroyCustomerAction;
use App\Http\Requests\Admin\BulkDestroyCustomerRequest;
use Illuminate\Http\RedirectResponse;

final readonly class BulkCustomerController
{
    public function destroy(BulkDestroyCustomerRequest $request, BulkDestroyCustomerAction $action): RedirectResponse
    {
        $action->handle($request->validated('ids'));

        return back();
    }
}
