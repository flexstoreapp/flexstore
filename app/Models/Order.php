<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CancellationReason;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderAddressType;
use App\Enums\PaymentGatewayDriver;
use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Payment\PaymentManager;
use Brick\Math\BigDecimal;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Override;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * @property-read int $id
 * @property-read PaymentStatus $payment_status
 * @property-read FulfillmentStatus $fulfillment_status
 * @property-read int|null $customer_id
 * @property-read User|null $customer
 * @property-read string $customer_email
 * @property-read bool $prices_include_tax
 * @property-read bool $shipping_is_taxable
 * @property-read string $tax_based_on
 * @property-read string|null $default_tax_rate
 * @property-read string|null $tax_store_country_code
 * @property-read string|null $tax_store_state
 * @property-read string|null $tax_store_postal_code
 * @property-read string $subtotal
 * @property-read string $discount_total
 * @property-read string $shipping_total
 * @property-read string $tax_total
 * @property-read string $total
 * @property-read string $paid_total
 * @property-read string $refund_total
 * @property-read string $net_paid_total
 * @property-read string $balance_due_total
 * @property-read string $credit_due_total
 * @property-read bool $has_outstanding_balance
 * @property-read bool $has_credit_owed
 * @property-read bool $can_collect_cod
 * @property-read bool $is_voidable
 * @property-read bool $is_refundable
 * @property-read string $currency_code
 * @property-read string $exchange_rate
 * @property-read int|null $shipping_carrier_id
 * @property-read array<string, string>|null $shipping_carrier_name
 * @property-read string $shipping_carrier_label
 * @property-read int|null $shipping_rate_id
 * @property-read array<string, string>|null $shipping_rate_name
 * @property-read int|null $region_id
 * @property-read array<string, string>|null $region_name
 * @property-read int|null $payment_gateway_id
 * @property-read array<string, string>|null $payment_gateway_name
 * @property-read int|null $coupon_id
 * @property-read Coupon|null $coupon
 * @property-read string|null $coupon_code
 * @property-read string|null $notes
 * @property-read \Carbon\Carbon|null $canceled_at
 * @property-read CancellationReason|null $cancellation_reason
 * @property-read string|null $cancellation_note
 * @property-read bool $is_canceled
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderItem> $items
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderItemDownload> $itemDownloads
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderTaxDetail> $taxDetails
 * @property-read PaymentGateway|null $paymentGateway
 * @property-read ShippingCarrier|null $shippingCarrier
 * @property-read ShippingRate|null $shippingRate
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderAddress> $addresses
 * @property-read OrderAddress|null $billingAddress
 * @property-read OrderAddress|null $shippingAddress
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderActivity> $activities
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderShipment> $shipments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderRefund> $refunds
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderTransaction> $transactions
 * @property-read string|null $revenue
 * @property-read int|null $orders
 * @property-read \Carbon\Carbon|null $first_order_date
 */
