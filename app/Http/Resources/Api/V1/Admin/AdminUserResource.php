<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * @property-read \App\Models\User $resource
 */
final class AdminUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'roles' => $this->resource->roles->map(fn (Role $role): string => $role->name)->values()->all(),
            'permissions' => $this->resource->getAllPermissions()
                ->map(fn (Permission $permission): string => $permission->name)
                ->sort()
                ->values()
                ->all(),
            'two_factor_enabled' => $this->resource->hasTwoFactorEnabled(),
        ];
    }
}
