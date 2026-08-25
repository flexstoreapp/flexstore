<?php

declare(strict_types=1);

use App\Actions\StoreRegionAction;
use App\DTOs\StoreRegionInput;
use App\Models\Region;

covers(StoreRegionAction::class, StoreRegionInput::class);

uses()->group('actions', 'region');

test('creates a region with required fields', function () {
    $data = [
        'name' => 'Test Region',
        'countries' => ['US', 'CA'],
    ];

    $action = new StoreRegionAction();
    $result = $action->handle(StoreRegionInput::fromArray($data));

    expect($result)->toBeInstanceOf(Region::class)
        ->and($result->name)->toBe('Test Region')
        ->and($result->countries)->toBe(['US', 'CA'])
        ->and($result->states)->toBe([])
        ->and($result->postal_codes)->toBe([])
        ->and($result->is_active)->toBeTrue()
        ->and(Region::query()->count())->toBe(1);
});

test('creates a region with all fields', function () {
    $data = [
        'name' => 'Full Region',
        'countries' => ['US'],
        'states' => ['CA', 'NY', 'TX'],
        'postal_codes' => ['90210', '10001'],
        'is_active' => false,
    ];

    $action = new StoreRegionAction();
    $result = $action->handle(StoreRegionInput::fromArray($data));

    expect($result->name)->toBe('Full Region')
        ->and($result->countries)->toBe(['US'])
        ->and($result->states)->toBe(['CA', 'NY', 'TX'])
        ->and($result->postal_codes)->toBe(['90210', '10001'])
        ->and($result->is_active)->toBeFalse();
});

test('defaults optional fields when not provided', function () {
    $data = [
        'name' => 'Default Region',
        'countries' => ['GB'],
    ];

    $action = new StoreRegionAction();
    $result = $action->handle(StoreRegionInput::fromArray($data));

    expect($result->states)->toBe([])
        ->and($result->postal_codes)->toBe([])
        ->and($result->is_active)->toBeTrue();
});

test('creates region with empty states and postal_codes arrays', function () {
    $data = [
        'name' => 'Empty Arrays Region',
        'countries' => ['FR'],
        'states' => [],
        'postal_codes' => [],
        'is_active' => true,
    ];

    $action = new StoreRegionAction();
    $result = $action->handle(StoreRegionInput::fromArray($data));

    expect($result->states)->toBe([])
        ->and($result->postal_codes)->toBe([]);
});

test('creates multiple regions', function () {
    $action = new StoreRegionAction();

    $region1 = $action->handle(StoreRegionInput::fromArray([
        'name' => 'Region 1',
        'countries' => ['US'],
    ]));

    $region2 = $action->handle(StoreRegionInput::fromArray([
        'name' => 'Region 2',
        'countries' => ['CA'],
    ]));

    expect(Region::query()->count())->toBe(2)
        ->and($region1->name)->toBe('Region 1')
        ->and($region2->name)->toBe('Region 2');
});
