<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MediaType;
use App\Models\Media;

final readonly class CreateExternalMediaAction
{
    public function handle(string $url, MediaType $type = MediaType::Image): Media
    {
        return Media::query()->firstOrCreate(
            ['external_url' => $url],
            ['type' => $type, 'disk' => 'public'],
        );
    }
}
