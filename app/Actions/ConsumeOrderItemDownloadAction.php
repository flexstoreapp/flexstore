<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\OrderItemDownload;

final readonly class ConsumeOrderItemDownloadAction
{
    public function handle(OrderItemDownload $download): bool
    {
        $consumed = OrderItemDownload::query()
            ->whereKey($download->id)
            ->increment('download_count', 1, ['last_downloaded_at' => now()]);

        return $consumed > 0;
    }
}
