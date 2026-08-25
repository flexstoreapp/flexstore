<?php

declare(strict_types=1);

use App\Actions\DestroyRegionAction;
use App\Models\Region;

covers(DestroyRegionAction::class);

uses()->group('actions', 'region');

test('deletes a region', function () {
    $region = Region::factory()->create();

    $action = new DestroyRegionAction();
    $result = $action->handle($region);

    expect($result)->toBeTrue()
        ->and(Region::query()->count())->toBe(0);
});

test('returns false when region does not exist', function () {
    $region = new Region();
    $region->id = 999; // Non-existent ID

    $action = new DestroyRegionAction();
    $result = $action->handle($region);

    expect($result)->toBeFalse();
});

test('deletes multiple regions independently', function () {
    $region1 = Region::factory()->create();
    $region2 = Region::factory()->create();

    $action = new DestroyRegionAction();

    $result1 = $action->handle($region1);
    $result2 = $action->handle($region2);

    expect($result1)->toBeTrue()
        ->and($result2)->toBeTrue()
        ->and(Region::query()->count())->toBe(0);
});

test('preserves other regions when deleting one', function () {
    $regionToDelete = Region::factory()->create();
    $regionToKeep = Region::factory()->create();

    $action = new DestroyRegionAction();
    $action->handle($regionToDelete);

    expect(Region::query()->count())->toBe(1)
        ->and(Region::query()->first()->id)->toBe($regionToKeep->id);
});
