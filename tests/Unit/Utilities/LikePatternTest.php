<?php

declare(strict_types=1);

use App\Utilities\LikePattern;

covers(LikePattern::class);

uses()->group('utilities');

test('escapes the wildcard characters', function () {
    expect(LikePattern::escape('100%'))->toBe('100!%')
        ->and(LikePattern::escape('a_b'))->toBe('a!_b');
});

test('escapes the escape character itself first', function () {
    expect(LikePattern::escape('!'))->toBe('!!')
        ->and(LikePattern::escape('!%'))->toBe('!!!%');
});

test('leaves plain values untouched', function () {
    expect(LikePattern::escape('shoes'))->toBe('shoes')
        ->and(LikePattern::escape(''))->toBe('');
});

test('exposes the escape clause used by queries', function () {
    expect(LikePattern::ESCAPE_CLAUSE)->toBe("escape '!'");
});
