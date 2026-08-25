<?php

declare(strict_types=1);

use App\Actions\UpdateRegionAction;
use App\DTOs\UpdateRegionInput;
use App\Models\Region;

covers(UpdateRegionAction::class, UpdateRegionInput::class);

uses()->group('actions', 'region');

test('updates a region with all fields', function () {
    $region = Region::factory()->active()->create();

    $data = [
        'name' => 'Updated Region',
        'countries' => ['CA', 'MX'],
        'states' => ['ON', 'QC'],
        'postal_codes' => ['M5V3L9', 'H3B2J5'],
        'is_active' => false,
    ];

    $action = new UpdateRegionAction();
    $result = $action->handle($region, UpdateRegionInput::fromArray($data));

    expect($result)->toBeInstanceOf(Region::class)
        ->and($result->name)->toBe('Updated Region')
        ->and($result->countries)->toBe(['CA', 'MX'])
        ->and($result->states)->toBe(['ON', 'QC'])
        ->and($result->postal_codes)->toBe(['M5V3L9', 'H3B2J5'])
        ->and($result->is_active)->toBeFalse();

    $region->refresh();
    expect($region->name)->toBe('Updated Region')
        ->and($region->is_active)->toBeFalse();
});

test('updates only specified fields', function () {
    $region = Region::factory()->active()->create([
        'name' => 'Original Name',
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
    ]);

    $data = [
        'name' => 'New Name Only',
    ];

    $action = new UpdateRegionAction();
    $result = $action->handle($region, UpdateRegionInput::fromArray($data));

    expect($result->name)->toBe('New Name Only')
        ->and($result->countries)->toBe(['US']) // Unchanged
        ->and($result->states)->toBe(['CA']) // Unchanged
        ->and($result->postal_codes)->toBe(['90210']) // Unchanged
        ->and($result->is_active)->toBeTrue(); // Unchanged
});

test('handles empty arrays correctly', function () {
    $region = Region::factory()->create([
        'states' => ['CA', 'NY'],
        'postal_codes' => ['90210', '10001'],
    ]);

    $data = [
        'states' => [],
        'postal_codes' => [],
    ];

    $action = new UpdateRegionAction();
    $result = $action->handle($region, UpdateRegionInput::fromArray($data));

    expect($result->states)->toBe([])
        ->and($result->postal_codes)->toBe([]);
});

test('preserves original values when fields not provided', function () {
    $originalData = [
        'name' => 'Original Region',
        'countries' => ['US', 'CA'],
        'states' => ['CA', 'NY'],
        'postal_codes' => ['90210'],
        'is_active' => false,
    ];

    $region = Region::factory()->create($originalData);

    $action = new UpdateRegionAction();
    $result = $action->handle($region, UpdateRegionInput::fromArray([])); // Empty data

    expect($result->name)->toBe($originalData['name'])
        ->and($result->countries)->toBe($originalData['countries'])
        ->and($result->states)->toBe($originalData['states'])
        ->and($result->postal_codes)->toBe($originalData['postal_codes'])
        ->and($result->is_active)->toBe($originalData['is_active']);
});

test('toggles is_active status', function () {
    $region = Region::factory()->active()->create();

    $action = new UpdateRegionAction();

    $result = $action->handle($region, UpdateRegionInput::fromArray(['is_active' => false]));
    expect($result->is_active)->toBeFalse();

    $result = $action->handle($region, UpdateRegionInput::fromArray(['is_active' => true]));
    expect($result->is_active)->toBeTrue();
});
