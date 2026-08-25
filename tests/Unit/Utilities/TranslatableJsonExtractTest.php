<?php

declare(strict_types=1);

use App\Utilities\TranslatableJsonExtract;
use Illuminate\Support\Facades\DB;

covers(TranslatableJsonExtract::class);

uses()->group('utilities');

test('expression uses pgsql ->> operator with bare locale binding', function () {
    DB::shouldReceive('getDriverName')->andReturn('pgsql');

    expect(TranslatableJsonExtract::expression('title', 'en'))
        ->toBe(['title->>?', ['en']]);
});

test('expression uses sqlite JSON_EXTRACT with $.locale path', function () {
    DB::shouldReceive('getDriverName')->andReturn('sqlite');

    expect(TranslatableJsonExtract::expression('title', 'ar'))
        ->toBe(['JSON_EXTRACT(title, ?)', ['$.ar']]);
});

test('expression uses mysql JSON_UNQUOTE wrapping for default driver', function () {
    DB::shouldReceive('getDriverName')->andReturn('mysql');

    expect(TranslatableJsonExtract::expression('name', 'bn'))
        ->toBe(['JSON_UNQUOTE(JSON_EXTRACT(name, ?))', ['$.bn']]);
});

test('expression supports table-qualified column names', function () {
    DB::shouldReceive('getDriverName')->andReturn('pgsql');

    expect(TranslatableJsonExtract::expression('brands.name', 'en'))
        ->toBe(['brands.name->>?', ['en']]);
});

test('expression falls back to current app locale when none given', function () {
    DB::shouldReceive('getDriverName')->andReturn('pgsql');
    app()->setLocale('ar');

    expect(TranslatableJsonExtract::expression('title'))
        ->toBe(['title->>?', ['ar']]);
});

test('orderByExpression appends asc suffix and validates direction', function () {
    DB::shouldReceive('getDriverName')->andReturn('pgsql');

    expect(TranslatableJsonExtract::orderByExpression('title', 'asc', 'en'))
        ->toBe(['title->>? asc', ['en']]);
});

test('orderByExpression appends desc suffix', function () {
    DB::shouldReceive('getDriverName')->andReturn('sqlite');

    expect(TranslatableJsonExtract::orderByExpression('title', 'desc', 'en'))
        ->toBe(['JSON_EXTRACT(title, ?) desc', ['$.en']]);
});

test('orderByExpression sanitizes unknown direction to asc', function () {
    DB::shouldReceive('getDriverName')->andReturn('pgsql');

    expect(TranslatableJsonExtract::orderByExpression('title', 'drop table', 'en'))
        ->toBe(['title->>? asc', ['en']]);
});

test('orderByExpression handles uppercase direction', function () {
    DB::shouldReceive('getDriverName')->andReturn('pgsql');

    expect(TranslatableJsonExtract::orderByExpression('title', 'DESC', 'en'))
        ->toBe(['title->>? desc', ['en']]);
});
