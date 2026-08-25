<?php

declare(strict_types=1);

use App\Http\Controllers\Storefront\CategoryController;
use App\Models\Category;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

covers(CategoryController::class);

uses()->group('category-list');

test('displays the category list page with top-level categories', function () {
    $electronics = Category::factory()->active()->create(['name' => ['en' => 'Electronics']]);
    Category::factory()->active()->withParent($electronics)->create(['name' => ['en' => 'Audio']]);
    Category::factory()->active()->create(['name' => ['en' => 'Fashion']]);
    Category::factory()->inactive()->create();

    $response = get(route('categories.index'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('storefront/categories/list')
                ->has('categories', 2)
                ->where('categories.0.name.en', 'Electronics')
                ->has('categories.0.children', 1)
                ->where('categories.0.children.0.name.en', 'Audio')
        );
});
