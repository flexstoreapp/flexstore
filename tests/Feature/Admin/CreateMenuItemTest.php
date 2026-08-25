<?php

declare(strict_types=1);

use App\Actions\StoreMenuItemAction;
use App\Enums\MenuItemLinkType;
use App\Enums\MenuLocation;
use App\Enums\MenuPage;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Requests\Admin\StoreMenuItemRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Media;
use App\Models\MenuItem;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

covers(MenuItemController::class, StoreMenuItemRequest::class, StoreMenuItemAction::class);

uses()->group('menu-item');

test('shows create form with location parameter', function () {
    $response = actingAsSuperAdmin()->get(route('admin.storefront.menu-items.create', ['location' => MenuLocation::Header->value]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/storefront/menu-items/create')
            ->has('location')
            ->where('location', MenuLocation::Header->value));
});

test('shows create form with default location when not specified', function () {
    $response = actingAsSuperAdmin()->get(route('admin.storefront.menu-items.create'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/storefront/menu-items/create')
            ->has('location')
            ->where('location', MenuLocation::Header->value));
});

test('creates menu item with category link successfully', function () {
    $category = Category::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Electronics',
        'link_type' => MenuItemLinkType::Category->value,
        'category_id' => $category->id,
        'target' => '_self',
        'is_active' => true,
    ]);

    $response->assertSessionHasNoErrors();

    expect(MenuItem::query()->count())->toBe(1);

    $menuItem = MenuItem::query()->first();

    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem));

    expect($menuItem)
        ->location->toBe(MenuLocation::Header)
        ->label->toBe('Electronics')
        ->link_type->toBe(MenuItemLinkType::Category)
        ->category_id->toBe($category->id)
        ->is_active->toBeTrue();
});

test('creates menu item with custom url successfully', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Footer->value,
        'label' => 'Contact Us',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com/contact',
        'target' => '_blank',
        'is_active' => true,
    ]);

    $response->assertSessionHasNoErrors();

    expect(MenuItem::query()->count())->toBe(1);

    $menuItem = MenuItem::query()->first();

    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem));

    expect($menuItem)
        ->location->toBe(MenuLocation::Footer)
        ->link_type->toBe(MenuItemLinkType::Custom)
        ->url->toBe('https://example.com/contact')
        ->target->toBe('_blank');
});

test('creates menu item with parent', function () {
    $parent = MenuItem::factory()->header()->create(['parent_id' => null]);

    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => $parent->location->value,
        'label' => 'Child Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
        'parent_id' => $parent->id,
        'is_active' => true,
    ]);

    $response->assertSessionHasNoErrors();

    expect(MenuItem::query()->count())->toBe(2);

    $childItem = MenuItem::query()->where('parent_id', $parent->id)->first();

    $response->assertRedirect(route('admin.storefront.menu-items.edit', $childItem));

    expect($childItem->parent_id)->toBe($parent->id);
});

test('validates location is required', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'label' => 'Test Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
    ]);

    $response->assertRedirect()
        ->assertInvalid('location');
});

test('validates label is required', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
    ]);

    $response->assertRedirect()
        ->assertInvalid('label');
});

test('validates label maximum length', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => str_repeat('a', 101),
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
    ]);

    $response->assertRedirect()
        ->assertInvalid('label');
});

test('validates link_type is required', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Test Item',
        'url' => 'https://example.com',
    ]);

    $response->assertRedirect()
        ->assertInvalid('link_type');
});

test('validates category_id is required when link_type is category', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Test Item',
        'link_type' => MenuItemLinkType::Category->value,
    ]);

    $response->assertRedirect()
        ->assertInvalid('category_id');
});

test('validates category_id must exist', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Test Item',
        'link_type' => MenuItemLinkType::Category->value,
        'category_id' => 99999,
    ]);

    $response->assertRedirect()
        ->assertInvalid('category_id');
});

test('validates url is required when link_type is custom', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Test Item',
        'link_type' => MenuItemLinkType::Custom->value,
    ]);

    $response->assertRedirect()
        ->assertInvalid('url');
});

test('validates url maximum length', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Test Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => str_repeat('a', 256),
    ]);

    $response->assertRedirect()
        ->assertInvalid('url');
});

test('validates target must be valid value', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Test Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
        'target' => 'invalid',
    ]);

    $response->assertRedirect()
        ->assertInvalid('target');
});

test('validates parent_id must exist', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Test Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
        'parent_id' => 99999,
    ]);

    $response->assertRedirect()
        ->assertInvalid('parent_id');
});

test('validates parent_id depth with MenuItemMaxDepthRule', function () {
    $root = MenuItem::factory()->create();
    $level2 = MenuItem::factory()->create(['parent_id' => $root->id]);
    $level3 = MenuItem::factory()->create(['parent_id' => $level2->id]);

    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Test Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
        'parent_id' => $level3->id,
    ]);

    $response->assertRedirect()
        ->assertInvalid('parent_id');
});

test('requires authentication', function () {
    $response = post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Test Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Test Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
    ]);

    $menuItem = MenuItem::query()->first();
    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem));

    $role->revokePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Another Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
    ]);

    $response->assertForbidden();
});

test('requires categories view permission for category link type', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontUpdate);
    $role->revokePermissionTo(Permission::CategoriesView);

    $category = Category::factory()->create();

    $response = actingAsAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Category Link',
        'link_type' => MenuItemLinkType::Category->value,
        'category_id' => $category->id,
    ]);

    $response->assertForbidden();

    $role->givePermissionTo(Permission::CategoriesView);

    $response = actingAsAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Category Link',
        'link_type' => MenuItemLinkType::Category->value,
        'category_id' => $category->id,
    ]);

    $menuItem = MenuItem::query()->first();
    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem));
});

