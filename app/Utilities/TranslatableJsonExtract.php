<?php

declare(strict_types=1);

namespace App\Utilities;

use Illuminate\Support\Facades\DB;

final readonly class TranslatableJsonExtract
{
    /**
     * @param  literal-string  $column
     * @return array{literal-string, list<string>}
     */
    public static function expression(string $column, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $driver = DB::getDriverName();

        $sql = match ($driver) {
            'pgsql' => "{$column}->>?",
            'sqlite' => "JSON_EXTRACT({$column}, ?)",
            default => "JSON_UNQUOTE(JSON_EXTRACT({$column}, ?))",
        };

        $binding = $driver === 'pgsql' ? $locale : '$.' . $locale;

        return [$sql, [$binding]];
    }

    /**
     * @param  literal-string  $column
     * @return array{literal-string, list<string>}
     */
    public static function expressionWithFallback(string $column, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $fallback = (string) config('app.fallback_locale');

        [$sql, $bindings] = self::expression($column, $locale);

        if ($fallback === '' || $fallback === $locale) {
            return [$sql, $bindings];
        }

        [$fallbackSql, $fallbackBindings] = self::expression($column, $fallback);

        return ["coalesce({$sql}, {$fallbackSql})", [...$bindings, ...$fallbackBindings]];
    }

    /**
     * @param  literal-string  $column
     * @return array{literal-string, list<string>}
     */
    public static function orderByExpression(string $column, string $direction = 'asc', ?string $locale = null): array
    {
        [$sql, $bindings] = self::expressionWithFallback($column, $locale);
        $suffix = mb_strtolower($direction) === 'desc' ? ' desc' : ' asc';

        return [$sql . $suffix, $bindings];
    }
}
