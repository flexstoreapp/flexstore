<?php

declare(strict_types=1);

use App\Http\Controllers\Storefront\BrandController;
use App\Models\Brand;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

covers(BrandController::class);

uses()->group('brand-list');

test('displays active brands sorted alphabetically', function () {
    Brand::factory()->active()->create(['name' => ['en' => 'Zenith']]);
    Brand::factory()->active()->create(['name' => ['en' => 'Alba']]);
    Brand::factory()->inactive()->create(['name' => ['en' => 'Hidden']]);

    $response = get(route('brands.index'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('storefront/brands/list')
                ->has('brands', 2)
                ->where('brands.0.name.en', 'Alba')
                ->where('brands.1.name.en', 'Zenith')
        );
});
