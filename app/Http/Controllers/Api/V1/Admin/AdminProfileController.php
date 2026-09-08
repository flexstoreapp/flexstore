<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\Api\V1\Admin\AdminUserResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

final readonly class AdminProfileController
{
    public function __invoke(#[CurrentUser] User $user): AdminUserResource
    {
        return new AdminUserResource($user);
    }
}
