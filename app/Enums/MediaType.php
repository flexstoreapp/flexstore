<?php

declare(strict_types=1);

namespace App\Enums;

enum MediaType: string
{
    case Image = 'image';
    case Video = 'video';
    case File = 'file';
}
