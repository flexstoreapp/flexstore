<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Region;

final readonly class DestroyRegionAction
{
    public function handle(Region $region): bool
    {
        return (bool) $region->delete();
    }
}
