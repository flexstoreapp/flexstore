<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Utilities\InputConverter;

final readonly class ProductFilterInput
{
    /**
     * @param  list<int>  $categoryIds
     * @param  list<int>  $brandIds
     */
    public function __construct(
        public ?string $search = null,
        public array $categoryIds = [],
        public array $brandIds = [],
        public ?string $minPrice = null,
        public ?string $maxPrice = null,
        public ?bool $inStock = null,
        public ?bool $onSale = null,
        public ?float $rating = null,
        public ?string $sort = null,
        public ?int $perPage = null,
        public ?int $page = null,
        public ?int $contextCategoryId = null,
        public ?int $contextBrandId = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $converter = new InputConverter();
        $rating = $data['rating'] ?? null;
        $perPage = $data['per_page'] ?? null;
        $page = $data['page'] ?? null;

        return new self(
            search: $converter->toNullableString($data['query'] ?? null),
            categoryIds: $converter->toIdList($data['categories'] ?? null),
            brandIds: $converter->toIdList($data['brands'] ?? null),
            minPrice: $converter->toNullableNumeric($data['min_price'] ?? null),
            maxPrice: $converter->toNullableNumeric($data['max_price'] ?? null),
            inStock: $converter->toNullableBoolean($data['in_stock'] ?? null),
            onSale: $converter->toNullableBoolean($data['on_sale'] ?? null),
            rating: is_numeric($rating) ? (float) $rating : null,
            sort: $converter->toNullableString($data['sort'] ?? null),
            perPage: is_numeric($perPage) ? (int) $perPage : null,
            page: is_numeric($page) ? (int) $page : null,
        );
    }

    public function hasSearch(): bool
    {
        return $this->search !== null && mb_trim($this->search) !== '';
    }

    /**
     * @return array<string, mixed>
     */
    public function toFilters(string $sort): array
    {
        $filters = ['sort' => $sort];

        if ($this->hasSearch()) {
            $filters['query'] = $this->search;
        }
        if ($this->categoryIds !== []) {
            $filters['categories'] = $this->categoryIds;
        }
        if ($this->brandIds !== []) {
            $filters['brands'] = $this->brandIds;
        }
        if ($this->minPrice !== null || $this->maxPrice !== null) {
            $filters['price_range'] = ['min' => $this->minPrice, 'max' => $this->maxPrice];
        }
        if ($this->inStock === true) {
            $filters['in_stock'] = true;
        }
        if ($this->onSale === true) {
            $filters['on_sale'] = true;
        }
        if ($this->rating !== null) {
            $filters['rating'] = $this->rating;
        }

        return $filters;
    }

    public function withContextCategory(int $categoryId): self
    {
        return new self(
            search: $this->search,
            categoryIds: $this->categoryIds,
            brandIds: $this->brandIds,
            minPrice: $this->minPrice,
            maxPrice: $this->maxPrice,
            inStock: $this->inStock,
            onSale: $this->onSale,
            rating: $this->rating,
            sort: $this->sort,
            perPage: $this->perPage,
            page: $this->page,
            contextCategoryId: $categoryId,
            contextBrandId: $this->contextBrandId,
        );
    }

    public function withContextBrand(int $brandId): self
    {
        return new self(
            search: $this->search,
            categoryIds: $this->categoryIds,
            brandIds: $this->brandIds,
            minPrice: $this->minPrice,
            maxPrice: $this->maxPrice,
            inStock: $this->inStock,
            onSale: $this->onSale,
            rating: $this->rating,
            sort: $this->sort,
            perPage: $this->perPage,
            page: $this->page,
            contextCategoryId: $this->contextCategoryId,
            contextBrandId: $brandId,
        );
    }
}
