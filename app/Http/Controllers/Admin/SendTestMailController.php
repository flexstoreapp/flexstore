<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\SendTestMailAction;
use App\Http\Requests\Admin\SendTestMailRequest;
use Illuminate\Http\RedirectResponse;
use Throwable;

final readonly class SendTestMailController
{
    public function __invoke(SendTestMailRequest $request, SendTestMailAction $action): RedirectResponse
    {
        try {
            $action->handle($request->safe()->string('recipient')->value());
        } catch (Throwable $exception) {
            return back()->withErrors(['recipient' => $exception->getMessage()]);
        }

        return back();
    }
}
