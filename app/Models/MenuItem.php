<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MenuItemLinkType;
use App\Enums\MenuLocation;
use App\Enums\MenuPage;
use Database\Factories\MenuItemFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * @property-read int $id
 * @property-read MenuLocation $location
 * @property-read string $label
 * @property-read MenuItemLinkType $link_type
 * @property-read int|null $brand_id
 * @property-read int|null $category_id
 * @property-read string|null $url
 * @property-read MenuPage|null $page
 * @property-read string $target
 * @property-read int|null $parent_id
 * @property-read int $sort_order
 * @property-read bool $is_mega_menu
 * @property-read int|null $featured_image_id
 * @property-read Media|null $featuredImage
 * @property-read string $featured_title
 * @property-read string|null $featured_url
 * @property-read bool $is_active
 * @property-read self|null $parent
 * @property-read Brand|null $brand
 * @property-read Category|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, self> $children
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 */
#[Translatable('label', 'featured_title')]
#[UseFactory(MenuItemFactory::class)]
final class MenuItem extends Model
{
    /** @use HasFactory<\Database\Factories\MenuItemFactory> */
    use HasFactory;

    use HasTranslations;

    /**
     * @return array<string, string>
     */
    #[Override]
    public function casts(): array
    {
        return [
            'location' => MenuLocation::class,
            'link_type' => MenuItemLinkType::class,
            'page' => MenuPage::class,
            'sort_order' => 'integer',
            'is_mega_menu' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function forLocation(Builder $query, MenuLocation $location): Builder
    {
        return $query->where('location', $location->value);
    }
}
