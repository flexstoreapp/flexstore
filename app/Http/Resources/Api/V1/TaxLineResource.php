<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Utilities\LocalizedText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property-read array<string, mixed> $resource
 */
final class TaxLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource,
            'tax_name' => LocalizedText::resolve($this->resource['tax_name'] ?? null),
        ];
    }
}
