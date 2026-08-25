<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionKind
{
    case View;
    case Manage;
    case Destructive;
    case Reference;
}
