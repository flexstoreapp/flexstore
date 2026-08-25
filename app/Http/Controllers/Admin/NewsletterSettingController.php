<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use Inertia\Response;

final readonly class NewsletterSettingController
{
    public function show(): Response
    {
        return Inertia::render('admin/settings/newsletter', [
            'newsletterProviders' => [],
            'activeNewsletterProviderId' => null,
        ]);
    }
}
