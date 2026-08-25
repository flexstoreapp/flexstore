<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\UpdateRegionInput;
use App\Models\Region;

final readonly class UpdateRegionAction
{
    public function handle(Region $region, UpdateRegionInput $input): Region
    {
        $region->update([
            'name' => $input->has('name') ? $input->name : $region->name,
            'countries' => $input->has('countries') ? $input->countries : $region->countries,
            'states' => $input->has('states') ? $input->states : $region->states,
            'postal_codes' => $input->has('postal_codes') ? $input->postal_codes : $region->postal_codes,
            'is_active' => $input->has('is_active') ? $input->isActive : $region->is_active,
        ]);

        return $region;
    }
}
