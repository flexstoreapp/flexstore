<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Enums\ReviewStatus;
use App\Http\Controllers\Storefront\ProductController;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withHeaders;

covers(ProductController::class);

uses()->group('storefront', 'product');

function partialReload(Product $product, string $only): Illuminate\Testing\TestResponse
{
    return withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => app(Inertia\ResponseFactory::class)->getVersion(),
        'X-Inertia-Partial-Component' => 'storefront/products/show',
        'X-Inertia-Partial-Data' => $only,
    ])->get(route('products.show', $product->url_handle));
}

test('renders the product detail page with core props', function () {
    $product = Product::factory()->available()->create();

    get(route('products.show', $product->url_handle))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('storefront/products/show')
            ->where('product.id', $product->id)
            ->has('settings')
            ->where('canReview', false));
});

test('returns 404 for an inactive product', function () {
    $product = Product::factory()->inactive()->create();

    get(route('products.show', $product->url_handle))->assertNotFound();
});

test('canReview is true for a purchaser who has not reviewed', function () {
    $user = User::factory()->create();
    $product = Product::factory()->available()->create();
    $order = Order::factory()->create(['customer_id' => $user->id, 'payment_status' => PaymentStatus::Paid]);
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id]);

    actingAs($user)
        ->get(route('products.show', $product->url_handle))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('canReview', true));
});

test('deferred reviews resolve on a partial reload', function () {
    $product = Product::factory()->available()->create();
    Review::factory()->for($product)->create(['rating' => 5, 'status' => ReviewStatus::Approved]);

    get(route('products.show', $product->url_handle))->assertOk();

    partialReload($product, 'reviews')
        ->assertOk()
        ->assertJsonCount(1, 'props.reviews.data')
        ->assertJsonPath('props.reviews.data.0.rating', 5);
});

test('deferred related products resolve on a partial reload', function () {
    $category = Category::factory()->active()->create();
    $product = Product::factory()->available()->create(['category_id' => $category->id]);
    Product::factory()->available()->count(2)->create(['category_id' => $category->id]);

    get(route('products.show', $product->url_handle))->assertOk();

    partialReload($product, 'relatedProducts')
        ->assertOk()
        ->assertJsonCount(2, 'props.relatedProducts');
});

test('reviews are omitted when the reviews section is disabled', function () {
    Setting::setValue('storefront_product_detail_show_reviews', false);
    $product = Product::factory()->available()->create();

    get(route('products.show', $product->url_handle))
        ->assertInertia(fn (AssertableInertia $page) => $page->missing('reviews'));
});

test('related products are omitted when the section is disabled', function () {
    Setting::setValue('storefront_product_detail_show_related_products', false);
    $product = Product::factory()->available()->create();

    get(route('products.show', $product->url_handle))
        ->assertInertia(fn (AssertableInertia $page) => $page->missing('relatedProducts'));
});
