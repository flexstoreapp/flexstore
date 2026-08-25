<?php

declare(strict_types=1);

namespace App\Utilities;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final readonly class TimeBucket
{
    /**
     * @param  Collection<string, mixed>  $grouped  Keyed by date_group
     * @param  callable(string $dateLabel, mixed $bucket, string $key): array<string, mixed>  $rowBuilder
     * @return list<array<string, mixed>>
     */
    public static function fill(
        CarbonInterface $from,
        CarbonInterface $to,
        Collection $grouped,
        callable $rowBuilder,
    ): array {
        if ($grouped->isEmpty()) {
            return [];
        }

        $days = $from->diffInDays($to);
        $rows = [];

        if ($days > 90) {
            $current = $from->copy()->startOfMonth();
            $end = $to->copy()->startOfMonth();

            while ($current <= $end) {
                $key = $current->format('Y-m');
                $rows[] = $rowBuilder($current->translatedFormat('M Y'), $grouped->get($key), $key);
                $current = $current->copy()->addMonth();
            }
        } else {
            for ($i = 0; $i <= $days; $i++) {
                $key = $from->copy()->addDays($i)->format('Y-m-d');
                $rows[] = $rowBuilder(Date::parse($key)->translatedFormat('M d'), $grouped->get($key), $key);
            }
        }

        return $rows;
    }

    /**
     * @param  literal-string  $column
     */
    public static function dateGroupExpression(CarbonInterface $from, CarbonInterface $to, string $column = 'created_at'): Expression
    {
        return DB::raw(self::columnExpression($from, $to, $column) . ' as date_group');
    }

    /**
     * @param  literal-string  $column
     * @return literal-string
     */
    public static function columnExpression(CarbonInterface $from, CarbonInterface $to, string $column): string
    {
        if ($from->diffInDays($to) <= 90) {
            return "DATE({$column})";
        }

        return match (DB::getDriverName()) {
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', {$column})",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }
}
