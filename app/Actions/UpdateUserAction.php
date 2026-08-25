<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\UpdateUserInput;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final readonly class UpdateUserAction
{
    public function handle(User $user, UpdateUserInput $input): User
    {
        return DB::transaction(function () use ($user, $input): User {
            $newEmail = $input->has('email') ? $input->email : $user->email;
            $emailChanged = $newEmail !== $user->email;

            $updateData = [
                'name' => $input->has('name') ? $input->name : $user->name,
                'url_handle' => $input->has('url_handle') ? $input->urlHandle : $user->url_handle,
                'email' => $newEmail,
                'email_verified_at' => $this->resolveEmailVerifiedAt($user, $input, $emailChanged),
                'appearance' => $input->has('appearance') ? $input->appearance : $user->appearance,
                'admin_theme_color' => $input->has('admin_theme_color') ? $input->adminThemeColor : $user->admin_theme_color,
            ];

            if ($input->password !== null) {
                $updateData['password'] = $input->password;
            }

            $user->update($updateData);

            if ($input->roleIds !== null) {
                $roles = Role::query()->findMany($input->roleIds);
                $user->syncRoles($roles);
            }

            return $user;
        });
    }

    private function resolveEmailVerifiedAt(User $user, UpdateUserInput $input, bool $emailChanged): mixed
    {
        if ($input->has('email_verified_at')) {
            return $input->emailVerifiedAt;
        }

        if ($emailChanged) {
            return null;
        }

        return $user->email_verified_at;
    }
}