test('requires storefront update permission to access create form', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->get(route('admin.storefront.menu-items.create'));

    $response->assertOk();

    $role->revokePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->get(route('admin.storefront.menu-items.create'));

    $response->assertForbidden();
});

test('redirects to edit page when creating header menu item', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Header Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
        'is_active' => true,
    ]);

    $menuItem = MenuItem::query()->first();
    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem));
});

test('redirects to edit page when creating footer menu item', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Footer->value,
        'label' => 'Footer Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
        'is_active' => true,
    ]);

    $menuItem = MenuItem::query()->first();
    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem));
});

test('creates menu item with is_mega_menu enabled', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Mega Menu Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
        'is_active' => true,
        'is_mega_menu' => true,
    ]);

    $response->assertSessionHasNoErrors();

    $menuItem = MenuItem::query()->first();

    expect($menuItem)
        ->is_mega_menu->toBeTrue();
});

test('creates menu item with is_mega_menu disabled by default', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Regular Menu Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
        'is_active' => true,
    ]);

    $response->assertSessionHasNoErrors();

    $menuItem = MenuItem::query()->first();

    expect($menuItem)
        ->is_mega_menu->toBeFalse();
});

test('creates menu item with featured_image', function () {
    $media = Media::factory()->create([
        'path' => 'images/menu/featured.jpg',
        'external_url' => null,
    ]);

    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Menu With Image',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
        'is_active' => true,
        'is_mega_menu' => true,
        'featured_image_id' => $media->id,
    ]);

    $response->assertSessionHasNoErrors();

    $menuItem = MenuItem::query()->first();

    expect($menuItem)
        ->featuredImage?->url->toBe(Storage::disk('public')->url('images/menu/featured.jpg'));
});

test('creates menu item with featured_image as null by default', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Menu Without Image',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
        'is_active' => true,
    ]);

    $response->assertSessionHasNoErrors();

    $menuItem = MenuItem::query()->first();

    expect($menuItem)
        ->featuredImage->toBeNull();
});

test('creates menu item with featured title and link', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Mega Menu',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
        'is_active' => true,
        'is_mega_menu' => true,
        'featured_title' => 'New season audio',
        'featured_url' => '/collections/audio',
    ]);

    $response->assertSessionHasNoErrors();

    $menuItem = MenuItem::query()->first();

    expect($menuItem)
        ->featured_title->toBe('New season audio')
        ->featured_url->toBe('/collections/audio');

    assertDatabaseHas('menu_items', [
        'id' => $menuItem->id,
        'featured_title' => json_encode(['en' => 'New season audio']),
        'featured_url' => '/collections/audio',
    ]);
});

test('validates featured_title maximum length', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Mega Menu',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
        'featured_title' => str_repeat('a', 151),
    ]);

    $response->assertRedirect()
        ->assertInvalid('featured_title');
});

test('creates menu item with page link successfully', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'All Products',
        'link_type' => MenuItemLinkType::Page->value,
        'page' => MenuPage::Products->value,
        'target' => '_self',
        'is_active' => true,
    ]);

    $response->assertSessionHasNoErrors();

    expect(MenuItem::query()->count())->toBe(1);

    $menuItem = MenuItem::query()->first();

    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem));

    expect($menuItem)
        ->location->toBe(MenuLocation::Header)
        ->label->toBe('All Products')
        ->link_type->toBe(MenuItemLinkType::Page)
        ->page->toBe(MenuPage::Products)
        ->is_active->toBeTrue();
});

test('validates page is required when link_type is page', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Test Item',
        'link_type' => MenuItemLinkType::Page->value,
    ]);

    $response->assertRedirect()
        ->assertInvalid('page');
});

test('validates page must be valid enum value', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Test Item',
        'link_type' => MenuItemLinkType::Page->value,
        'page' => 'invalid-page',
    ]);

    $response->assertRedirect()
        ->assertInvalid('page');
});

test('creates menu item with brand link successfully', function () {
    $brand = Brand::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Nike',
        'link_type' => MenuItemLinkType::Brand->value,
        'brand_id' => $brand->id,
        'target' => '_self',
        'is_active' => true,
    ]);

    $response->assertSessionHasNoErrors();

    $menuItem = MenuItem::query()->first();

    expect($menuItem)
        ->link_type->toBe(MenuItemLinkType::Brand)
        ->brand_id->toBe($brand->id);
});

test('validates brand_id is required when link_type is brand', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Test Item',
        'link_type' => MenuItemLinkType::Brand->value,
    ]);

    $response->assertRedirect()
        ->assertInvalid('brand_id');
});

test('validates brand_id must exist', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Test Item',
        'link_type' => MenuItemLinkType::Brand->value,
        'brand_id' => 99999,
    ]);

    $response->assertRedirect()
        ->assertInvalid('brand_id');
});

test('requires brands view permission for brand link type', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontUpdate);
    $role->revokePermissionTo(Permission::BrandsView);

    $brand = Brand::factory()->create();

    $response = actingAsAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Brand Link',
        'link_type' => MenuItemLinkType::Brand->value,
        'brand_id' => $brand->id,
    ]);

    $response->assertForbidden();

    $role->givePermissionTo(Permission::BrandsView);

    $response = actingAsAdmin()->post(route('admin.storefront.menu-items.store'), [
        'location' => MenuLocation::Header->value,
        'label' => 'Brand Link',
        'link_type' => MenuItemLinkType::Brand->value,
        'brand_id' => $brand->id,
    ]);

    $menuItem = MenuItem::query()->first();
    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem));
});
