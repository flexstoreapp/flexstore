<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\BulkDestroyRegionAction;
use App\Http\Requests\Admin\BulkDestroyRegionRequest;
use Illuminate\Http\RedirectResponse;

final readonly class BulkRegionController
{
    public function destroy(BulkDestroyRegionRequest $request, BulkDestroyRegionAction $action): RedirectResponse
    {
        $action->handle($request->validated('ids'));

        return back();
    }
}
