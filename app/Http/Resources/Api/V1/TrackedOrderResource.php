<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * Wraps the array shape produced by TrackedOrderQuery.
 */
final class TrackedOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $order */
        $order = $this->resource;

        /** @var list<array<string, mixed>> $groups */
        $groups = $order['groups'];

        return [
            ...$order,
            'groups' => array_map(fn (array $group): array => [
                ...$group,
                'items' => OrderLineResource::collection($group['items']),
            ], $groups),
        ];
    }
}
