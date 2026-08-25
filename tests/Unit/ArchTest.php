<?php

declare(strict_types=1);

arch()->preset()->php();
arch()->preset()->laravel()->ignoring('Database\Seeders');
arch()->preset()->security()->ignoring('assert');

arch('strict')
    ->expect('App')
    ->classes()
    ->not->toBeAbstract()
    ->toUseStrictTypes()
    ->toUseStrictEquality()
    ->expect(['sleep', 'usleep'])
    ->not->toBeUsed();

arch('exceptions')
    ->expect('App\Exceptions')
    ->toHaveSuffix('Exception');

arch('controllers')
    ->expect('App\Http\Controllers')
    ->not->toBeUsed()
    ->not->toBeAbstract()
    ->toHaveSuffix('Controller');

arch('requests')
    ->expect('App\Http\Requests')
    ->toHaveSuffix('Request');

arch('actions')
    ->expect('App\Actions')
    ->toHaveSuffix('Action');

arch('filter configs')
    ->expect('App\Filters\Configs')
    ->toHaveSuffix('Config');

arch('filter criteria')
    ->expect('App\Filters\Criteria')
    ->toHaveSuffix('Criterion');

arch('filter strategies')
    ->expect('App\Filters\Strategies')
    ->toHaveSuffix('Strategy');

arch('actions have handle method')
    ->expect('App\Actions')
    ->toHaveMethod('handle');

arch('queries have execute method')
    ->expect('App\Queries')
    ->toHaveMethod('execute');
