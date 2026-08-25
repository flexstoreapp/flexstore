<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Media;
use App\Models\Mediable;
use App\Utilities\StringIdMorphToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasMedia
{
    public static function bootHasMedia(): void
    {
        static::deleted(function (Model $model): void {
            /** @var self $model */
            $model->detachAllMedia();
        });
    }

    public function detachAllMedia(): void
    {
        Mediable::query()
            ->where('mediable_type', $this->getMorphClass())
            ->where('mediable_id', (string) $this->getKey())
            ->delete();
    }

    /**
     * @return MorphToMany<Media, $this>
     */
    protected function morphMedia(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    /**
     * @template TRelatedModel of Model
     * @template TDeclaringModel of Model
     *
     * @param  Builder<TRelatedModel>  $query
     * @param  TDeclaringModel  $parent
     * @param  string  $name
     * @param  string  $table
     * @param  string  $foreignPivotKey
     * @param  string  $relatedPivotKey
     * @param  string  $parentKey
     * @param  string  $relatedKey
     * @param  string|null  $relationName
     * @return StringIdMorphToMany<TRelatedModel, TDeclaringModel>
     */
    protected function newMorphToMany(
        Builder $query,
        Model $parent,
        $name,
        $table,
        $foreignPivotKey,
        $relatedPivotKey,
        $parentKey,
        $relatedKey,
        $relationName = null,
        $inverse = false,
    ): StringIdMorphToMany {
        return new StringIdMorphToMany(
            $query,
            $parent,
            $name,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey,
            $relatedKey,
            $relationName,
            $inverse,
        );
    }
}
