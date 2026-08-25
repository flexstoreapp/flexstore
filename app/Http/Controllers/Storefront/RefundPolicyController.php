<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Models\Setting;
use App\Utilities\StorefrontHead;
use Inertia\Inertia;
use Inertia\Response;

final readonly class RefundPolicyController
{
    public function show(): Response
    {
        StorefrontHead::page(__('Refund policy'));

        return Inertia::render('storefront/policies/refund', [
            'content' => Setting::getValue('refund_policy'),
        ]);
    }
}
