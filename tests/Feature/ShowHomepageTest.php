<?php

declare(strict_types=1);

use App\Http\Controllers\Storefront\HomepageController;
use App\Models\Setting;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

covers(HomepageController::class);

uses()->group('homepage');

test('displays homepage', function () {
    $response = get(route('home'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('storefront/homepage')
        );
});

test('renders homepage seo settings in the document head', function () {
    Setting::setValue('seo_homepage_meta_title', 'My Store');
    Setting::setValue('seo_homepage_meta_description', 'The best store');

    get(route('home'))
        ->assertOk()
        ->assertSee('<title data-inertia="title">My Store</title>', false)
        ->assertSee('name="description"', false)
        ->assertSee('content="The best store"', false);
});