#[Translatable('shipping_carrier_name', 'shipping_rate_name', 'region_name', 'payment_gateway_name')]
#[UseFactory(OrderFactory::class)]
final class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    use HasTranslations;

    #[Override]
    public function casts(): array
    {
        return [
            'payment_status' => PaymentStatus::class,
            'fulfillment_status' => FulfillmentStatus::class,
            'prices_include_tax' => 'boolean',
            'shipping_is_taxable' => 'boolean',
            'default_tax_rate' => 'decimal:4',
            'subtotal' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'shipping_total' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'total' => 'decimal:4',
            'paid_total' => 'decimal:4',
            'refund_total' => 'decimal:4',
            'net_paid_total' => 'decimal:4',
            'balance_due_total' => 'decimal:4',
            'credit_due_total' => 'decimal:4',
            'exchange_rate' => 'decimal:4',
            'cancellation_reason' => CancellationReason::class,
            'canceled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<OrderItemDownload, $this>
     */
    public function itemDownloads(): HasMany
    {
        return $this->hasMany(OrderItemDownload::class);
    }

    public function requiresShipping(): bool
    {
        return $this->items->contains(fn (OrderItem $item): bool => $item->requires_shipping);
    }

    /**
     * @return HasMany<OrderTaxDetail, $this>
     */
    public function taxDetails(): HasMany
    {
        return $this->hasMany(OrderTaxDetail::class);
    }

    /**
     * @return BelongsTo<PaymentGateway, $this>
     */
    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    /**
     * @return BelongsTo<ShippingCarrier, $this>
     */
    public function shippingCarrier(): BelongsTo
    {
        return $this->belongsTo(ShippingCarrier::class);
    }

    /**
     * @return BelongsTo<ShippingRate, $this>
     */
    public function shippingRate(): BelongsTo
    {
        return $this->belongsTo(ShippingRate::class);
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * @return HasMany<OrderAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(OrderAddress::class);
    }

    /**
     * @return HasOne<OrderAddress, $this>
     */
    public function shippingAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class, 'order_id')
            ->where('type', OrderAddressType::Shipping);
    }

    /**
     * @return HasOne<OrderAddress, $this>
     */
    public function billingAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class, 'order_id')
            ->where('type', OrderAddressType::Billing);
    }

    /**
     * @return HasMany<OrderActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(OrderActivity::class);
    }

    /**
     * @return HasMany<OrderShipment, $this>
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(OrderShipment::class);
    }

    /**
     * @return HasMany<OrderRefund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(OrderRefund::class);
    }

    /**
     * @return HasMany<OrderTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(OrderTransaction::class);
    }

    /**
     * @return array{string|null, array<string, mixed>|null}
     */
    public function getOriginalPaymentMethod(): array
    {
        $transaction = $this->findFirstSuccessfulPaymentTransaction();

        if (! $transaction instanceof OrderTransaction || $transaction->payment_method === null) {
            return [null, null];
        }

        return [$transaction->payment_method, $transaction->payment_method_details];
    }

    public function usesManualPayment(): bool
    {
        if ($this->paymentGateway === null) {
            return false;
        }

        return resolve(PaymentManager::class)->driver($this->paymentGateway)->isManual();
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function hasOutstandingBalance(): Attribute
    {
        return Attribute::get(fn (): bool => BigDecimal::of($this->balance_due_total)->isPositive());
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function hasCreditOwed(): Attribute
    {
        return Attribute::get(fn (): bool => BigDecimal::of($this->credit_due_total)->isPositive());
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function canCollectCod(): Attribute
    {
        return Attribute::get(fn (): bool => $this->paymentGateway?->driver === PaymentGatewayDriver::Cod
            && ($this->shippingCarrier->collects_cod ?? false)
            && BigDecimal::of($this->balance_due_total)->isPositive());
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function isVoidable(): Attribute
    {
        return Attribute::get(fn (): bool => $this->usesManualPayment() && BigDecimal::of($this->paid_total)->isPositive());
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function isRefundable(): Attribute
    {
        return Attribute::get(fn (): bool => in_array($this->payment_status, [
            PaymentStatus::Paid,
            PaymentStatus::PartiallyPaid,
            PaymentStatus::PartiallyRefunded,
        ], true));
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function isCancellable(): Attribute
    {
        return Attribute::get(fn (): bool => $this->canceled_at === null && $this->fulfillment_status !== FulfillmentStatus::Fulfilled);
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function isCanceled(): Attribute
    {
        return Attribute::get(fn (): bool => $this->canceled_at !== null);
    }

    /**
     * Customer-facing carrier: the aggregator's actual provider (e.g., "USPS") when present,
     * otherwise the configured carrier's translated name.
     *
     * @return Attribute<string, never>
     */
    protected function shippingCarrierLabel(): Attribute
    {
        return Attribute::get(
            fn (): string => $this->getTranslation('shipping_carrier_name', app()->getLocale()),
        );
    }

    /**
     * @return Collection<int, OrderTransaction>
     */
    private function successfulPaymentTransactions(): Collection
    {
        return $this->transactions
            ->filter(fn (OrderTransaction $t): bool => $t->status === TransactionStatus::Success
                && $t->type === TransactionType::Sale);
    }

    private function findFirstSuccessfulPaymentTransaction(): ?OrderTransaction
    {
        return $this->successfulPaymentTransactions()->sortBy('id')->first();
    }
}
