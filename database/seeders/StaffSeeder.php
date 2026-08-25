<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

final class StaffSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()
            ->create()
            ->assignRole(Role::SuperAdmin);
    }
}
