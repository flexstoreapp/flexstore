<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Override;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    #[Override]
    protected function setUpTraits(): array
    {
        $this->guardAgainstRefreshingTheDevelopmentDatabase();

        return parent::setUpTraits();
    }

    private function guardAgainstRefreshingTheDevelopmentDatabase(): void
    {
        if ($this->app->configurationIsCached()) {
            throw new RuntimeException(
                'The configuration is cached, so the environment in phpunit.xml is ignored and the suite would '
                . 'run against your development database. Run "php artisan config:clear" and try again.',
            );
        }

        $connection = (string) config('database.default');
        $database = config("database.connections.{$connection}.database");
        $allowedDatabase = env('PEST_ALLOW_DATABASE');

        if ($database === ':memory:' || ($allowedDatabase !== null && $allowedDatabase !== '' && $database === $allowedDatabase)) {
            return;
        }

        throw new RuntimeException(sprintf(
            'The suite expects the in-memory database but the [%s] connection resolved to [%s]. Refusing to '
            . 'run so the data is not migrated away. Set PEST_ALLOW_DATABASE to the exact database name to opt in.',
            $connection,
            is_scalar($database) ? (string) $database : get_debug_type($database),
        ));
    }
}
