<?php

declare(strict_types=1);

namespace App\Concerns;

trait InteractsWithUploadLimits
{
    private function maxUploadSizeMB(): int
    {
        return min((int) ini_get('post_max_size'), (int) ini_get('upload_max_filesize'));
    }
}
