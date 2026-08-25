<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdminThemeColor;
use App\Enums\Appearance;
use App\Enums\FulfillmentStatus;
use App\Notifications\CustomerVerifyEmailNotification;
use App\Notifications\ResetPasswordNotification;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\PasskeyAuthenticatable;
use Override;
use SensitiveParameter;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string|null $url_handle
 * @property-read string $email
 * @property-read string $password
 * @property-read string|null $two_factor_secret
 * @property-read list<string>|null $two_factor_recovery_codes
 * @property-read CarbonInterface|null $two_factor_confirmed_at
 * @property-read CarbonInterface|null $email_verified_at
 * @property-read string|null $last_login_ip
 * @property-read CarbonInterface|null $last_login_at
 * @property-read string $lifetime_value
 * @property-read Appearance $appearance
 * @property-read AdminThemeColor $admin_theme_color
 * @property-read string|null $remember_token
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read Collection<int, Role> $roles
 * @property-read Collection<int, Permission> $permissions
 * @property-read Collection<int, CustomerAddress> $addresses
 * @property-read Collection<int, Order> $orders
 * @property-read Collection<int, Passkey> $passkeys
 * @property-read Wishlist|null $wishlist
 * @property-read int $order_count
 * @property-read CarbonInterface|null $last_fulfilled_order_date
 */
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
#[UseFactory(UserFactory::class)]
final class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use HasPermissions;
    use HasRoles;
    use Notifiable;
    use PasskeyAuthenticatable;

    #[Override]
    public function casts(): array
    {
        return [
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'lifetime_value' => 'decimal:4',
            'appearance' => Appearance::class,
            'admin_theme_color' => AdminThemeColor::class,
        ];
    }

    #[Override]
    public function sendPasswordResetNotification(#[SensitiveParameter] mixed $token): void
    {
        $this->notify(new ResetPasswordNotification((string) $token));
    }

    #[Override]
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new CustomerVerifyEmailNotification);
    }

    public function hasTwoFactorSecret(): bool
    {
        return ! empty($this->two_factor_secret);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->hasTwoFactorSecret() && $this->two_factor_confirmed_at;
    }

    public function hasPendingTwoFactorConfirmation(): bool
    {
        return $this->hasTwoFactorSecret() && ! $this->two_factor_confirmed_at;
    }

    /**
     * @return HasMany<CustomerAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /**
     * @return HasOne<Wishlist, $this>
     */
    public function wishlist(): HasOne
    {
        return $this->hasOne(Wishlist::class, 'customer_id');
    }

    /**
     * @return Attribute<int<0, max>, never>
     */
    protected function orderCount(): Attribute
    {
        return Attribute::get(fn (): int => $this->orders()->count());
    }

    /**
     * @return Attribute<CarbonInterface|null, never>
     */
    protected function lastFulfilledOrderDate(): Attribute
    {
        return Attribute::get(fn (): ?CarbonInterface => $this->orders()
            ->select('created_at')
            ->where('fulfillment_status', FulfillmentStatus::Fulfilled)
            ->latest()
            ->value('created_at'));
    }
}
