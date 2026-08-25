<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\BulkDestroyBrandAction;
use App\Http\Requests\Admin\BulkDestroyBrandRequest;
use Illuminate\Http\RedirectResponse;

final readonly class BulkBrandController
{
    public function destroy(BulkDestroyBrandRequest $request, BulkDestroyBrandAction $action): RedirectResponse
    {
        $action->handle($request->validated('ids'));

        return back();
    }
}
