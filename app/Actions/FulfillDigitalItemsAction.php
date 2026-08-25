<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderActivityType;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemDownload;
use App\Models\Product;
use App\Models\ProductDownload;
use App\StateMachines\FulfillmentStatusMachine;
use Illuminate\Support\Str;

final readonly class FulfillDigitalItemsAction
{
    public function __construct(
        private TransitionFulfillmentStatusAction $transitionFulfillmentStatusAction,
        private StoreOrderActivityAction $storeOrderActivityAction,
    ) {
    }

    public function handle(Order $order): void
    {
        if ($order->payment_status !== PaymentStatus::Paid) {
            return;
        }

        $order->loadMissing('items.product.downloads.media');

        $grantedAny = false;

        foreach ($order->items as $item) {
            $product = $item->product;
            if (! $product instanceof Product) {
                continue;
            }
            if (! $product->isDigital()) {
                continue;
            }

            $applicable = $product->downloads->filter(
                fn (ProductDownload $download): bool => $download->product_variant_id === null
                    || $download->product_variant_id === $item->product_variant_id,
            );

            foreach ($applicable as $download) {
                $this->grantDownload($order, $item, $download);
                $grantedAny = true;
            }
        }

        if ($grantedAny && ! $this->orderRequiresShipping($order)) {
            $this->autoFulfill($order);
        }
    }

    private function grantDownload(Order $order, OrderItem $item, ProductDownload $download): void
    {
        OrderItemDownload::query()->firstOrCreate(
            [
                'order_item_id' => $item->id,
                'product_download_id' => $download->id,
            ],
            [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'media_id' => $download->media_id,
                'token' => Str::random(64),
                'name' => $download->name,
                'file_path' => (string) $download->media->path,
                'original_filename' => (string) $download->media->original_filename,
                'file_size' => $download->media->size ?? 0,
                'mime_type' => $download->media->mime_type,
            ],
        );
    }

    private function orderRequiresShipping(Order $order): bool
    {
        return $order->items->contains(fn (OrderItem $item): bool => $item->requires_shipping);
    }

    private function autoFulfill(Order $order): void
    {
        if (! FulfillmentStatusMachine::canTransition($order->fulfillment_status, FulfillmentStatus::Fulfilled)) {
            return;
        }

        $this->transitionFulfillmentStatusAction->handle($order, FulfillmentStatus::Fulfilled);

        $this->storeOrderActivityAction->handle(
            order: $order,
            type: OrderActivityType::ItemsFulfilled,
        );
    }
}
