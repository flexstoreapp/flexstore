<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Announcement;

final readonly class DestroyAnnouncementAction
{
    public function handle(Announcement $announcement): bool
    {
        return (bool) $announcement->delete();
    }
}
