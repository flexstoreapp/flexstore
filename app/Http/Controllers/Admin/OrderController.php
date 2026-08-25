<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\SendCustomerNotificationAction;
use App\Actions\StoreOrderAction;
use App\Actions\UpdateOrderAction;
use App\Enums\DisplayTaxTotals;
use App\Http\Requests\Admin\IndexAdminOrderRequest;
use App\Http\Requests\Admin\StoreOrderRequest;
use App\Http\Requests\Admin\UpdateOrderRequest;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemDownload;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\CustomerOrderUpdatedNotification;
use App\Queries\ActivePaymentGatewayListQuery;
use App\Queries\CustomerStatsByEmailQuery;
use App\Queries\OrderItemBreakdownQuery;
use App\Queries\OrderListQuery;
use App\Queries\OrderShippedQuantitiesQuery;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class OrderController
{
    public function index(IndexAdminOrderRequest $request, OrderListQuery $query): Response
    {
        return Inertia::render('admin/orders/list', [
            'orders' => $query->execute($request->query(), $request->safe()->integer('per_page', 15)),
            'filters' => Inertia::always([
                'query' => $request->validated('query'),
                'fulfillment_status' => $request->validated('fulfillment_status'),
                'payment_status' => $request->validated('payment_status'),
                'cancellation_status' => $request->validated('cancellation_status'),
                'sort' => $request->validated('sort', 'created_at'),
                'direction' => $request->validated('direction', 'desc'),
            ]),
        ]);
    }

    public function create(ActivePaymentGatewayListQuery $paymentGatewayQuery): Response
    {
        return Inertia::render('admin/orders/create', [
            'paymentGateways' => $paymentGatewayQuery->execute(),
            'displayTaxTotals' => DisplayTaxTotals::from(Setting::getValue('display_tax_totals')),
            'storeCountryCode' => Setting::getValue('store_country_code'),
        ]);
    }

    public function store(
        StoreOrderRequest $request,
        #[CurrentUser] User $user,
        StoreOrderAction $action,
    ): RedirectResponse {
        $order = $action->handle($request->toDto(), $user);

        if ($request->safe()->boolean('add_more')) {
            return back();
        }

        return to_route('admin.orders.show', $order);
    }

    public function show(
        Order $order,
        OrderItemBreakdownQuery $itemBreakdownQuery,
        CustomerStatsByEmailQuery $customerStatsQuery,
    ): Response {
        $order->load([
            'items.media:' . Media::displaySelect(),
            'items.product:id,track_stock',
            'items.productVariant:id,track_stock',
            'taxDetails',
            'billingAddress',
            'shippingAddress',
            'paymentGateway',
            'shippingCarrier:id,name,driver',
            'activities' => fn (Relation $query): Relation => $query->latest('created_at')->latest('id'),
            'activities.user:id,name',
            'shipments' => fn (Relation $query): Relation => $query->latest(),
            'shipments.items.orderItem:id,product_title,variant_title,product_sku,quantity',
            'shipments.user:id,name',
            'refunds' => fn (Relation $query): Relation => $query->latest(),
            'refunds.items.orderItem:id,product_title,variant_title',
            'transactions' => fn (Relation $query): Relation => $query->latest()->latest('id'),
            'itemDownloads' => fn (Relation $query): Relation => $query
                ->select([
                    'id', 'order_id', 'order_item_id', 'name', 'original_filename', 'mime_type',
                    'download_count', 'last_downloaded_at',
                ])
                ->latest(),
        ]);

        $order->shippingCarrier?->append('collects_cod');
        $order->itemDownloads->each(function (OrderItemDownload $download) use ($order): void {
            $download->setRelation('order', $order);
            $download->append(['is_available']);
        });

        $breakdown = $itemBreakdownQuery->execute($order);

        return Inertia::render('admin/orders/show', [
            'order' => $order->append([
                'is_voidable', 'is_refundable', 'is_cancellable',
                'has_outstanding_balance', 'has_credit_owed', 'can_collect_cod',
            ]),
            'itemBreakdown' => $breakdown,
            'displayTaxTotals' => DisplayTaxTotals::from(Setting::getValue('display_tax_totals')),
            'canRequestPayment' => $order->has_outstanding_balance && ! $order->usesManualPayment() && ! empty($order->customer_email),
            'canRecordPayment' => $order->has_outstanding_balance,
            'canRefundCredit' => $order->has_credit_owed,
            'customerStats' => $customerStatsQuery->execute($order->customer_email),
        ]);
    }

    public function edit(
        Order $order,
        ActivePaymentGatewayListQuery $paymentGatewayQuery,
        OrderShippedQuantitiesQuery $shippedQuantitiesQuery,
    ): Response {
        $order->load([
            'customer',
            'items.media:' . Media::displaySelect(),
            'items.product:id,track_stock',
            'items.productVariant:id,track_stock',
            'billingAddress',
            'shippingAddress',
            'taxDetails',
        ]);

        $shippedQuantities = $shippedQuantitiesQuery->execute($order);

        $order->items->each(function (OrderItem $item) use ($shippedQuantities): void {
            $item->shipped_quantity = $shippedQuantities[$item->id] ?? 0; // @phpstan-ignore property.notFound
        });

        return Inertia::render('admin/orders/edit', [
            'order' => $order,
            'paymentGateways' => $paymentGatewayQuery->execute(),
            'displayTaxTotals' => DisplayTaxTotals::from(Setting::getValue('display_tax_totals')),
        ]);
    }

    public function update(
        UpdateOrderRequest $request,
        #[CurrentUser] User $user,
        Order $order,
        UpdateOrderAction $action,
        SendCustomerNotificationAction $sendCustomerNotificationAction,
    ): RedirectResponse {
        $action->handle($user, $order, $request->toDto());

        if ($request->safe()->boolean('notify_customer')) {
            $sendCustomerNotificationAction->handle(
                new CustomerOrderUpdatedNotification($order),
                $order->customer,
                $order->customer_email,
            );
        }

        return back();
    }
}
