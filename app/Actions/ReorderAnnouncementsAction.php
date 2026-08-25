<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Announcement;
use Illuminate\Support\Facades\DB;

final readonly class ReorderAnnouncementsAction
{
    /**
     * @param  array<int, int>  $orderedIds
     */
    public function handle(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $sortOrder => $id) {
                Announcement::query()
                    ->where('id', $id)
                    ->update(['sort_order' => $sortOrder]);
            }
        });
    }
}
