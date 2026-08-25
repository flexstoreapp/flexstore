<?php

declare(strict_types=1);

namespace App\Utilities;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Override;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends MorphToMany<TRelatedModel, TDeclaringModel>
 */
final class StringIdMorphToMany extends MorphToMany
{
    #[Override]
    public function addEagerConstraints(array $models): void
    {
        $this->whereInEager(
            'whereIn',
            $this->getQualifiedForeignPivotKeyName(),
            $this->stringKeys($models),
        );

        $this->query->where($this->qualifyPivotColumn($this->morphType), $this->morphClass);
    }

    #[Override]
    public function newPivotQuery()
    {
        $query = $this->newPivotStatement();

        foreach ($this->pivotWheres as $arguments) {
            $query->where(...$arguments);
        }

        foreach ($this->pivotWhereIns as $arguments) {
            $query->whereIn(...$arguments);
        }

        foreach ($this->pivotWhereNulls as $arguments) {
            $query->whereNull(...$arguments);
        }

        return $query
            ->where($this->getQualifiedForeignPivotKeyName(), $this->stringParentKey())
            ->where($this->morphType, $this->morphClass);
    }

    #[Override]
    protected function addWhereConstraints()
    {
        $this->query->where(
            $this->getQualifiedForeignPivotKeyName(),
            '=',
            $this->stringParentKey(),
        );

        $this->query->where($this->qualifyPivotColumn($this->morphType), $this->morphClass);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    protected function baseAttachRecord($id, $timed)
    {
        $record = parent::baseAttachRecord($id, $timed);
        $record[$this->foreignPivotKey] = $this->stringParentKey();

        return $record;
    }

    private function stringParentKey(): ?string
    {
        $key = $this->parent->{$this->parentKey};

        return $key === null ? null : (string) $key;
    }

    /**
     * @param  array<int, TDeclaringModel>  $models
     * @return list<string>
     */
    private function stringKeys(array $models): array
    {
        return array_values(array_map(
            strval(...),
            $this->getKeys($models, $this->parentKey),
        ));
    }
}
