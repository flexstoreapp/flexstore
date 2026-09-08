<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\ShowDashboardRequest;
use App\Http\Resources\Api\V1\Admin\DashboardResource;
use App\Queries\DashboardStatsQuery;
use App\Queries\PeriodRangesQuery;

final readonly class DashboardController
{
    public function __invoke(
        ShowDashboardRequest $request,
        DashboardStatsQuery $dashboardStatsQuery,
        PeriodRangesQuery $periodRangesQuery,
    ): DashboardResource {
        $from = $request->dateFrom();
        $to = $request->dateTo();

        return new DashboardResource([
            'period' => $request->period()->value,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'periods' => $periodRangesQuery->execute(),
            ...$dashboardStatsQuery->execute($from, $to),
        ]);
    }
}
