<?php

declare(strict_types=1);

use App\Exceptions\UpdateFailedException;

covers(UpdateFailedException::class);

uses()->group('exceptions');

test('stores step, message, and previous throwable', function () {
    $previous = new RuntimeException('original error');

    $exception = new UpdateFailedException(
        step: 'migrate',
        message: 'Migration failed',
        previous: $previous,
    );

    expect($exception->step)->toBe('migrate')
        ->and($exception->getMessage())->toBe('Migration failed')
        ->and($exception->getPrevious())->toBe($previous);
});

test('previous throwable is optional', function () {
    $exception = new UpdateFailedException(step: 'backup', message: 'Backup failed');

    expect($exception->step)->toBe('backup')
        ->and($exception->getPrevious())->toBeNull();
});
