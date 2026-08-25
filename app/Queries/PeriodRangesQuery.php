<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\DatePeriod;
use Carbon\CarbonInterface;

final readonly class PeriodRangesQuery
{
    public function __construct(private LifetimeStartQuery $lifetimeStart)
    {
    }

    /**
     * @return array<string, array{from: string, to: string}>
     */
    public function execute(): array
    {
        $lifetimeStart = $this->lifetimeStart->execute();
        $resolve = static fn (): CarbonInterface => $lifetimeStart;

        $ranges = [];

        foreach (DatePeriod::cases() as $period) {
            if ($period === DatePeriod::Custom) {
                continue;
            }

            $ranges[$period->value] = [
                'from' => $period->startsAt($resolve)->format('Y-m-d'),
                'to' => $period->endsAt()->format('Y-m-d'),
            ];
        }

        return $ranges;
    }
}
