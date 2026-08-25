<?php

declare(strict_types=1);

use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Storefront\HomepageController;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Setting;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

covers(HomepageController::class, HandleInertiaRequests::class);

uses()->group('homepage', 'theme');

test('homepage uses storefront theme from settings', function () {
    Setting::setValue('storefront_theme_color', 'blue');

    $response = get(route('home'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('storefront/homepage')
                ->where('theme', 'blue')
        );
});

test('homepage uses default storefront accent when no setting exists', function () {
    Setting::query()->where('key', 'storefront_theme_color')->delete();

    $response = get(route('home'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('storefront/homepage')
                ->where('theme', 'blue')
        );
});

test('homepage emits storefront accent tokens for the configured accent', function () {
    Setting::setValue('storefront_theme_color', 'green');

    $response = get(route('home'));

    $response->assertOk()
        ->assertSee('--color-primary: oklch(0.53 0.14 150)', false)
        ->assertSee('--color-primary-hover: oklch(0.46 0.13 150)', false)
        ->assertSee('--color-primary-tint: oklch(0.95 0.04 150)', false);
});

test('storefront does not share an appearance prop', function () {
    $response = get(route('home'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page->missing('appearance')
        );
});

test('homepage storefront theme is independent of user preferences', function () {
    $user = User::factory()->create([
        'admin_theme_color' => 'red',
        'appearance' => 'dark',
    ]);

    Setting::setValue('storefront_theme_color', 'blue');

    $response = actingAs($user)->get(route('home'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('storefront/homepage')
                ->where('theme', 'blue')
        );
});

test('admin routes use user personal theme preferences', function () {
    $superAdmin = Role::query()->firstOrCreate(['name' => RoleEnum::SuperAdmin]);
    $user = User::factory()->create([
        'admin_theme_color' => 'purple',
        'appearance' => 'dark',
    ])->assignRole($superAdmin);

    Setting::setValue('storefront_theme_color', 'blue');

    $response = actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('adminThemeColor', 'purple')
                ->where('appearance', 'dark')
        );
});

test('admin routes use default theme when user has no preferences', function () {
    $superAdmin = Role::query()->firstOrCreate(['name' => RoleEnum::SuperAdmin]);
    $user = User::factory()->create([
        'admin_theme_color' => null,
        'appearance' => null,
    ])->assignRole($superAdmin);

    $response = actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('adminThemeColor', 'neutral')
                ->where('appearance', 'system')
        );
});

test('homepage shares ltr direction for english locale', function () {
    app()->setLocale('en');

    $response = get(route('home'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('direction', 'ltr')
        );
});

test('homepage shares rtl direction for arabic locale', function () {
    Setting::setValue('available_locales', ['en', 'ar']);

    $response = get(route('home', ['lang' => 'ar']));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('direction', 'rtl')
        );
});

test('homepage shares rtl direction for hyphenated locale', function () {
    Setting::setValue('available_locales', ['en', 'ar-SA']);

    $response = get(route('home', ['lang' => 'ar-SA']));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('direction', 'rtl')
        );
});

test('storefront theme changes are immediately reflected', function () {
    Setting::setValue('storefront_theme_color', 'red');

    $response1 = get(route('home'));
    $response1->assertInertia(
        fn (AssertableInertia $page) => $page->where('theme', 'red')
    );

    Setting::setValue('storefront_theme_color', 'teal');

    $response2 = get(route('home'));
    $response2->assertInertia(
        fn (AssertableInertia $page) => $page->where('theme', 'teal')
    );
});
