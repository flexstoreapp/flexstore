<?php

declare(strict_types=1);

use App\Enums\ProductRelationType;

uses()->group('demo-data');

function demoRelationRows(): array
{
    $sql = (string) file_get_contents(resource_path('demo/demo-data.sql'));

    expect($sql)->toContain('INSERT INTO product_relations');

    preg_match('/INSERT INTO product_relations[^;]+VALUES\s*(.*?);/s', $sql, $statement);

    preg_match_all("/\((\d+), (\d+), (\d+), '([a-z_]+)', (\d+),/", $statement[1], $matches, PREG_SET_ORDER);

    return array_map(fn (array $row): array => [
        'id' => (int) $row[1],
        'product_id' => (int) $row[2],
        'related_product_id' => (int) $row[3],
        'relation_type' => $row[4],
        'sort_order' => (int) $row[5],
    ], $matches);
}

function demoProductIds(): array
{
    $sql = (string) file_get_contents(resource_path('demo/demo-data.sql'));

    preg_match('/INSERT INTO products \(id,[^;]+VALUES\s*(.*?);\s*\n/s', $sql, $statement);

    preg_match_all('/^\((\d+), /m', $statement[1], $matches);

    return array_map(intval(...), $matches[1]);
}

test('demo data ships both relation types', function () {
    $types = array_values(array_unique(array_column(demoRelationRows(), 'relation_type')));

    expect($types)->toEqualCanonicalizing([
        ProductRelationType::CrossSell->value,
        ProductRelationType::UpSell->value,
    ]);
});

test('demo relations point at products that exist', function () {
    $productIds = demoProductIds();

    foreach (demoRelationRows() as $row) {
        expect($productIds)->toContain($row['product_id'])
            ->and($productIds)->toContain($row['related_product_id']);
    }
});

test('no demo product relates to itself', function () {
    foreach (demoRelationRows() as $row) {
        expect($row['product_id'])->not->toBe($row['related_product_id']);
    }
});

test('demo relation ids and pairs are unique', function () {
    $rows = demoRelationRows();

    $ids = array_column($rows, 'id');
    $pairs = array_map(
        fn (array $row): string => "{$row['product_id']}-{$row['related_product_id']}-{$row['relation_type']}",
        $rows,
    );

    expect($ids)->toEqualCanonicalizing(array_values(array_unique($ids)))
        ->and($pairs)->toEqualCanonicalizing(array_values(array_unique($pairs)));
});

test('demo relations are numbered from zero within each list', function () {
    $lists = [];

    foreach (demoRelationRows() as $row) {
        $lists["{$row['product_id']}-{$row['relation_type']}"][] = $row['sort_order'];
    }

    foreach ($lists as $sortOrders) {
        expect($sortOrders)->toBe(range(0, count($sortOrders) - 1));
    }
});
