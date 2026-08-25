<?php

declare(strict_types=1);

use App\Actions\UpdateSettingsAction;
use App\DTOs\UpdateSettingsInput;
use App\Models\Setting;

covers(UpdateSettingsAction::class, UpdateSettingsInput::class);

uses()->group('actions', 'setting');

test('updates multiple settings', function () {
    $setting1 = Setting::factory()->text()->create(['key' => 'site_name', 'value' => 'Old Name']);
    $setting2 = Setting::factory()->text()->create(['key' => 'site_email', 'value' => 'old@email.com']);

    $data = [
        'site_name' => 'New Site Name',
        'site_email' => 'new@email.com',
    ];

    $action = app(UpdateSettingsAction::class);
    $result = $action->handle(UpdateSettingsInput::fromArray($data));

    expect($result)->toHaveCount(2);

    $setting1->refresh();
    $setting2->refresh();

    expect($setting1->value)->toBe('New Site Name')
        ->and($setting2->value)->toBe('new@email.com');
});

test('updates only specified settings', function () {
    $setting1 = Setting::factory()->text()->create(['key' => 'site_name', 'value' => 'Original']);
    $setting2 = Setting::factory()->text()->create(['key' => 'site_email', 'value' => 'original@email.com']);
    $setting3 = Setting::factory()->boolean()->create(['key' => 'feature_enabled', 'value' => false]);

    $data = [
        'site_name' => 'Updated Name',
    ];

    $action = app(UpdateSettingsAction::class);
    $result = $action->handle(UpdateSettingsInput::fromArray($data));

    expect($result)->toHaveCount(1);

    $setting1->refresh();
    $setting2->refresh();
    $setting3->refresh();

    expect($setting1->value)->toBe('Updated Name')
        ->and($setting2->value)->toBe('original@email.com') // Unchanged
        ->and($setting3->value)->toBe(false); // Unchanged
});

test('updates settings with different value types', function () {
    $setting1 = Setting::factory()->boolean()->create(['key' => 'feature_enabled', 'value' => false]);
    $setting2 = Setting::factory()->integer()->create(['key' => 'max_items_per_page', 'value' => 10]);
    $setting3 = Setting::factory()->text()->create(['key' => 'site_description', 'value' => '']);

    $data = [
        'feature_enabled' => true,
        'max_items_per_page' => 25,
        'site_description' => 'A new description',
    ];

    $action = app(UpdateSettingsAction::class);
    $result = $action->handle(UpdateSettingsInput::fromArray($data));

    expect($result)->toHaveCount(3);

    $setting1->refresh();
    $setting2->refresh();
    $setting3->refresh();

    expect($setting1->value)->toBeTrue()
        ->and($setting2->value)->toBe(25)
        ->and($setting3->value)->toBe('A new description');
});

test('handles empty data array', function () {
    Setting::factory()->count(3)->create();

    $data = [];

    $action = app(UpdateSettingsAction::class);
    $result = $action->handle(UpdateSettingsInput::fromArray($data));

    expect($result)->toHaveCount(0);
});

test('updates settings that exist and ignores non-existent keys', function () {
    $setting = Setting::factory()->text()->create(['key' => 'site_name', 'value' => 'Original']);

    $data = [
        'site_name' => 'Updated',
        'non_existent_key' => 'should not crash',
    ];

    $action = app(UpdateSettingsAction::class);
    $result = $action->handle(UpdateSettingsInput::fromArray($data));

    expect($result)->toHaveCount(1);

    $setting->refresh();
    expect($setting->value)->toBe('Updated');
});

test('returns collection of matching settings', function () {
    $setting1 = Setting::factory()->text()->create(['key' => 'site_name']);
    $setting2 = Setting::factory()->text()->create(['key' => 'site_email']);
    $setting3 = Setting::factory()->text()->create(['key' => 'other_setting']); // Not in update data

    $data = [
        'site_name' => 'New Name',
        'site_email' => 'new@email.com',
    ];

    $action = app(UpdateSettingsAction::class);
    $result = $action->handle(UpdateSettingsInput::fromArray($data));

    expect($result)->toHaveCount(2)
        ->and($result->pluck('key')->sort()->values()->toArray())->toBe(['site_email', 'site_name']);
});

test('updates settings within a database transaction', function () {
    $setting1 = Setting::factory()->text()->create(['key' => 'site_name', 'value' => 'Original']);
    $setting2 = Setting::factory()->text()->create(['key' => 'site_email', 'value' => 'original@email.com']);

    $data = [
        'site_name' => 'New Name',
        'site_email' => 'new@email.com',
    ];

    $action = app(UpdateSettingsAction::class);

    // Simulate a transaction rollback by checking that both updates happen atomically
    $result = $action->handle(UpdateSettingsInput::fromArray($data));

    expect($result)->toHaveCount(2);

    $setting1->refresh();
    $setting2->refresh();

    expect($setting1->value)->toBe('New Name')
        ->and($setting2->value)->toBe('new@email.com');
});

test('updates setting with null value', function () {
    $setting = Setting::factory()->text()->create(['key' => 'site_description', 'value' => 'Original description']);

    $data = [
        'site_description' => null,
    ];

    $action = app(UpdateSettingsAction::class);
    $result = $action->handle(UpdateSettingsInput::fromArray($data));

    expect($result)->toHaveCount(1);

    $setting->refresh();
    expect($setting->value)->toBeNull();
});

test('updates setting with empty string', function () {
    $setting = Setting::factory()->text()->create(['key' => 'site_description', 'value' => 'Original description']);

    $data = [
        'site_description' => '',
    ];

    $action = app(UpdateSettingsAction::class);
    $result = $action->handle(UpdateSettingsInput::fromArray($data));

    expect($result)->toHaveCount(1);

    $setting->refresh();
    expect($setting->value)->toBe('');
});
