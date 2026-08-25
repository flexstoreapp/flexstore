<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StoreAnnouncementInput;
use App\Models\Announcement;

final readonly class StoreAnnouncementAction
{
    public function handle(StoreAnnouncementInput $input): Announcement
    {
        $maxSortOrder = Announcement::query()->max('sort_order') ?? -1;

        return Announcement::query()->create([
            'content' => $input->content,
            'url' => $input->url,
            'is_active' => $input->isActive ?? true,
            'sort_order' => $maxSortOrder + 1,
            'starts_at' => $input->startsAt,
            'ends_at' => $input->endsAt,
        ]);
    }
}
