<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\UpdateStorefrontSectionInput;
use App\Models\StorefrontSection;

final readonly class UpdateStorefrontSectionAction
{
    public function __construct(
        private SyncSectionMediaAction $syncSectionMediaAction,
    ) {
    }

    public function handle(StorefrontSection $section, UpdateStorefrontSectionInput $input): StorefrontSection
    {
        $section->update([
            'type' => $input->has('type') ? $input->type : $section->type,
            'title' => $input->has('title') ? $input->title : $section->title,
            'settings' => $input->has('settings') ? $input->settings : $section->settings,
            'is_active' => $input->has('is_active') ? $input->isActive : $section->is_active,
        ]);

        if ($input->has('settings')) {
            $this->syncSectionMediaAction->handle($section);
        }

        return $section;
    }
}
