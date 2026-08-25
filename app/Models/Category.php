<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Override;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;
use stdClass;

/**
 * @property-read int $id
 * @property-read string $url_handle
 * @property-read string $name
 * @property-read string $description
 * @property-read string|null $seo_title
 * @property-read string|null $seo_description
 * @property-read int|null $parent_id
 * @property-read int $sort_order
 * @property-read bool $is_active
 * @property-read self|null $parent
 * @property-read Collection<int, self> $children
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 */
#[Translatable('name', 'description', 'seo_title', 'seo_description')]
#[UseFactory(CategoryFactory::class)]
final class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    use HasTranslations;

    #[Override]
    public function casts(): array
    {
        return [
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
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return Collection<int, self>
     */
    public function ancestors(): Collection
    {
        if ($this->parent_id === null) {
            return new Collection;
        }

        /** @var list<stdClass> $rows */
        $rows = DB::select(
            'WITH RECURSIVE ancestors AS (
                SELECT id, parent_id, 0 AS depth FROM categories WHERE id = ?
                UNION ALL
                SELECT c.id, c.parent_id, a.depth + 1 FROM categories c INNER JOIN ancestors a ON c.id = a.parent_id WHERE a.depth < 50
            ) SELECT id FROM ancestors ORDER BY depth DESC',
            [$this->parent_id]
        );

        $ids = array_map(fn (stdClass $row): int => (int) $row->id, $rows);

        if ($ids === []) {
            return new Collection;
        }

        $categories = self::query()->whereIn('id', $ids)->get()->keyBy('id');

        return new Collection(array_filter(
            array_map(fn (int $id) => $categories->get($id), $ids)
        ));
    }

    public function isAncestorOf(self $other): bool
    {
        if ($this->id === $other->id) {
            return false;
        }

        return in_array($this->id, $this->ancestorIds($other->id), true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function withDescendants(Builder $query, int $id): Builder
    {
        $ids = $this->descendantIds($id);

        return $query->whereIn('categories.id', $ids);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function withAncestors(Builder $query, int $id): Builder
    {
        $ids = $this->ancestorIds($id);

        return $query->whereIn('categories.id', $ids);
    }

    /**
     * @return list<int>
     */
    private function descendantIds(int $id): array
    {
        /** @var list<stdClass> $rows */
        $rows = DB::select(
            'WITH RECURSIVE descendants AS (
                SELECT id, 0 AS depth FROM categories WHERE id = ?
                UNION ALL
                SELECT c.id, d.depth + 1 FROM categories c INNER JOIN descendants d ON c.parent_id = d.id WHERE d.depth < 50
            ) SELECT id FROM descendants',
            [$id]
        );

        return array_map(fn (stdClass $row): int => (int) $row->id, $rows);
    }

    /**
     * @return list<int>
     */
    private function ancestorIds(int $id): array
    {
        /** @var list<stdClass> $rows */
        $rows = DB::select(
            'WITH RECURSIVE ancestors AS (
                SELECT id, parent_id, 0 AS depth FROM categories WHERE id = ?
                UNION ALL
                SELECT c.id, c.parent_id, a.depth + 1 FROM categories c INNER JOIN ancestors a ON c.id = a.parent_id WHERE a.depth < 50
            ) SELECT id FROM ancestors',
            [$id]
        );

        return array_map(fn (stdClass $row): int => (int) $row->id, $rows);
    }
}
