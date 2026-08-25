<?php

declare(strict_types=1);

use App\Actions\UpdateMenuItemAction;
use App\Enums\MenuItemLinkType;
use App\Enums\MenuLocation;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Requests\Admin\UpdateMenuItemRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Media;
use App\Models\MenuItem;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;
use function Pest\Laravel\patch;

covers(MenuItemController::class, UpdateMenuItemRequest::class, UpdateMenuItemAction::class);

uses()->group('menu-item');

test('shows edit form with menu item data', function () {
    $menuItem = MenuItem::factory()->create([
        'label' => 'Test Menu Item',
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.storefront.menu-items.edit', $menuItem));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/storefront/menu-items/edit')
            ->has('menuItem')
            ->where('menuItem.id', $menuItem->id)
            ->where('location', $menuItem->location->value));
});

test('shows edit form with category data when link type is category', function () {
    $category = Category::factory()->create(['name' => 'Electronics']);
    $menuItem = MenuItem::factory()->create([
        'link_type' => MenuItemLinkType::Category,
        'category_id' => $category->id,
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.storefront.menu-items.edit', $menuItem));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/storefront/menu-items/edit')
            ->has('menuItem.category')
            ->where('menuItem.category.id', $category->id));
});

test('updates menu item successfully', function () {
    $category = Category::factory()->create();
    $menuItem = MenuItem::factory()->header()->create([
        'label' => 'Old Label',
        'link_type' => MenuItemLinkType::Custom,
        'url' => 'https://old.com',
    ]);

    $response = actingAsSuperAdmin()
        ->from(route('admin.storefront.menu-items.edit', $menuItem))
        ->patch(route('admin.storefront.menu-items.update', $menuItem), [
            'label' => 'New Label',
            'link_type' => MenuItemLinkType::Category->value,
            'category_id' => $category->id,
            'url' => null,
            'target' => '_blank',
        ]);

    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem))
        ->assertSessionHasNoErrors();

    $menuItem->refresh();
    expect($menuItem->label)->toBe('New Label')
        ->and($menuItem->link_type)->toBe(MenuItemLinkType::Category)
        ->and($menuItem->category_id)->toBe($category->id)
        ->and($menuItem->target)->toBe('_blank');
});

test('updates only label', function () {
    $menuItem = MenuItem::factory()->header()->create([
        'label' => 'Old Label',
        'url' => 'https://example.com',
    ]);

    $response = actingAsSuperAdmin()
        ->from(route('admin.storefront.menu-items.edit', $menuItem))
        ->patch(route('admin.storefront.menu-items.update', $menuItem), [
            'label' => 'New Label',
        ]);

    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem))
        ->assertSessionHasNoErrors();

    $menuItem->refresh();
    expect($menuItem->label)->toBe('New Label')
        ->and($menuItem->url)->toBe('https://example.com');
});

test('updates is_active status', function () {
    $menuItem = MenuItem::factory()->header()->active()->create();

    $response = actingAsSuperAdmin()
        ->from(route('admin.storefront.menu-items.edit', $menuItem))
        ->patch(route('admin.storefront.menu-items.update', $menuItem), [
            'is_active' => false,
        ]);

    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem))
        ->assertSessionHasNoErrors();

    $menuItem->refresh();
    expect($menuItem->is_active)->toBeFalse();
});

test('updates parent_id', function () {
    $parent = MenuItem::factory()->header()->create();
    $menuItem = MenuItem::factory()->header()->create(['parent_id' => null]);

    $response = actingAsSuperAdmin()
        ->from(route('admin.storefront.menu-items.edit', $menuItem))
        ->patch(route('admin.storefront.menu-items.update', $menuItem), [
            'parent_id' => $parent->id,
        ]);

    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem))
        ->assertSessionHasNoErrors();

    $menuItem->refresh();
    expect($menuItem->parent_id)->toBe($parent->id);
});

test('validates label maximum length', function () {
    $menuItem = MenuItem::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.menu-items.update', $menuItem), [
        'label' => str_repeat('a', 101),
    ]);

    $response->assertRedirect()
        ->assertInvalid('label');
});

test('validates category_id is required when link_type is category', function () {
    $menuItem = MenuItem::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.menu-items.update', $menuItem), [
        'link_type' => MenuItemLinkType::Category->value,
        'category_id' => null,
    ]);

    $response->assertRedirect()
        ->assertInvalid('category_id');
});

test('validates url is required when link_type is custom', function () {
    $menuItem = MenuItem::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.menu-items.update', $menuItem), [
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => null,
    ]);

    $response->assertRedirect()
        ->assertInvalid('url');
});

test('validates parent_id cannot be itself', function () {
    $menuItem = MenuItem::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.menu-items.update', $menuItem), [
        'parent_id' => $menuItem->id,
    ]);

    $response->assertRedirect()
        ->assertInvalid('parent_id');
});

