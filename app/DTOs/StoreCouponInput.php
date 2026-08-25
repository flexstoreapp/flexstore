<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\CouponType;

final readonly class StoreCouponInput
{
    public function __construct(
        public string $code,
        public CouponType $type,
        public string $value,
        public ?string $minOrderValue,
        public ?string $maximumDiscount,
        public ?int $usageLimit,
        public ?int $usageLimitPerCustomer,
        public bool $isActive,
        public ?string $startsAt,
        public ?string $expiresAt,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $type = $data['type'] instanceof CouponType ? $data['type'] : CouponType::from((string) $data['type']);

        return new self(
            code: (string) $data['code'],
            type: $type,
            value: (string) $data['value'],
            minOrderValue: isset($data['min_order_value']) ? (string) $data['min_order_value'] : null,
            maximumDiscount: isset($data['maximum_discount']) ? (string) $data['maximum_discount'] : null,
            usageLimit: isset($data['usage_limit']) ? (int) $data['usage_limit'] : null,
            usageLimitPerCustomer: isset($data['usage_limit_per_customer']) ? (int) $data['usage_limit_per_customer'] : null,
            isActive: (bool) ($data['is_active'] ?? true),
            startsAt: isset($data['starts_at']) ? (string) $data['starts_at'] : null,
            expiresAt: isset($data['expires_at']) ? (string) $data['expires_at'] : null,
        );
    }
}
