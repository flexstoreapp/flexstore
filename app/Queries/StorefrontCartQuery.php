<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\Relation;

final readonly class StorefrontCartQuery
{
    public function execute(Cart $cart): Cart
    {
        $cart->loadMissing([
            'items:id,cart_id,product_id,product_variant_id,quantity,unit_price,compare_at_price,total_price,variant_title',
            'items.product:id,url_handle,title,type,weight,weight_unit',
            'items.product.mediaGallery' => fn (Relation $query): Relation => $query->select(Media::displayColumns())->limit(1),
            'items.productVariant:id,product_id,media_id,weight,weight_unit',
            'items.productVariant.media:' . Media::displaySelect(),
        ]);

        $cart->setAttribute('requires_shipping', $cart->requiresShipping());

        $cart->items->each(function (CartItem $item): void {
            $item->product?->append('featured_media');
            $item->product?->makeHidden(['mediaGallery']);
        });

        return $cart;
    }
}
