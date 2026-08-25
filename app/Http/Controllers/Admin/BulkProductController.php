<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\BulkDestroyProductAction;
use App\Http\Requests\Admin\BulkDestroyProductRequest;
use Illuminate\Http\RedirectResponse;

final readonly class BulkProductController
{
    public function destroy(BulkDestroyProductRequest $request, BulkDestroyProductAction $action): RedirectResponse
    {
        $action->handle($request->validated('ids'));

        return back();
    }
}
