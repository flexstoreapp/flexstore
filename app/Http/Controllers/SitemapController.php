<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Queries\SitemapQuery;
use Illuminate\Http\Response;

final readonly class SitemapController
{
    public function __invoke(SitemapQuery $query): Response
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($query->execute() as $url) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>' . e($url['loc']) . '</loc>';

            if ($url['lastmod'] !== null) {
                $lines[] = '    <lastmod>' . $url['lastmod'] . '</lastmod>';
            }

            $lines[] = '    <changefreq>' . $url['changefreq'] . '</changefreq>';
            $lines[] = '    <priority>' . $url['priority'] . '</priority>';
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return response(implode("\n", $lines))
            ->header('Content-Type', 'application/xml');
    }
}
