<?php

declare(strict_types=1);

namespace App\Filters\Configs;

use App\Filters\Criteria\ExactMatchCriterion;
use App\Filters\Criteria\NullStateCriterion;
use App\Filters\Criteria\SortCriterion;
use App\Filters\Criteria\TextSearchCriterion;
use App\Filters\CriteriaCollection;
use App\Filters\Strategies\CustomerNameColumnSortStrategy;
use App\Filters\Strategies\NormalizedTotalColumnSortStrategy;
use App\Models\Order;

final readonly class OrderFilterConfig
{
    /**
     * @return CriteriaCollection<Order>
     */
    public static function getCriteria(mixed $direction = null): CriteriaCollection
    {
        /** @var CriteriaCollection<Order> $criteria */
        $criteria = new CriteriaCollection();

        /** @var TextSearchCriterion<Order> $textSearch */
        $textSearch = new TextSearchCriterion(
            columns: ['id', 'customer_email', 'billingAddress.first_name', 'billingAddress.last_name'],
            stripIdPrefixes: ['#'],
        );
        $criteria->add('query', $textSearch);

        /** @var ExactMatchCriterion<Order> $fulfillmentStatusMatch */
        $fulfillmentStatusMatch = new ExactMatchCriterion('fulfillment_status');
        $criteria->add('fulfillment_status', $fulfillmentStatusMatch);

        /** @var ExactMatchCriterion<Order> $paymentStatusMatch */
        $paymentStatusMatch = new ExactMatchCriterion('payment_status');
        $criteria->add('payment_status', $paymentStatusMatch);

        /** @var NullStateCriterion<Order> $cancellationStatus */
        $cancellationStatus = new NullStateCriterion(
            column: 'canceled_at',
            nullValue: 'active',
            notNullValue: 'canceled',
        );
        $criteria->add('cancellation_status', $cancellationStatus);

        /** @var SortCriterion<Order> $sortCriterion */
        $sortCriterion = new SortCriterion(
            allowedColumns: ['id', 'customer_name', 'total', 'created_at'],
            columnStrategies: [
                'customer_name' => new CustomerNameColumnSortStrategy(),
                'total' => new NormalizedTotalColumnSortStrategy(),
            ],
            direction: $direction,
        );
        $criteria->add('sort', $sortCriterion);

        return $criteria;
    }
}
