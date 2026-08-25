<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StoreStorefrontSectionInput;
use App\Models\StorefrontSection;

final readonly class StoreStorefrontSectionAction
{
    public function __construct(
        private SyncSectionMediaAction $syncSectionMediaAction,
    ) {
    }

    public function handle(StoreStorefrontSectionInput $input): StorefrontSection
    {
        $maxSortOrder = StorefrontSection::query()->max('sort_order') ?? -1;

        $section = StorefrontSection::query()->create([
            'type' => $input->type,
            'title' => $input->title,
            'settings' => $input->settings,
            'sort_order' => $maxSortOrder + 1,
            'is_active' => $input->isActive,
        ]);

        $this->syncSectionMediaAction->handle($section);

        return $section;
    }
}