test('validates parent_id depth with MenuItemMaxDepthRule', function () {
    $root = MenuItem::factory()->create();
    $level2 = MenuItem::factory()->create(['parent_id' => $root->id]);
    $level3 = MenuItem::factory()->create(['parent_id' => $level2->id]);
    $menuItem = MenuItem::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.menu-items.update', $menuItem), [
        'parent_id' => $level3->id,
    ]);

    $response->assertRedirect()
        ->assertInvalid('parent_id');
});

test('validates target must be valid value', function () {
    $menuItem = MenuItem::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.menu-items.update', $menuItem), [
        'target' => 'invalid',
    ]);

    $response->assertRedirect()
        ->assertInvalid('target');
});

test('validates sort_order must be non-negative', function () {
    $menuItem = MenuItem::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.menu-items.update', $menuItem), [
        'sort_order' => -1,
    ]);

    $response->assertRedirect()
        ->assertInvalid('sort_order');
});

test('requires authentication to view edit form', function () {
    $menuItem = MenuItem::factory()->create();

    $response = get(route('admin.storefront.menu-items.edit', $menuItem));

    $response->assertRedirect(route('admin.login'));
});

test('requires authentication to update', function () {
    $menuItem = MenuItem::factory()->create();

    $response = patch(route('admin.storefront.menu-items.update', $menuItem), [
        'label' => 'New Label',
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront update permission to view edit form', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontUpdate);

    $menuItem = MenuItem::factory()->create();

    $response = actingAsAdmin()->get(route('admin.storefront.menu-items.edit', $menuItem));

    $response->assertOk();

    $role->revokePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->get(route('admin.storefront.menu-items.edit', $menuItem));

    $response->assertForbidden();
});

test('requires storefront update permission to update', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontUpdate);

    $menuItem = MenuItem::factory()->header()->create();

    $response = actingAsAdmin()
        ->from(route('admin.storefront.menu-items.edit', $menuItem))
        ->patch(route('admin.storefront.menu-items.update', $menuItem), [
            'label' => 'New Label',
        ]);

    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem));

    $role->revokePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->patch(route('admin.storefront.menu-items.update', $menuItem), [
        'label' => 'Another Label',
    ]);

    $response->assertForbidden();
});

test('requires categories view permission to update to category link type', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontUpdate);
    $role->revokePermissionTo(Permission::CategoriesView);

    $menuItem = MenuItem::factory()->header()->create();
    $category = Category::factory()->create();

    $response = actingAsAdmin()->patch(route('admin.storefront.menu-items.update', $menuItem), [
        'link_type' => MenuItemLinkType::Category->value,
        'category_id' => $category->id,
    ]);

    $response->assertForbidden();

    $role->givePermissionTo(Permission::CategoriesView);

    $response = actingAsAdmin()
        ->from(route('admin.storefront.menu-items.edit', $menuItem))
        ->patch(route('admin.storefront.menu-items.update', $menuItem), [
            'link_type' => MenuItemLinkType::Category->value,
            'category_id' => $category->id,
        ]);

    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem));
});

test('redirects back when updating menu item', function () {
    $menuItem = MenuItem::factory()->create([
        'location' => MenuLocation::Header,
    ]);

    $response = actingAsSuperAdmin()
        ->from(route('admin.storefront.menu-items.edit', $menuItem))
        ->patch(route('admin.storefront.menu-items.update', $menuItem), [
            'label' => 'Updated Label',
        ]);

    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem));
});

test('updates is_mega_menu setting', function () {
    $menuItem = MenuItem::factory()->header()->create(['is_mega_menu' => false]);

    $response = actingAsSuperAdmin()
        ->from(route('admin.storefront.menu-items.edit', $menuItem))
        ->patch(route('admin.storefront.menu-items.update', $menuItem), [
            'is_mega_menu' => true,
        ]);

    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem))
        ->assertSessionHasNoErrors();

    $menuItem->refresh();
    expect($menuItem->is_mega_menu)->toBeTrue();
});

test('disables is_mega_menu setting', function () {
    $menuItem = MenuItem::factory()->header()->megaMenu()->create();

    $response = actingAsSuperAdmin()
        ->from(route('admin.storefront.menu-items.edit', $menuItem))
        ->patch(route('admin.storefront.menu-items.update', $menuItem), [
            'is_mega_menu' => false,
        ]);

    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem))
        ->assertSessionHasNoErrors();

    $menuItem->refresh();
    expect($menuItem->is_mega_menu)->toBeFalse();
});

