<?php

declare(strict_types=1);

use App\Actions\UpdateSettingsAction;
use App\Actions\UpdateStorefrontHeaderAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\StorefrontHeaderController;
use App\Http\Requests\Admin\UpdateStorefrontHeaderRequest;
use App\Models\Category;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patch;

covers(StorefrontHeaderController::class, UpdateStorefrontHeaderRequest::class, UpdateStorefrontHeaderAction::class, UpdateSettingsAction::class);

uses()->group('storefront');

test('updates sticky setting', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.header.update'), [
        'storefront_header_sticky' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_header_sticky',
        'value' => '1',
    ]);
});

test('validates storefront_header_sticky is boolean', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.header.update'), [
        'storefront_header_sticky' => 'invalid',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('storefront_header_sticky');
});

test('updates browse categories', function () {
    $category = Category::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.header.update'), [
        'storefront_header_browse_categories' => [
            [
                'category_id' => $category->id,
                'is_mega_menu' => true,
                'featured_image_id' => null,
                'featured_title' => 'New arrivals',
                'featured_url' => '/products',
            ],
        ],
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect(App\Models\Setting::getValue('storefront_header_browse_categories'))
        ->toBe([[
            'category_id' => $category->id,
            'is_mega_menu' => true,
            'featured_image_id' => null,
            'featured_title' => ['en' => 'New arrivals'],
            'featured_url' => '/products',
        ]]);
});

test('merges featured title per locale preserving other locales', function () {
    $category = Category::factory()->create();

    App\Models\Setting::setValue('storefront_header_browse_categories', [
        [
            'category_id' => $category->id,
            'is_mega_menu' => true,
            'featured_image_id' => null,
            'featured_title' => ['en' => 'English title', 'ar' => 'العنوان'],
            'featured_url' => null,
        ],
    ]);

    actingAsSuperAdmin()->patch(route('admin.storefront.header.update'), [
        'storefront_header_browse_categories' => [
            ['category_id' => $category->id, 'is_mega_menu' => true, 'featured_title' => 'Updated English'],
        ],
    ])->assertSessionHasNoErrors();

    $stored = App\Models\Setting::getValue('storefront_header_browse_categories');

    expect($stored[0]['featured_title'])->toBe(['en' => 'Updated English', 'ar' => 'العنوان']);
});

test('allows clearing browse categories with empty array', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.header.update'), [
        'storefront_header_browse_categories' => [],
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();
});

test('validates browse category exists', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.header.update'), [
        'storefront_header_browse_categories' => [
            ['category_id' => 999999, 'is_mega_menu' => false],
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('storefront_header_browse_categories.0.category_id');
});

test('requires authentication', function () {
    $response = patch(route('admin.storefront.header.update'), [
        'storefront_header_sticky' => true,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront.update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->patch(route('admin.storefront.header.update'), [
        'storefront_header_sticky' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    $role->revokePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->patch(route('admin.storefront.header.update'), [
        'storefront_header_sticky' => false,
    ]);

    $response->assertForbidden();
});
