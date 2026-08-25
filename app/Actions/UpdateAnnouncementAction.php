<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\UpdateAnnouncementInput;
use App\Models\Announcement;

final readonly class UpdateAnnouncementAction
{
    public function handle(Announcement $announcement, UpdateAnnouncementInput $input): Announcement
    {
        $announcement->update([
            'content' => $input->has('content') ? $input->content : $announcement->content,
            'url' => $input->has('url') ? $input->url : $announcement->url,
            'is_active' => $input->has('is_active') ? $input->isActive : $announcement->is_active,
            'starts_at' => $input->has('starts_at') ? $input->startsAt : $announcement->starts_at,
            'ends_at' => $input->has('ends_at') ? $input->endsAt : $announcement->ends_at,
        ]);

        return $announcement;
    }
}
