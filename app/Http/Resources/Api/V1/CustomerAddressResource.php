<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin CustomerAddress
 */
final class CustomerAddressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'state_name' => $this->state_name,
            'postal_code' => $this->postal_code,
            'country_code' => $this->country_code,
            'phone' => $this->phone,
            'is_default' => $this->is_default,
        ];
    }
}
