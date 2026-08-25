<?php

declare(strict_types=1);

namespace Tests\Unit\Filters;

use App\Filters\Contracts\Criterion;
use App\Filters\Criteria\SortCriterion;
use App\Filters\CriteriaCollection;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

covers(CriteriaCollection::class);

uses()->group('filters');

function createTestCriterion(
    bool $canApply = true,
    string $whereClause = 'test_column',
    mixed $whereValue = 'test_value'
): Criterion {
    return new class($canApply, $whereClause, $whereValue) implements Criterion
    {
        public function __construct(
            private readonly bool $canApply,
            private readonly string $whereClause,
            private readonly mixed $whereValue
        ) {
        }

        public function canApply(mixed $value): bool
        {
            return $this->canApply;
        }

        public function apply(Builder $builder, mixed $value): Builder
        {
            return $builder->where($this->whereClause, $this->whereValue);
        }
    };
}

test('can create empty CriteriaCollection', function () {
    $collection = new CriteriaCollection();

    expect($collection->keys())->toBeEmpty();
});

test('can add criterion to collection', function () {
    $collection = new CriteriaCollection();
    $criterion = createTestCriterion();

    $result = $collection->add('test_key', $criterion);

    expect($result)->toBe($collection)
        ->and($collection->has('test_key'))->toBeTrue()
        ->and($collection->get('test_key'))->toBe($criterion);
});

test('can check if collection has criterion', function () {
    $collection = new CriteriaCollection();

    expect($collection->has('test_key'))->toBeFalse();

    $collection->add('test_key', createTestCriterion());

    expect($collection->has('test_key'))->toBeTrue();
});

test('can get criterion from collection', function () {
    $collection = new CriteriaCollection();
    $criterion = createTestCriterion();

    expect($collection->get('non_existent'))->toBeNull();

    $collection->add('test_key', $criterion);

    expect($collection->get('test_key'))->toBe($criterion);
});

test('can get all keys from collection', function () {
    $collection = new CriteriaCollection();

    expect($collection->keys())->toBeEmpty();

    $collection->add('key1', createTestCriterion());
    $collection->add('key2', createTestCriterion());

    expect($collection->keys())->toBe(['key1', 'key2']);
});

test('applies filters correctly when criteria exist and can apply', function () {
    $collection = new CriteriaCollection();
    $criterion = createTestCriterion(true, 'name', 'John');
    $builder = User::query();

    $collection->add('test_key', $criterion);

    $result = $collection->apply($builder, ['test_key' => 'test_value']);

    expect($result->toSql())->toContain('where');
    expect($result->getBindings())->toContain('John');
});

test('skips filters when criteria cannot apply', function () {
    $collection = new CriteriaCollection();
    $criterion = createTestCriterion(canApply: false);
    $builder = User::query();
    $originalSql = $builder->toSql();

    $collection->add('test_key', $criterion);

    $result = $collection->apply($builder, ['test_key' => 'test_value']);

    // SQL should remain unchanged since filter was skipped
    expect($result->toSql())->toBe($originalSql);
});

test('skips filters when criteria do not exist', function () {
    $collection = new CriteriaCollection();
    $builder = User::query();
    $originalSql = $builder->toSql();

    $result = $collection->apply($builder, ['non_existent_key' => 'test_value']);

    // SQL should remain unchanged since no criteria exist
    expect($result->toSql())->toBe($originalSql);
});

test('applies multiple filters correctly', function () {
    $collection = new CriteriaCollection();
    $criterion1 = createTestCriterion(true, 'name', 'John');
    $criterion2 = createTestCriterion(true, 'email', 'john@example.com');
    $builder = User::query();

    $collection->add('key1', $criterion1);
    $collection->add('key2', $criterion2);

    $result = $collection->apply($builder, [
        'key1' => 'value1',
        'key2' => 'value2',
    ]);

    $sql = $result->toSql();
    expect($sql)->toContain('where')->toContain('and');
    expect($result->getBindings())->toBe(['John', 'john@example.com']);
});

test('applies only applicable filters in mixed scenario', function () {
    $collection = new CriteriaCollection();
    $criterion1 = createTestCriterion(canApply: true, whereClause: 'name', whereValue: 'John');
    $criterion2 = createTestCriterion(canApply: false, whereClause: 'email', whereValue: 'john@example.com'); // Cannot apply
    $builder = User::query();

    $collection->add('key1', $criterion1);
    $collection->add('key2', $criterion2);

    $result = $collection->apply($builder, [
        'key1' => 'value1',
        'key2' => 'value2',
    ]);

    // Only the first filter should be applied
    $sql = $result->toSql();
    expect($sql)->toContain('where')->not->toContain('email');
    expect($result->getBindings())->toBe(['John']);
});

test('returns same builder when no filters match', function () {
    $collection = new CriteriaCollection();
    $criterion = createTestCriterion();
    $builder = User::query();

    $collection->add('existing_key', $criterion);

    $result = $collection->apply($builder, ['different_key' => 'value']);

    expect($result)->toBe($builder);
});

test('handles empty filters array', function () {
    $collection = new CriteriaCollection();
    $criterion = createTestCriterion();
    $builder = User::query();

    $collection->add('test_key', $criterion);

    $result = $collection->apply($builder, []);

    expect($result)->toBe($builder);
});

test('applies default sorting when sort criterion exists but no sort filter provided', function () {
    $collection = new CriteriaCollection();
    $sortCriterion = new SortCriterion(['name', 'email', 'created_at'], 'created_at', defaultDirection: 'desc');
    $builder = User::query();

    $collection->add('sort', $sortCriterion);

    $result = $collection->apply($builder, []);

    $sql = $result->toSql();
    expect($sql)->toContain('order by')
        ->and($sql)->toContain('created_at')
        ->and($sql)->toContain('desc');
});

test('does not apply default sorting when sort filter is provided', function () {
    $collection = new CriteriaCollection();
    $sortCriterion = new SortCriterion(['name', 'email', 'created_at'], 'created_at', defaultDirection: 'desc');
    $builder = User::query();

    $collection->add('sort', $sortCriterion);

    $result = $collection->apply($builder, ['sort' => 'name']);

    $sql = $result->toSql();
    expect($sql)->toContain('order by')
        ->and($sql)->toContain('name')
        ->and($sql)->not->toContain('created_at');
});

test('does not apply default sorting when sort criterion does not exist', function () {
    $collection = new CriteriaCollection();
    $criterion = createTestCriterion();
    $builder = User::query();
    $originalSql = $builder->toSql();

    $collection->add('test_key', $criterion);

    $result = $collection->apply($builder, []);

    // Should not have order by clause
    expect($result->toSql())->not->toContain('order by');
});
