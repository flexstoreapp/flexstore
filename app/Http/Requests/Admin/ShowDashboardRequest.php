<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\DatePeriod;
use App\Queries\LifetimeStartQuery;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Override;

final class ShowDashboardRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'period' => ['sometimes', Rule::enum(DatePeriod::class)],
            'from' => ['exclude_unless:period,custom', 'required', 'date_format:Y-m-d', 'before_or_equal:tomorrow'],
            'to' => ['exclude_unless:period,custom', 'required', 'date_format:Y-m-d', 'before_or_equal:tomorrow', 'after_or_equal:from'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'period' => mb_strtolower(__('Period')),
            'from' => mb_strtolower(__('From')),
            'to' => mb_strtolower(__('To')),
        ];
    }

    public function period(): DatePeriod
    {
        if (! $this->safe()->has('period')) {
            return DatePeriod::Last30Days;
        }

        return $this->safe()->enum('period', DatePeriod::class) ?? DatePeriod::Last30Days;
    }

    public function dateFrom(): CarbonInterface
    {
        $period = $this->period();

        if ($period === DatePeriod::Custom) {
            return Date::parse($this->safe()->string('from')->value())->startOfDay();
        }

        return $period->startsAt(resolve(LifetimeStartQuery::class)->execute(...));
    }

    public function dateTo(): CarbonInterface
    {
        $period = $this->period();

        if ($period === DatePeriod::Custom) {
            return Date::parse($this->safe()->string('to')->value())->endOfDay();
        }

        return $period->endsAt();
    }
}
