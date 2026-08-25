<?php

declare(strict_types=1);

use App\Http\Controllers\RobotsController;
use App\Models\Setting;

use function Pest\Laravel\get;

covers(RobotsController::class);

uses()->group('seo', 'robots');

test('serves robots.txt referencing the sitemap when indexing is enabled', function () {
    Setting::setValue('seo_robots_indexing', true);

    $response = get('/robots.txt');

    $response->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('text/plain');

    $response->assertSee('User-agent: *', false)
        ->assertSee('Sitemap: ' . route('sitemap'), false);
});

test('does not disclose the admin path', function () {
    config(['admin.prefix' => 'control-panel']);
    Setting::setValue('seo_robots_indexing', true);

    get('/robots.txt')
        ->assertOk()
        ->assertDontSee('control-panel', false)
        ->assertDontSee('Disallow: /admin', false);
});

test('disallows all crawling when indexing is disabled', function () {
    Setting::setValue('seo_robots_indexing', false);

    $response = get('/robots.txt');

    $response->assertOk()
        ->assertSee('Disallow: /', false)
        ->assertDontSee('Sitemap:', false);
});
