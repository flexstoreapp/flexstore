<?php

declare(strict_types=1);

namespace App\Http\Controllers\Installer;

use App\Enums\Role;
use App\Http\Requests\Installer\StoreAccountRequest;
use App\Installer\Contracts\InstallationState;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final readonly class AccountController
{
    public function __construct(private InstallationState $installationState)
    {
    }

    public function create(): Response|RedirectResponse
    {
        if (! $this->installationState->databaseIsMigrated()) {
            return to_route('installer.database.create')->withErrors(['db_connection' => $this->databaseError()]);
        }

        return Inertia::render('installer/account');
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        if (! $this->installationState->databaseIsMigrated()) {
            return to_route('installer.database.create')->withErrors(['db_connection' => $this->databaseError()]);
        }

        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated): void {
                $user = User::query()->updateOrCreate(
                    ['email' => $validated['email']],
                    [
                        'name' => $validated['name'],
                        'password' => $validated['password'],
                        'email_verified_at' => now(),
                    ],
                );

                $user->syncRoles([Role::SuperAdmin]);
            });
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'email' => $e->getMessage() !== '' ? $e->getMessage() : $e::class,
            ]);
        }

        return to_route('installer.finalize.show');
    }

    private function databaseError(): ?string
    {
        $default = config('database.default');
        $database = config("database.connections.{$default}.database");

        try {
            if (Schema::hasTable('users')) {
                return null;
            }

            return "Connected to {$default} database '{$database}' but the users table does not exist. Re-run the database step.";
        } catch (Throwable $e) {
            return "Cannot connect to the {$default} database '{$database}': " . $e->getMessage();
        }
    }
}
