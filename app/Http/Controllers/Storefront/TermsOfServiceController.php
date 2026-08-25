<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Models\Setting;
use App\Utilities\StorefrontHead;
use Inertia\Inertia;
use Inertia\Response;

final readonly class TermsOfServiceController
{
    public function show(): Response
    {
        StorefrontHead::page(__('Terms of service'));

        return Inertia::render('storefront/policies/terms', [
            'content' => Setting::getValue('terms_of_service'),
        ]);
    }
}
