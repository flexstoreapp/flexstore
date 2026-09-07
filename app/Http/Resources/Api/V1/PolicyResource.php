<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Enums\StorePolicy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property-read array{policy: StorePolicy, content: string} $resource
 */
final class PolicyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        $policy = $this->resource['policy'];

        return [
            'policy' => $policy->value,
            'title' => $policy->title(),
            'content' => $this->resource['content'],
        ];
    }
}
