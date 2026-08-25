<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\ResolveVisitorCartAction;
use App\Enums\Permission;
use App\Enums\Role;
use App\Installer\Contracts\EnvWriter as EnvWriterContract;
use App\Installer\EnvWriter;
use App\Models\User;
use App\Utilities\PermissionResolver;
use App\Utilities\Translations;
use App\View\Composers\EmailLayoutComposer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Head\ErrorPages;
use Laravel\Head\Facades\Head;
use Laravel\Head\HeadBuilder;
use Laravel\Passkeys\Passkeys;
use Override;

final class AppServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->scoped(EnvWriterContract::class, EnvWriter::class);
        $this->app->scoped(ResolveVisitorCartAction::class);

        Passkeys::ignoreRoutes();
    }

    public function boot(): void
    {
        $this->configureAuthorization();
        $this->configurePassword();
        $this->configureCommands();
        $this->configureModels();
        $this->configureDates();
        $this->configureUrls();
        $this->configureVite();
        $this->configureEmailViews();
        $this->configureTranslations();
        $this->configureHead();
    }

    private function configureHead(): void
    {
        Head::errors(function (ErrorPages $errors): void {
            $errors->defaults(fn (HeadBuilder $head): HeadBuilder => $head->hiddenFromRobots());
        });
    }

    private function configureTranslations(): void
    {
        foreach (Translations::BUNDLES as $bundle) {
            Lang::addJsonPath(lang_path($bundle));
        }
    }

    private function configureEmailViews(): void
    {
        View::composer('emails.*', EmailLayoutComposer::class);
    }

    private function configureAuthorization(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->hasRole(Role::SuperAdmin)) {
                return true;
            }

            foreach (PermissionResolver::grantedBy($ability) as $permission) {
                if ($user->checkPermissionTo($permission->value)) {
                    return true;
                }
            }

            return null;
        });

        Gate::define('settings.access', fn (User $user): bool => $user->hasAnyPermission(Permission::settings()));
    }

    private function configurePassword(): void
    {
        Password::defaults(fn () => app()->isProduction() ? Password::min(8)->max(255)->uncompromised() : null);
    }

    private function configureCommands(): void
    {
        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );
    }

    private function configureDates(): void
    {
        Date::use(CarbonImmutable::class);
    }

    private function configureModels(): void
    {
        Model::unguard();
        Model::shouldBeStrict();
        Model::automaticallyEagerLoadRelationships();
    }

    private function configureUrls(): void
    {
        URL::forceHttps(str_starts_with((string) config('app.url'), 'https://'));
    }

    private function configureVite(): void
    {
        Vite::useAggressivePrefetching();
    }
}
