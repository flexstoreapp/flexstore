<?php

declare(strict_types=1);

uses()->group('demo-data');

test('demo data prices use two decimal places', function () {
    $sql = (string) file_get_contents(resource_path('demo/demo-data.sql'));

    preg_match_all('/(?<![\d.])(\d+\.\d{3,})(?![\d])/', $sql, $matches);

    expect(array_values(array_unique($matches[1])))->toEqualCanonicalizing(['1.0000'])
        ->and($sql)->toContain('499.99')
        ->and($sql)->not->toContain('499.9900');
});
