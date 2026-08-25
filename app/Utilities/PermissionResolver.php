<?php

declare(strict_types=1);

namespace App\Utilities;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Support\Collection;

final class PermissionResolver
{
    /** @var array<string, list<Permission>>|null */
    private static ?array $grantMap = null;

    /**
     * @return Collection<int, string>
     */
    public static function effectiveAbilities(User $user): Collection
    {
        $assigned = $user->getAllPermissions()->pluck('name')->all();
        $abilities = array_fill_keys($assigned, true);

        foreach ($assigned as $name) {
            foreach (self::impliedAbilities(Permission::tryFrom($name)) as $implied) {
                $abilities[$implied] = true;
            }
        }

        return collect(array_keys($abilities));
    }

    /**
     * @return list<Permission>
     */
    public static function grantedBy(string $ability): array
    {
        self::$grantMap ??= self::buildGrantMap();

        return self::$grantMap[$ability] ?? [];
    }

    /**
     * @return array<string, list<Permission>>
     */
    private static function buildGrantMap(): array
    {
        $map = [];

        foreach (Permission::cases() as $permission) {
            foreach (self::impliedAbilities($permission) as $ability) {
                $map[$ability][] = $permission;
            }
        }

        return $map;
    }

    /**
     * @return list<string>
     */
    private static function impliedAbilities(?Permission $permission): array
    {
        if (! $permission instanceof Permission) {
            return [];
        }

        $resolved = [];
        $queue = $permission->implies();

        while ($queue !== []) {
            $current = array_pop($queue);

            if (isset($resolved[$current->value])) {
                continue;
            }

            $resolved[$current->value] = true;
            $queue = [...$queue, ...$current->implies()];
        }

        return array_keys($resolved);
    }
}
