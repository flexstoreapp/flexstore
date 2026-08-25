<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Models\Setting;
use App\Utilities\StorefrontHead;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PrivacyPolicyController
{
    public function show(): Response
    {
        StorefrontHead::page(__('Privacy policy'));

        return Inertia::render('storefront/policies/privacy', [
            'content' => Setting::getValue('privacy_policy'),
        ]);
    }
}
