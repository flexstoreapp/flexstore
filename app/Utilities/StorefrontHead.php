<?php

declare(strict_types=1);

namespace App\Utilities;

use App\Enums\SettingGroup;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Media;
use App\Models\Setting;
use Laravel\Head\Enums\OfferAvailability;
use Laravel\Head\Enums\OgType;
use Laravel\Head\Facades\Head;
use Laravel\Head\Facades\Schema;
use Laravel\Head\Schema\Breadcrumbs;
use Laravel\Head\Schema\SchemaObject;

final readonly class StorefrontHead
{
    public static function page(string $title, ?string $description = null, bool $exactTitle = false, OgType|string|null $type = null): void
    {
        $head = Head::title($title, exact: $exactTitle);

        if (self::filled($description)) {
            $head->description($description);
        }

        if ($type !== null) {
            $head->og(type: $type);
        }
    }

    public static function homepage(): void
    {
        $title = (string) Setting::getValue('seo_homepage_meta_title');
        $description = (string) Setting::getValue('seo_homepage_meta_description');

        if (self::filled($title)) {
            Head::title($title, exact: true);
        }

        if (self::filled($description)) {
            Head::description($description);
        }
    }

    public static function shop(): void
    {
        $title = (string) Setting::getValue('seo_shop_meta_title');
        $description = (string) Setting::getValue('seo_shop_meta_description');

        self::page(
            title: self::resolvedTitle($title, __('Shop')),
            description: self::firstFilled($description),
            exactTitle: self::filled($title),
        );
    }

    public static function search(string $query): void
    {
        self::page(
            self::filled($query) ? __('Search results for ":query"', ['query' => $query]) : __('Search'),
        );
    }

    public static function category(Category $category): void
    {
        self::resource($category->seo_title, $category->name, $category->seo_description, $category->description);
    }

    public static function brand(Brand $brand): void
    {
        self::resource($brand->seo_title, $brand->name, $brand->seo_description, $brand->description);
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    public static function product(array $detail): void
    {
        $title = self::resolvedTitle(
            self::translated($detail['seo_title'] ?? null),
            self::translated($detail['title'] ?? null),
        );
        $description = self::resolvedDescription(
            self::translated($detail['seo_description'] ?? null),
            self::translated($detail['description'] ?? null),
        );
        $images = self::productImages($detail);
        $url = route('products.show', (string) $detail['url_handle']);
        $variant = ProductPricing::defaultVariant($detail);
        $pricing = ProductPricing::resolve($detail, $variant);
        $currency = self::activeCurrency();

        self::page($title, $description, type: 'product');

        if ($images !== []) {
            Head::ogImage($images[0]);
        }

        if (self::filled($pricing['price'])) {
            Head::meta('product:price:amount', $pricing['price'], property: true)
                ->meta('product:price:currency', $currency, property: true);
        }

        Head::schema(self::productSchema($detail, $variant, $pricing, $images, $url, $description, $currency))
            ->schema(self::productBreadcrumbs($detail, self::translated($detail['title'] ?? null), $url));
    }

    private static function resource(?string $seoTitle, ?string $name, ?string $seoDescription, ?string $description): void
    {
        self::page(
            title: self::resolvedTitle((string) $seoTitle, (string) $name),
            description: self::resolvedDescription((string) $seoDescription, (string) $description),
        );
    }

    /**
     * @param  array<string, mixed>  $detail
     * @param  array<string, mixed>|null  $variant
     * @param  array{price: string|null, compare_at: string|null, range: array{0: string, 1: string}|null}  $pricing
     * @param  list<string>  $images
     */
    private static function productSchema(
        array $detail,
        ?array $variant,
        array $pricing,
        array $images,
        string $url,
        ?string $description,
        string $currency,
    ): SchemaObject {
        $schema = Schema::product()->name(self::translated($detail['title'] ?? null))->set('url', $url);
        $sku = self::firstFilled(self::field($variant, 'sku'), self::field($detail, 'sku'));
        $barcode = self::firstFilled(self::field($variant, 'barcode'), self::field($detail, 'barcode'));

        if (self::filled($description)) {
            $schema->description($description);
        }

        if ($images !== []) {
            $schema->image($images);
        }

        if (self::filled($sku)) {
            $schema->sku($sku);
        }

        if (self::filled($barcode)) {
            $schema->set('gtin', $barcode);
        }

        $brandName = self::resourceName($detail['brand'] ?? null);

        if ($brandName !== '') {
            $schema->brand(Schema::brand()->name($brandName));
        }

        $categoryName = self::resourceName($detail['category'] ?? null);

        if ($categoryName !== '') {
            $schema->set('category', $categoryName);
        }

        self::applyProductOffer($schema, $pricing, $detail, $currency, $url);
        self::applyAggregateRating($schema, $detail);

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $detail
     * @param  array{price: string|null, compare_at: string|null, range: array{0: string, 1: string}|null}  $pricing
     */
    private static function applyProductOffer(
        SchemaObject $schema,
        array $pricing,
        array $detail,
        string $currency,
        string $url,
    ): void {
        $availability = ($detail['in_stock'] ?? false)
            ? OfferAvailability::InStock
            : OfferAvailability::OutOfStock;
        $validThrough = null;
        $range = $pricing['range'];

        if ($range !== null) {
            $offer = Schema::make('AggregateOffer')
                ->set('lowPrice', $range[0])
                ->set('highPrice', $range[1])
                ->set('priceCurrency', $currency)
                ->set('availability', $availability->url())
                ->set('url', $url);

            self::applyOfferTiming($offer, $validThrough);
            $schema->set('offers', $offer);

            return;
        }

        if (! self::filled($pricing['price'])) {
            return;
        }

        $offer = Schema::offer()
            ->price($pricing['price'])
            ->currency($currency)
            ->availability($availability)
            ->set('url', $url);

        self::applyOfferTiming($offer, $validThrough);
        $schema->set('offers', $offer);
    }

    private static function applyOfferTiming(SchemaObject $offer, ?string $validThrough): void
    {
        if (self::filled($validThrough)) {
            $offer->set('priceValidUntil', $validThrough);
        }
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private static function applyAggregateRating(SchemaObject $schema, array $detail): void
    {
        $rating = $detail['rating'] ?? null;
        $reviewCount = (int) ($detail['review_count'] ?? 0);

        if (! is_numeric($rating) || (float) $rating <= 0 || $reviewCount <= 0) {
            return;
        }

        $schema->set('aggregateRating', [
            '@type' => 'AggregateRating',
            'ratingValue' => $rating,
            'reviewCount' => $reviewCount,
            'bestRating' => 5,
            'worstRating' => 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $detail
     * @return list<string>
     */
    private static function productImages(array $detail): array
    {
        $sources = [$detail['featured_media'] ?? null];

        foreach ((array) ($detail['media'] ?? []) as $media) {
            $sources[] = $media;
        }

        foreach ((array) ($detail['variants'] ?? []) as $variant) {
            $sources[] = is_array($variant) ? ($variant['media'] ?? null) : null;
        }

        $urls = [];

        foreach ($sources as $media) {
            $url = self::mediaUrl($media);

            if ($url !== null) {
                $urls[] = $url;
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private static function productBreadcrumbs(array $detail, string $title, string $url): Breadcrumbs
    {
        $items = [__('Home') => route('home')];
        $category = is_array($detail['category'] ?? null) ? $detail['category'] : null;
        $ancestors = is_array($category) && is_array($category['ancestors'] ?? null) ? $category['ancestors'] : [];

        foreach ($ancestors as $ancestor) {
            self::pushCategoryCrumb($items, $ancestor);
        }

        self::pushCategoryCrumb($items, $category);

        $items[$title] = $url;

        return Schema::breadcrumbs()->items($items);
    }

    /**
     * @param  array<string, string>  $items
     */
    private static function pushCategoryCrumb(array &$items, mixed $category): void
    {
        if (! is_array($category) || ! is_string($category['url_handle'] ?? null)) {
            return;
        }

        $name = self::translated($category['name'] ?? null);

        if ($name !== '') {
            $items[$name] = route('categories.products.show', $category['url_handle']);
        }
    }

    private static function activeCurrency(): string
    {
        $active = request()->attributes->get('active_currency');

        if (is_string($active) && $active !== '') {
            return $active;
        }

        return (string) Setting::getByGroup(SettingGroup::Currency)->get('base_currency', 'USD');
    }

    private static function mediaUrl(mixed $media): ?string
    {
        if ($media instanceof Media) {
            return self::firstFilled($media->url);
        }

        if (is_array($media)) {
            return self::firstFilled(isset($media['url']) ? (string) $media['url'] : null);
        }

        return null;
    }

    private static function translated(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (! is_array($value)) {
            return '';
        }

        foreach ([app()->getLocale(), app()->getFallbackLocale()] as $locale) {
            if (isset($value[$locale]) && is_string($value[$locale]) && $value[$locale] !== '') {
                return $value[$locale];
            }
        }

        $first = reset($value);

        return is_string($first) ? $first : '';
    }

    private static function resourceName(mixed $resource): string
    {
        return is_array($resource) ? self::translated($resource['name'] ?? null) : '';
    }

    private static function resolvedTitle(string $seoTitle, string $name): string
    {
        return self::firstFilled($seoTitle, $name) ?? $name;
    }

    private static function resolvedDescription(string $seoDescription, string $description): ?string
    {
        return MetaDescription::resolve($seoDescription, $description) ?: null;
    }

    /**
     * @param  array<string, mixed>|null  $source
     */
    private static function field(?array $source, string $key): ?string
    {
        return $source === null ? null : self::stringValue($source[$key] ?? null);
    }

    private static function stringValue(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function firstFilled(?string ...$values): ?string
    {
        foreach ($values as $value) {
            if (self::filled($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @phpstan-assert-if-true non-empty-string $value
     */
    private static function filled(?string $value): bool
    {
        return $value !== null && $value !== '';
    }
}
