<?php

declare(strict_types=1);

namespace App\Http\Controllers\Installer;

use App\Http\Requests\Installer\StoreFinalizeRequest;
use App\Installer\Contracts\EnvWriter;
use App\Installer\Contracts\InstallationState;
use App\Models\Setting;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

final readonly class FinalizeController
{
    public function __construct(private InstallationState $installationState)
    {
    }

    public function show(): Response|RedirectResponse
    {
        if (! $this->installationState->databaseIsMigrated()) {
            return to_route('installer.database.create');
        }

        return Inertia::render('installer/finalize', [
            'appUrl' => request()->getSchemeAndHttpHost(),
            'timezones' => DateTimeZone::listIdentifiers(),
        ]);
    }

    public function store(StoreFinalizeRequest $request, EnvWriter $environmentWriter): RedirectResponse|SymfonyResponse
    {
        @set_time_limit(0);
        ignore_user_abort(true);

        if (! $this->installationState->databaseIsMigrated()) {
            return to_route('installer.database.create')->withErrors([
                'db_connection' => 'The database is not yet migrated. Connect to a database and run migrations to continue.',
            ]);
        }

        $appUrl = request()->getSchemeAndHttpHost();

        $storeName = $request->validated('store_name');

        try {
            $environmentWriter->write([
                'APP_NAME' => $storeName,
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
                'APP_URL' => $appUrl,
                'APP_TIMEZONE' => $request->validated('timezone'),
                'SESSION_DRIVER' => 'database',
                'CACHE_STORE' => 'database',
            ]);

            Setting::setValue('store_name', $storeName);

            Artisan::call('storage:link', ['--force' => true]);

            $this->installationState->removeInstallerKey();

            $this->installationState->markAsInstalled();

            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            Artisan::call('event:cache');
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'store_name' => $e->getMessage() !== '' ? $e->getMessage() : $e::class,
            ]);
        }

        return Inertia::location(route('installer.completion.show', [], false));
    }
}
