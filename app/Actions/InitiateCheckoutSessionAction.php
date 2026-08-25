<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CheckoutSessionStatus;
use App\Enums\CheckoutStep;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CheckoutSession;
use App\Models\Coupon;
use App\Models\Media;
use App\Utilities\OrderUtility;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

final readonly class InitiateCheckoutSessionAction
{
    public function __construct(
        private OrderUtility $orderUtility,
    ) {
    }

    public function handle(string $cartId, string $customerEmail, ?int $customerId = null, ?string $currencyCode = null): CheckoutSession
    {
        $cart = Cart::query()
            ->with([
                'items.product:id,title',
                'items.product.mediaGallery' => fn (Relation $q): Relation => $q->select(Media::displayColumns())->limit(1),
                'items.productVariant:id,product_id,title,media_id',
                'items.productVariant.media:' . Media::displaySelect(),
            ])
            ->find($cartId);

        $snapshot = $cart ? $this->buildItemsSnapshot($cart->items) : ['items' => [], 'subtotal' => '0'];
        $discount = $this->discountFromCart($cart);

        return DB::transaction(function () use ($cartId, $customerEmail, $customerId, $currencyCode, $snapshot, $discount): CheckoutSession {
            $session = CheckoutSession::query()
                ->where('cart_id', $cartId)
                ->where('status', CheckoutSessionStatus::Pending)
                ->lockForUpdate()
                ->latest()
                ->latest('id')
                ->first();

            $payload = [
                'customer_email' => $customerEmail,
                'items' => $snapshot['items'],
                'total_quantity' => array_sum(array_column($snapshot['items'], 'quantity')),
                'subtotal' => $snapshot['subtotal'],
                'coupon_id' => $discount['coupon_id'],
                'coupon_code' => $discount['coupon_code'],
                'discount_total' => $discount['discount_total'],
                'total' => $this->orderUtility->calculateOrderTotal(
                    $snapshot['subtotal'],
                    $session->tax_total ?? '0.0000',
                    $session->shipping_total ?? '0.0000',
                    $discount['discount_total'],
                ),
                ...($customerId !== null ? ['customer_id' => $customerId] : []),
                ...($currencyCode !== null ? ['currency_code' => $currencyCode] : []),
            ];

            if ($session) {
                $session->update($payload);

                return $session;
            }

            return CheckoutSession::query()->create([
                'cart_id' => $cartId,
                'customer_id' => $customerId,
                'status' => CheckoutSessionStatus::Pending,
                'step' => CheckoutStep::ContactInformation,
                ...$payload,
            ]);
        });
    }

    /**
     * @param  Collection<int, CartItem>  $items
     * @return array{items: list<array<string, mixed>>, subtotal: string}
     */
    private function buildItemsSnapshot(Collection $items): array
    {
        $subtotal = BigDecimal::zero();
        $serialized = [];

        foreach ($items as $item) {
            $subtotal = $subtotal->plus(BigDecimal::of($item->total_price));
            $serialized[] = [
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
                'product_title' => $item->product?->getTranslations('title'),
                'variant_title' => $item->variant_title,
                'variant_options' => $item->variant_options,
                'thumbnail_url' => $item->productVariant->media->small_thumbnail_url
                    ?? $item->product->featured_media->small_thumbnail_url
                    ?? null,
            ];
        }

        return ['items' => $serialized, 'subtotal' => $subtotal->toScale(4)->toString()];
    }

    /**
     * @return array{coupon_id: int|null, coupon_code: string|null, discount_total: string}
     */
    private function discountFromCart(?Cart $cart): array
    {
        if (! $cart instanceof Cart || $cart->coupon_code === null || $cart->coupon_code === '') {
            return [
                'coupon_id' => null,
                'coupon_code' => null,
                'discount_total' => '0.0000',
            ];
        }

        return [
            'coupon_id' => Coupon::query()->where('code', $cart->coupon_code)->first(['id'])?->id,
            'coupon_code' => $cart->coupon_code,
            'discount_total' => $cart->discount_total ?? '0.0000',
        ];
    }
}
