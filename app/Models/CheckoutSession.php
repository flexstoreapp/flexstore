<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CheckoutSessionStatus;
use App\Enums\CheckoutStep;
use Database\Factories\CheckoutSessionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * @property-read string $id
 * @property-read ?string $cart_id
 * @property-read int|null $customer_id
 * @property-read string $customer_email
 * @property-read array<string, mixed>|null $shipping_address
 * @property-read array<string, mixed>|null $billing_address
 * @property-read int|null $customer_address_id
 * @property-read bool $different_billing_address
 * @property-read int|null $shipping_rate_id
 * @property-read int|null $shipping_carrier_id
 * @property-read array<string, string>|null $shipping_carrier_name
 * @property-read string $shipping_carrier_label
 * @property-read array<string, string>|null $shipping_rate_name
 * @property-read int|null $region_id
 * @property-read array<string, string>|null $region_name
 * @property-read int|null $payment_gateway_id
 * @property-read array<string, string>|null $payment_gateway_name
 * @property-read int|null $coupon_id
 * @property-read string|null $coupon_code
 * @property-read string|null $notes
 * @property-read list<array<string, mixed>>|null $items
 * @property-read int $total_quantity
 * @property-read string|null $currency_code
 * @property-read string $exchange_rate
 * @property-read bool|null $prices_include_tax
 * @property-read bool|null $shipping_is_taxable
 * @property-read string|null $tax_based_on
 * @property-read string|null $default_tax_rate
 * @property-read string|null $tax_store_country_code
 * @property-read string|null $tax_store_state
 * @property-read string|null $tax_store_postal_code
 * @property-read string $subtotal
 * @property-read string $tax_total
 * @property-read string $shipping_total
 * @property-read string $discount_total
 * @property-read string $total
 * @property-read int|null $order_id
 * @property-read CheckoutSessionStatus $status
 * @property-read CheckoutStep|null $step
 * @property-read \Carbon\Carbon|null $expires_at
 * @property-read \Carbon\Carbon|null $completed_at
 * @property-read \Carbon\Carbon|null $recovery_email_sent_at
 * @property-read int $recovery_email_sent_count
 * @property-read \Carbon\Carbon|null $recovery_clicked_at
 * @property-read bool $was_recovered
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 * @property-read ?Cart $cart
 * @property-read User|null $customer
 * @property-read PaymentGateway|null $paymentGateway
 * @property-read Coupon|null $coupon
 * @property-read Order|null $order
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StockReservation> $reservations
 */
#[Translatable('shipping_carrier_name', 'shipping_rate_name', 'region_name', 'payment_gateway_name')]
#[UseFactory(CheckoutSessionFactory::class)]
final class CheckoutSession extends Model
{
    /** @use HasFactory<\Database\Factories\CheckoutSessionFactory> */
    use HasFactory;

    use HasTranslations;
    use HasUuids;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateReplacingTranslations(array $attributes): self
    {
        foreach ($this->getTranslatableAttributes() as $key) {
            if (array_key_exists($key, $attributes)) {
                $this->replaceTranslations($key, is_array($attributes[$key]) ? $attributes[$key] : []);
                unset($attributes[$key]);
            }
        }

        $this->fill($attributes)->save();

        return $this;
    }

    #[Override]
    public function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'different_billing_address' => 'boolean',
            'items' => 'array',
            'total_quantity' => 'integer',
            'prices_include_tax' => 'boolean',
            'shipping_is_taxable' => 'boolean',
            'default_tax_rate' => 'decimal:4',
            'subtotal' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'shipping_total' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'total' => 'decimal:4',
            'exchange_rate' => 'decimal:4',
            'step' => CheckoutStep::class,
            'status' => CheckoutSessionStatus::class,
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'recovery_email_sent_at' => 'datetime',
            'recovery_clicked_at' => 'datetime',
            'recovery_email_sent_count' => 'integer',
        ];
    }

    public function belongsToVisitor(?string $visitorCartId, ?User $visitor): bool
    {
        return $this->cart_id === $visitorCartId
            || ($visitor instanceof User && $this->customer_id === $visitor->id);
    }

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<PaymentGateway, $this>
     */
    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return HasMany<StockReservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function shippingCarrierLabel(): Attribute
    {
        return Attribute::get(
            fn (): string => $this->getTranslation('shipping_carrier_name', app()->getLocale()),
        );
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function wasRecovered(): Attribute
    {
        return Attribute::get(
            fn (): bool => $this->recovery_email_sent_at !== null
                && $this->status === CheckoutSessionStatus::Completed
                && $this->order_id !== null,
        );
    }
}
