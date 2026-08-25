<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\StorefrontSection;
use Illuminate\Database\Eloquent\Collection;

final readonly class StorefrontSectionListQuery
{
    /**
     * @return Collection<int, StorefrontSection>
     */
    public function execute(): Collection
    {
        return StorefrontSection::query()
            ->orderBy('sort_order')
            ->get(['id', 'type', 'title', 'is_active']);
    }
}
