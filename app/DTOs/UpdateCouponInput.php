<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\CouponType;

final readonly class UpdateCouponInput
{
    /**
     * @param  array<string, true>  $provided
     */
    public function __construct(
        public ?string $code,
        public ?CouponType $type,
        public ?string $value,
        public ?string $minOrderValue,
        public ?string $maximumDiscount,
        public ?int $usageLimit,
        public ?int $usageLimitPerCustomer,
        public ?bool $isActive,
        public ?string $startsAt,
        public ?string $expiresAt,
        public array $provided,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $keys = [
            'code', 'type', 'value', 'min_order_value', 'maximum_discount',
            'usage_limit', 'usage_limit_per_customer', 'is_active', 'starts_at', 'expires_at',
        ];
        $provided = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $provided[$key] = true;
            }
        }

        $type = null;
        if (isset($data['type'])) {
            $type = $data['type'] instanceof CouponType ? $data['type'] : CouponType::from((string) $data['type']);
        }

        return new self(
            code: isset($data['code']) ? (string) $data['code'] : null,
            type: $type,
            value: isset($data['value']) ? (string) $data['value'] : null,
            minOrderValue: isset($data['min_order_value']) ? (string) $data['min_order_value'] : null,
            maximumDiscount: isset($data['maximum_discount']) ? (string) $data['maximum_discount'] : null,
            usageLimit: isset($data['usage_limit']) ? (int) $data['usage_limit'] : null,
            usageLimitPerCustomer: isset($data['usage_limit_per_customer']) ? (int) $data['usage_limit_per_customer'] : null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            startsAt: isset($data['starts_at']) ? (string) $data['starts_at'] : null,
            expiresAt: isset($data['expires_at']) ? (string) $data['expires_at'] : null,
            provided: $provided,
        );
    }

    public function has(string $field): bool
    {
        return isset($this->provided[$field]);
    }
}
