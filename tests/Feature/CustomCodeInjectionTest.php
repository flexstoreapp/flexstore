<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Queries\StorefrontLayoutDataQuery;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

covers(StorefrontLayoutDataQuery::class);

uses()->group('custom-code-injection');

test('storefront pages include custom css and js in shared props', function () {
    Setting::setValue('storefront_custom_css', 'body { background: red; }');
    Setting::setValue('storefront_custom_js', 'console.log("custom")');

    $response = get(route('home'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('storefront.customCss', 'body { background: red; }')
                ->where('storefront.customJs', 'console.log("custom")')
        );
});

test('storefront pages include empty strings when no custom code is set', function () {
    $response = get(route('home'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('storefront.customCss', '')
                ->where('storefront.customJs', '')
        );
});

test('admin pages do not include custom css and js', function () {
    $response = actingAsSuperAdmin()->get(route('admin.dashboard'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->missing('storefront')
        );
});
