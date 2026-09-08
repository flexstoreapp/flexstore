<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\TokenAbility;
use App\Http\Controllers\Api\V1\Admin;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::post('auth/login', [Admin\AdminAccessTokenController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('auth.login');

    Route::post('auth/two-factor', Admin\AdminTwoFactorChallengeController::class)
        ->middleware(['auth:sanctum', 'ability:' . TokenAbility::TwoFactorPending->value, 'throttle:6,1'])
        ->name('auth.two-factor');

    Route::middleware(['auth:sanctum', 'ability:' . TokenAbility::Admin->value])->group(function (): void {
        Route::post('auth/logout', [Admin\AdminAccessTokenController::class, 'destroy'])->name('auth.logout');
        Route::get('auth/me', Admin\AdminProfileController::class)->name('auth.me');

        Route::get('config', Admin\StoreConfigController::class)->name('config');

        // media
        Route::post('media', [Admin\MediaController::class, 'store'])->middleware(Authorize::using(Permission::MediaUpload))->name('media.store');

        // categories
        Route::get('categories/search', Admin\CategorySearchController::class)->middleware(Authorize::using(Permission::CategoriesReference))->name('categories.search');
        Route::get('categories', [Admin\CategoryController::class, 'index'])->middleware(Authorize::using(Permission::CategoriesView))->name('categories.index');
        Route::post('categories', [Admin\CategoryController::class, 'store'])->middleware(Authorize::using(Permission::CategoriesManage))->name('categories.store');
        Route::get('categories/{category}', [Admin\CategoryController::class, 'show'])->middleware(Authorize::using(Permission::CategoriesView))->name('categories.show');
        Route::patch('categories/{category}/reorder', [Admin\CategoryReorderController::class, 'update'])->middleware(Authorize::using(Permission::CategoriesManage))->name('categories.reorder');
        Route::patch('categories/{category}', [Admin\CategoryController::class, 'update'])->middleware(Authorize::using(Permission::CategoriesManage))->name('categories.update');
        Route::delete('categories/{category}', [Admin\CategoryController::class, 'destroy'])->middleware(Authorize::using(Permission::CategoriesDelete))->name('categories.destroy');

        // brands
        Route::get('brands/search', Admin\BrandSearchController::class)->middleware(Authorize::using(Permission::BrandsReference))->name('brands.search');
        Route::get('brands', [Admin\BrandController::class, 'index'])->middleware(Authorize::using(Permission::BrandsView))->name('brands.index');
        Route::post('brands', [Admin\BrandController::class, 'store'])->middleware(Authorize::using(Permission::BrandsManage))->name('brands.store');
        Route::delete('brands/bulk', [Admin\BulkBrandController::class, 'destroy'])->middleware(Authorize::using(Permission::BrandsDelete))->name('brands.bulk.destroy');
        Route::get('brands/{brand}', [Admin\BrandController::class, 'show'])->middleware(Authorize::using(Permission::BrandsView))->name('brands.show');
        Route::patch('brands/{brand}', [Admin\BrandController::class, 'update'])->middleware(Authorize::using(Permission::BrandsManage))->name('brands.update');
        Route::delete('brands/{brand}', [Admin\BrandController::class, 'destroy'])->middleware(Authorize::using(Permission::BrandsDelete))->name('brands.destroy');

        // products
        Route::get('products/search', Admin\ProductSearchController::class)->middleware(Authorize::using(Permission::ProductsReference))->name('products.search');
        Route::delete('products/bulk', [Admin\BulkProductController::class, 'destroy'])->middleware(Authorize::using(Permission::ProductsDelete))->name('products.bulk.destroy');
        Route::get('products', [Admin\ProductController::class, 'index'])->middleware(Authorize::using(Permission::ProductsView))->name('products.index');
        Route::post('products', [Admin\ProductController::class, 'store'])->middleware(Authorize::using(Permission::ProductsManage))->name('products.store');
        Route::get('products/{product}', [Admin\ProductController::class, 'show'])->middleware(Authorize::using(Permission::ProductsView))->name('products.show');
        Route::patch('products/{product}', [Admin\ProductController::class, 'update'])->middleware(Authorize::using(Permission::ProductsManage))->name('products.update');
        Route::delete('products/{product}', [Admin\ProductController::class, 'destroy'])->middleware(Authorize::using(Permission::ProductsDelete))->name('products.destroy');
        Route::post('products/{product}/duplicate', [Admin\DuplicateProductController::class, 'store'])->middleware(Authorize::using(Permission::ProductsManage))->name('products.duplicate');
        Route::post('product-downloads', [Admin\DigitalFileController::class, 'store'])->middleware(Authorize::using(Permission::ProductsManage))->name('product-downloads.store');
    });
});
