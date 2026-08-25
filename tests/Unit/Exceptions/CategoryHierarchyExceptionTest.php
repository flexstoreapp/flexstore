<?php

declare(strict_types=1);

use App\Exceptions\CategoryHierarchyException;
use Symfony\Component\HttpKernel\Exception\HttpException;

covers(CategoryHierarchyException::class);

uses()->group('exceptions');

test('has the expected default message', function () {
    $exception = new CategoryHierarchyException();

    expect($exception->getMessage())->toBe('Cannot move a category under its own descendant.');
});

test('render aborts with http 400', function () {
    $exception = new CategoryHierarchyException();

    expect(fn () => $exception->render())
        ->toThrow(HttpException::class, 'Cannot move a category under its own descendant.');

    try {
        $exception->render();
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(400);
    }
});
