<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\SendPasswordResetLinkAction;
use App\Http\Requests\SendPasswordResetLinkRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PasswordResetLinkController
{
    public function create(Request $request): Response
    {
        return Inertia::render('admin/auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(SendPasswordResetLinkRequest $request, SendPasswordResetLinkAction $action): RedirectResponse
    {
        $action->handle($request->toDto(), admin: true);

        return back()->with('status', __('A reset link will be sent if the account exists.'));
    }
}
