<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Utilities\LocalizedText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * Wraps a single option from EligiblePaymentOptionsQuery.
 */
final class PaymentOptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $option */
        $option = $this->resource;

        return [
            ...$option,
            'name' => LocalizedText::resolve($option['name']),
        ];
    }
}
