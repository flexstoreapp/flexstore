<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Response;

final readonly class RobotsController
{
    public function __invoke(): Response
    {
        $indexingEnabled = (bool) Setting::getValue('seo_robots_indexing', false);

        $lines = ['User-agent: *'];

        if ($indexingEnabled) {
            $lines = [
                ...$lines,
                'Disallow: /checkout',
                'Disallow: /cart',
                'Disallow: /account',
                'Disallow: /track-order',
                '',
                'Sitemap: ' . route('sitemap'),
            ];
        } else {
            $lines[] = 'Disallow: /';
        }

        return response(implode("\n", $lines) . "\n")
            ->header('Content-Type', 'text/plain');
    }
}
