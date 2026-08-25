<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StoreRegionInput;
use App\Models\Region;

final readonly class StoreRegionAction
{
    public function handle(StoreRegionInput $input): Region
    {
        return Region::query()->create([
            'name' => $input->name,
            'countries' => $input->countries,
            'states' => $input->states ?? [],
            'postal_codes' => $input->postal_codes ?? [],
            'is_active' => $input->isActive ?? true,
        ]);
    }
}
