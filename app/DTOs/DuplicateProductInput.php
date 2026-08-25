<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class DuplicateProductInput
{
    /**
     * @param  array<string, string>|string  $title
     */
    public function __construct(
        public array|string $title,
        public ?string $urlHandle,
        public bool $isActive,
        public bool $duplicateCategory,
        public bool $duplicateBrand,
        public bool $duplicateMedia,
        public bool $duplicatePricing,
        public bool $duplicateTax,
        public bool $duplicateInventory,
        public bool $duplicateSkus,
        public bool $duplicateBarcodes,
        public bool $duplicateShipping,
        public bool $duplicateSeo,
        public bool $duplicateDigitalFiles,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? '',
            urlHandle: isset($data['url_handle']) && $data['url_handle'] !== '' ? (string) $data['url_handle'] : null,
            isActive: (bool) ($data['is_active'] ?? false),
            duplicateCategory: (bool) ($data['duplicate_category'] ?? false),
            duplicateBrand: (bool) ($data['duplicate_brand'] ?? false),
            duplicateMedia: (bool) ($data['duplicate_media'] ?? false),
            duplicatePricing: (bool) ($data['duplicate_pricing'] ?? false),
            duplicateTax: (bool) ($data['duplicate_tax'] ?? false),
            duplicateInventory: (bool) ($data['duplicate_inventory'] ?? false),
            duplicateSkus: (bool) ($data['duplicate_skus'] ?? false),
            duplicateBarcodes: (bool) ($data['duplicate_barcodes'] ?? false),
            duplicateShipping: (bool) ($data['duplicate_shipping'] ?? false),
            duplicateSeo: (bool) ($data['duplicate_seo'] ?? false),
            duplicateDigitalFiles: (bool) ($data['duplicate_digital_files'] ?? false),
        );
    }
}