test('updates featured_image', function () {
    $menuItem = MenuItem::factory()->header()->megaMenu()->create();
    $media = Media::factory()->create([
        'path' => 'images/menu/new-featured.jpg',
        'external_url' => null,
    ]);

    $response = actingAsSuperAdmin()
        ->from(route('admin.storefront.menu-items.edit', $menuItem))
        ->patch(route('admin.storefront.menu-items.update', $menuItem), [
            'featured_image_id' => $media->id,
        ]);

    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem))
        ->assertSessionHasNoErrors();

    $menuItem->refresh();
    expect($menuItem->featuredImage?->url)->toBe(Storage::disk('public')->url('images/menu/new-featured.jpg'));
});

test('clears featured_image', function () {
    $media = Media::factory()->create();
    $menuItem = MenuItem::factory()->header()->megaMenu()->create(['featured_image_id' => $media->id]);

    $response = actingAsSuperAdmin()
        ->from(route('admin.storefront.menu-items.edit', $menuItem))
        ->patch(route('admin.storefront.menu-items.update', $menuItem), [
            'featured_image_id' => null,
        ]);

    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem))
        ->assertSessionHasNoErrors();

    $menuItem->refresh();
    expect($menuItem->featuredImage)->toBeNull();
});

test('updates featured title and link', function () {
    $menuItem = MenuItem::factory()->header()->megaMenu()->create();

    $response = actingAsSuperAdmin()
        ->from(route('admin.storefront.menu-items.edit', $menuItem))
        ->patch(route('admin.storefront.menu-items.update', $menuItem), [
            'featured_title' => 'Winter drop',
            'featured_url' => '/collections/winter',
        ]);

    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem))
        ->assertSessionHasNoErrors();

    $menuItem->refresh();
    expect($menuItem->featured_title)->toBe('Winter drop')
        ->and($menuItem->featured_url)->toBe('/collections/winter');
});

test('leaves featured title and link unchanged when not provided', function () {
    $menuItem = MenuItem::factory()->header()->megaMenu()->create([
        'featured_title' => ['en' => 'Original title'],
        'featured_url' => '/original',
    ]);

    $response = actingAsSuperAdmin()
        ->from(route('admin.storefront.menu-items.edit', $menuItem))
        ->patch(route('admin.storefront.menu-items.update', $menuItem), [
            'label' => 'Updated Label',
        ]);

    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem))
        ->assertSessionHasNoErrors();

    $menuItem->refresh();
    expect($menuItem->featured_title)->toBe('Original title')
        ->and($menuItem->featured_url)->toBe('/original');
});

test('shows edit form with brand data when link type is brand', function () {
    $brand = Brand::factory()->create();
    $menuItem = MenuItem::factory()->create([
        'link_type' => MenuItemLinkType::Brand,
        'brand_id' => $brand->id,
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.storefront.menu-items.edit', $menuItem));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/storefront/menu-items/edit')
            ->has('menuItem.brand')
            ->where('menuItem.brand.id', $brand->id));
});

test('updates menu item to brand link type', function () {
    $brand = Brand::factory()->create();
    $menuItem = MenuItem::factory()->header()->create([
        'link_type' => MenuItemLinkType::Custom,
        'url' => 'https://old.com',
    ]);

    $response = actingAsSuperAdmin()
        ->from(route('admin.storefront.menu-items.edit', $menuItem))
        ->patch(route('admin.storefront.menu-items.update', $menuItem), [
            'label' => 'Nike',
            'link_type' => MenuItemLinkType::Brand->value,
            'brand_id' => $brand->id,
        ]);

    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem))
        ->assertSessionHasNoErrors();

    $menuItem->refresh();
    expect($menuItem->link_type)->toBe(MenuItemLinkType::Brand)
        ->and($menuItem->brand_id)->toBe($brand->id);
});

test('validates brand_id is required when link_type is brand', function () {
    $menuItem = MenuItem::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.menu-items.update', $menuItem), [
        'link_type' => MenuItemLinkType::Brand->value,
        'brand_id' => null,
    ]);

    $response->assertRedirect()
        ->assertInvalid('brand_id');
});

test('requires brands view permission to update to brand link type', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontUpdate);
    $role->revokePermissionTo(Permission::BrandsView);

    $menuItem = MenuItem::factory()->header()->create();
    $brand = Brand::factory()->create();

    $response = actingAsAdmin()->patch(route('admin.storefront.menu-items.update', $menuItem), [
        'link_type' => MenuItemLinkType::Brand->value,
        'brand_id' => $brand->id,
    ]);

    $response->assertForbidden();

    $role->givePermissionTo(Permission::BrandsView);

    $response = actingAsAdmin()
        ->from(route('admin.storefront.menu-items.edit', $menuItem))
        ->patch(route('admin.storefront.menu-items.update', $menuItem), [
            'link_type' => MenuItemLinkType::Brand->value,
            'brand_id' => $brand->id,
        ]);

    $response->assertRedirect(route('admin.storefront.menu-items.edit', $menuItem));
});
