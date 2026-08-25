<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use RectorLaravel\Rector\ArrayDimFetch\EnvVariableToEnvHelperRector;
use RectorLaravel\Rector\Class_\AddHasFactoryToModelsRector;
use RectorLaravel\Rector\FuncCall\FactoryFuncCallToStaticCallRector;
use RectorLaravel\Rector\FuncCall\RemoveDumpDataDeadCodeRector;
use RectorLaravel\Rector\MethodCall\AssertStatusToAssertMethodRector;
use RectorLaravel\Rector\MethodCall\EloquentWhereRelationTypeHintingParameterRector;
use RectorLaravel\Rector\MethodCall\EloquentWhereTypeHintClosureParameterRector;
use RectorLaravel\Rector\MethodCall\JsonCallToExplicitJsonCallRector;
use RectorLaravel\Rector\MethodCall\WhereToWhereLikeRector;
use RectorLaravel\Rector\PropertyFetch\OptionalToNullsafeOperatorRector;
use RectorLaravel\Rector\StaticCall\AssertWithClassStringToTypeHintedClosureRector;
use RectorLaravel\Rector\StaticCall\CarbonSetTestNowToTravelToRector;
use RectorLaravel\Rector\StaticCall\RouteActionCallableRector;
use RectorLaravel\Set\LaravelSetList;
use RectorLaravel\Set\LaravelSetProvider;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/bootstrap/app.php',
        __DIR__ . '/database',
        __DIR__ . '/public',
    ])
    ->withSkip([
        EnvVariableToEnvHelperRector::class => [
            __DIR__ . '/app/Installer/EnvWriter.php',
            __DIR__ . '/app/Providers/InstallerServiceProvider.php',
        ],
        // Mediable is a pivot with no factory, so the trait would only break PHPStan's
        // generic check for a factory that does not exist.
        AddHasFactoryToModelsRector::class => [
            __DIR__ . '/app/Models/Mediable.php',
        ],
    ])
    ->withSetProviders(LaravelSetProvider::class)
    ->withComposerBased(laravel: true)
    ->withCache(
        cacheDirectory: '/tmp/rector',
        cacheClass: FileCacheStorage::class,
    )
    ->withPhpSets()
    ->withImportNames(importDocBlockNames: false, removeUnusedImports: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
    )
    ->withSets([
        LaravelSetList::LARAVEL_ARRAYACCESS_TO_METHOD_CALL,
        LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_CONTAINER_STRING_TO_FULLY_QUALIFIED_NAME,
        LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
        LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
        LaravelSetList::LARAVEL_FACTORIES,
        LaravelSetList::LARAVEL_LEGACY_FACTORIES_TO_CLASSES,
    ])
    ->withRules([
        AssertStatusToAssertMethodRector::class,
        AssertWithClassStringToTypeHintedClosureRector::class,
        CarbonSetTestNowToTravelToRector::class,
        EloquentWhereRelationTypeHintingParameterRector::class,
        EloquentWhereTypeHintClosureParameterRector::class,
        FactoryFuncCallToStaticCallRector::class,
        JsonCallToExplicitJsonCallRector::class,
        OptionalToNullsafeOperatorRector::class,
        RemoveDumpDataDeadCodeRector::class,
        RouteActionCallableRector::class,
        WhereToWhereLikeRector::class,
    ]);
