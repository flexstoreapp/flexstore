<?php

declare(strict_types=1);

use App\Actions\BulkDestroyProductAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\BulkProductController;
use App\Http\Requests\Admin\BulkDestroyProductRequest;
use App\Models\Product;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;

covers(BulkProductController::class, BulkDestroyProductRequest::class, BulkDestroyProductAction::class);

uses()->group('product');

test('bulk deletes products', function () {
    $products = Product::factory(3)->create();
    $ids = $products->pluck('id')->all();

    $response = actingAsSuperAdmin()->delete(route('admin.products.bulk.destroy'), [
        'ids' => $ids,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    foreach ($ids as $id) {
        assertDatabaseMissing('products', ['id' => $id]);
    }
});

test('requires at least one product id', function () {
    $response = actingAsSuperAdmin()->delete(route('admin.products.bulk.destroy'), [
        'ids' => [],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('ids');
});

test('validates that ids exists', function () {
    $product = Product::factory()->create();
    $nonExistentId = 9999;

    $response = actingAsSuperAdmin()->delete(route('admin.products.bulk.destroy'), [
        'ids' => [$product->id, $nonExistentId],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('ids');
});

test('requires authentication', function () {
    $products = Product::factory()->count(2)->create();

    $response = delete(route('admin.products.bulk.destroy'), [
        'ids' => $products->pluck('id')->all(),
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires products.delete permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $products = Product::factory()->count(2)->create();

    $response = actingAsAdmin()->delete(route('admin.products.bulk.destroy'), [
        'ids' => $products->pluck('id')->all(),
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    foreach ($products as $product) {
        assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    $role->revokePermissionTo(Permission::ProductsDelete);

    $moreProducts = Product::factory()->count(2)->create();

    $response = actingAsAdmin()->delete(route('admin.products.bulk.destroy'), [
        'ids' => $moreProducts->pluck('id')->all(),
    ]);

    $response->assertForbidden();

    foreach ($moreProducts as $product) {
        assertDatabaseHas('products', [
            'id' => $product->id,
        ]);
    }
});
