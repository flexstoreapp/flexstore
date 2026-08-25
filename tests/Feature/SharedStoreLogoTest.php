<?php

declare(strict_types=1);

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Media;
use App\Models\Setting;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

covers(HandleInertiaRequests::class);

uses()->group('storefront');

test('resolves the store logo setting to a media object', function () {
    $logo = Media::factory()->create();
    Setting::setValue('store_logo', $logo->id);

    get(route('home'))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('storeLogo.id', $logo->id)
                ->has('storeLogo.url'),
        );
});

test('resolves the dark store logo setting to a media object', function () {
    $logo = Media::factory()->create();
    Setting::setValue('store_logo_dark', $logo->id);

    get(route('home'))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('storeLogoDark.id', $logo->id)
                ->has('storeLogoDark.url'),
        );
});

test('store logo is null when the setting is not set', function () {
    get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('storeLogo', null));
});
