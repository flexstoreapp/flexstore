<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\ReorderStorefrontSectionsAction;
use App\Http\Requests\Admin\ReorderStorefrontSectionsRequest;
use Illuminate\Http\RedirectResponse;

final readonly class StorefrontSectionReorderController
{
    public function update(
        ReorderStorefrontSectionsRequest $request,
        ReorderStorefrontSectionsAction $action
    ): RedirectResponse {
        $action->handle($request->validated('ordered_ids'));

        return back();
    }
}
