<?php

declare(strict_types=1);

namespace App\Filters\Criteria;

use App\Filters\Contracts\Criterion;
use App\Utilities\LikePattern;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @template TModel of Model
 *
 * @implements Criterion<TModel>
 */
final readonly class TextSearchCriterion implements Criterion
{
    /**
     * @param  array<string>  $columns
     * @param  array<string>  $stripIdPrefixes  Prefixes stripped from the value (after any leading #) when searching the id column
     */
    public function __construct(private array $columns, private array $stripIdPrefixes = [])
    {
    }

    public function canApply(mixed $value): bool
    {
        if (is_string($value)) {
            return mb_trim($value) !== '';
        }

        return is_array($value) && $this->groups($value) !== [];
    }

    public function apply(Builder $builder, mixed $value): Builder
    {
        $groups = $this->groups($value);

        if ($groups === []) {
            return $builder;
        }

        return $builder->where(function (Builder $query) use ($groups): void {
            foreach ($groups as $group) {
                $query->where(function (Builder $groupQuery) use ($group): void {
                    foreach ($group as $token) {
                        $this->applyToken($groupQuery, $token);
                    }
                });
            }
        });
    }

    /**
     * @return list<list<string>>
     */
    private function groups(mixed $value): array
    {
        if (is_string($value)) {
            $tokens = preg_split('/\s+/', mb_strtolower(mb_trim($value)), -1, PREG_SPLIT_NO_EMPTY);

            if ($tokens === false || $tokens === []) {
                return [];
            }

            return array_map(fn (string $token): array => [$token], $tokens);
        }

        if (! is_array($value)) {
            return [];
        }

        $groups = [];

        foreach ($value as $group) {
            if (is_string($group)) {
                $token = mb_strtolower(mb_trim($group));

                if ($token !== '') {
                    $groups[] = [$token];
                }

                continue;
            }

            if (! is_array($group)) {
                continue;
            }

            $alternatives = [];

            foreach ($group as $term) {
                if (! is_string($term)) {
                    continue;
                }

                $token = mb_strtolower(mb_trim($term));

                if ($token !== '') {
                    $alternatives[$token] = $token;
                }
            }

            if ($alternatives !== []) {
                $groups[] = array_values($alternatives);
            }
        }

        return $groups;
    }

    /**
     * @param  Builder<TModel>  $query
     */
    private function applyToken(Builder $query, string $token): void
    {
        $connection = DB::connection();
        $grammar = $connection->getQueryGrammar();
        $escape = LikePattern::ESCAPE_CLAUSE;
        $pattern = '%' . LikePattern::escape($token) . '%';

        foreach ($this->columns as $column) {
            if (str_contains($column, '.')) {
                [$relation, $relationColumn] = explode('.', $column, 2);

                $query->orWhereHas($relation, function (Builder $relationQuery) use ($relationColumn, $pattern, $escape, $grammar, $connection): void {
                    $expression = $this->lowerExpression($grammar->wrap($relationColumn), $connection->getDriverName());
                    $relationQuery->whereRaw("{$expression} LIKE ? {$escape}", [$pattern]); // @phpstan-ignore argument.type
                });
            } else {
                $columnPattern = $column === 'id'
                    ? '%' . LikePattern::escape($this->stripIdPrefixes($token)) . '%'
                    : $pattern;

                $expression = $this->lowerExpression($grammar->wrap($column), $connection->getDriverName());
                $query->orWhereRaw("{$expression} LIKE ? {$escape}", [$columnPattern]); // @phpstan-ignore argument.type
            }
        }
    }

    private function stripIdPrefixes(string $token): string
    {
        foreach ($this->stripIdPrefixes as $prefix) {
            $prefix = mb_strtolower($prefix);
            if (str_starts_with($token, $prefix)) {
                $token = mb_substr($token, mb_strlen($prefix));
            }
        }

        return $token;
    }

    private function lowerExpression(string $wrappedColumn, string $driver): string
    {
        return $driver === 'pgsql'
            ? "LOWER(CAST({$wrappedColumn} AS TEXT))"
            : "LOWER({$wrappedColumn})";
    }
}
