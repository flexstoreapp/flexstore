<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\StorefrontSection;

final readonly class SyncSectionMediaAction
{
    public function __construct(
        private SyncMediaAction $syncMediaAction,
    ) {
    }

    public function handle(StorefrontSection $section): void
    {
        $this->syncMediaAction->handle(
            $section,
            $section->type->extractMediaIds($section->settings ?? []),
        );
    }
}
