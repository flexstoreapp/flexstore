<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MediaType;
use App\Enums\SettingType;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Override;

/**
 * @property-read int $id
 * @property-read MediaType $type
 * @property-read string $disk
 * @property-read string|null $path
 * @property-read string|null $thumbnail_path
 * @property-read string|null $small_thumbnail_path
 * @property-read string|null $external_url
 * @property-read string|null $url
 * @property-read string|null $thumbnail_url
 * @property-read string|null $small_thumbnail_url
 * @property-read string|null $mime_type
 * @property-read int|null $size
 * @property-read int|null $width
 * @property-read int|null $height
 * @property-read int|null $duration
 * @property-read string|null $original_filename
 * @property-read string|null $alt
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Mediable> $attachments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MenuItem> $menuItems
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Brand> $brands
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductDownload> $productDownloads
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductVariant> $productVariants
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderItem> $orderItems
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderItemDownload> $orderItemDownloads
 */
#[Table('media')]
#[Appends(['url', 'thumbnail_url', 'small_thumbnail_url'])]
#[Hidden(['disk', 'path', 'thumbnail_path', 'small_thumbnail_path', 'external_url', 'created_at', 'updated_at', 'pivot'])]
#[UseFactory(MediaFactory::class)]
final class Media extends Model
{
    /** @use HasFactory<\Database\Factories\MediaFactory> */
    use HasFactory;

    private const array DISPLAY_COLUMNS = ['id', 'type', 'disk', 'path', 'thumbnail_path', 'small_thumbnail_path', 'external_url', 'alt', 'width', 'height'];

    /**
     * @return list<string>
     */
    public static function displayColumns(): array
    {
        return array_map(fn (string $column): string => 'media.' . $column, self::DISPLAY_COLUMNS);
    }

    public static function displaySelect(): string
    {
        return implode(',', self::DISPLAY_COLUMNS);
    }

    /**
     * @param  array<int>  $mediaIds
     */
    public static function deleteUnreferenced(array $mediaIds): void
    {
        $mediaIds = array_values(array_unique(array_filter($mediaIds)));

        if ($mediaIds === []) {
            return;
        }

        $assetSettingMediaIds = Setting::query()
            ->where('type', SettingType::Asset)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->toBase()
            ->pluck('value')
            ->map(fn (mixed $value): int => (int) $value);

        self::query()
            ->whereIn('id', $mediaIds)
            ->whereNotIn('id', $assetSettingMediaIds)
            ->whereDoesntHave('attachments')
            ->whereDoesntHave('menuItems')
            ->whereDoesntHave('brands')
            ->whereDoesntHave('productDownloads')
            ->whereDoesntHave('productVariants')
            ->whereDoesntHave('orderItems')
            ->whereDoesntHave('orderItemDownloads')
            ->get()
            ->each->delete();
    }

    #[Override]
    public function casts(): array
    {
        return [
            'type' => MediaType::class,
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'duration' => 'integer',
        ];
    }

    /**
     * @return HasMany<Mediable, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Mediable::class);
    }

    /**
     * @return HasMany<MenuItem, $this>
     */
    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'featured_image_id');
    }

    /**
     * @return HasMany<Brand, $this>
     */
    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class, 'image_id');
    }

    /**
     * @return HasMany<ProductDownload, $this>
     */
    public function productDownloads(): HasMany
    {
        return $this->hasMany(ProductDownload::class);
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function productVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'media_id');
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<OrderItemDownload, $this>
     */
    public function orderItemDownloads(): HasMany
    {
        return $this->hasMany(OrderItemDownload::class);
    }

    #[Override]
    protected static function booted(): void
    {
        self::deleting(function (self $media): void {
            if ($media->path === null) {
                return;
            }

            Storage::disk($media->disk)->delete(array_values(array_filter([
                $media->path,
                $media->thumbnail_path,
                $media->small_thumbnail_path,
            ])));
        });
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function url(): Attribute
    {
        return Attribute::get(function (mixed $value, array $attributes): ?string {
            if (($attributes['external_url'] ?? null) !== null) {
                return $attributes['external_url'];
            }

            if (($attributes['path'] ?? null) !== null && ($attributes['disk'] ?? null) === 'public') {
                return Storage::disk('public')->url($attributes['path']);
            }

            return null;
        });
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->thumbnail_path !== null && $this->disk === 'public') {
                return Storage::disk('public')->url($this->thumbnail_path);
            }

            return $this->type === MediaType::Image ? $this->url : null;
        });
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function smallThumbnailUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->small_thumbnail_path !== null && $this->disk === 'public') {
                return Storage::disk('public')->url($this->small_thumbnail_path);
            }

            return $this->thumbnail_url;
        });
    }
}
