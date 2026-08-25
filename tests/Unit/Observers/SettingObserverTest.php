<?php

declare(strict_types=1);

use App\Enums\SettingGroup;
use App\Models\Setting;
use App\Observers\SettingObserver;
use Illuminate\Support\Facades\Cache;

covers(SettingObserver::class);

uses()->group('observers', 'setting');

test('observer is called when setting is saved', function () {
    $setting = Setting::factory()->text()->create([
        'key' => 'test_observer_key',
        'value' => 'test_value',
    ]);

    Cache::put("settings.{$setting->key}", 'cached_value', now()->addHour());
    Cache::memo('array')->put("memo:settings.{$setting->key}", 'cached_value', now()->addHour());

    expect(Cache::has("settings.{$setting->key}"))->toBeTrue();
    expect(Cache::memo('array')->has("memo:settings.{$setting->key}"))->toBeTrue();

    $setting->update(['value' => 'updated_value']);

    expect(Cache::has("settings.{$setting->key}"))->toBeFalse();
    expect(Cache::memo('array')->has("memo:settings.{$setting->key}"))->toBeFalse();
});

test('saved method clears cache for the specific setting key', function () {
    $setting = new Setting([
        'key' => 'test_key',
    ]);

    Cache::put('settings.test_key', 'test_value', now()->addHour());
    Cache::memo('array')->put('memo:settings.test_key', 'test_value', now()->addHour());

    expect(Cache::has('settings.test_key'))->toBeTrue();
    expect(Cache::memo('array')->has('memo:settings.test_key'))->toBeTrue();

    $observer = new SettingObserver();
    $observer->saved($setting);

    expect(Cache::has('settings.test_key'))->toBeFalse();
    expect(Cache::memo('array')->has('memo:settings.test_key'))->toBeFalse();
});

test('saved method clears cache for the setting group', function () {
    $setting = new Setting([
        'key' => 'test_key',
        'group' => SettingGroup::General,
    ]);

    Cache::put('settings.group.general', 'test_value', now()->addHour());
    Cache::memo('array')->put('memo:settings.group.general', 'test_value', now()->addHour());

    expect(Cache::has('settings.group.general'))->toBeTrue();
    expect(Cache::memo('array')->has('memo:settings.group.general'))->toBeTrue();

    $observer = new SettingObserver();
    $observer->saved($setting);

    expect(Cache::has('settings.group.general'))->toBeFalse();
    expect(Cache::memo('array')->has('memo:settings.group.general'))->toBeFalse();
});

test('saved method clears both key and group caches', function () {
    $setting = new Setting([
        'key' => 'test_key',
        'group' => SettingGroup::General,
    ]);

    Cache::put('settings.test_key', 'key_value', now()->addHour());
    Cache::memo('array')->put('memo:settings.test_key', 'key_value', now()->addHour());
    Cache::put('settings.group.general', 'group_value', now()->addHour());
    Cache::memo('array')->put('memo:settings.group.general', 'group_value', now()->addHour());

    expect(Cache::has('settings.test_key'))->toBeTrue();
    expect(Cache::memo('array')->has('memo:settings.test_key'))->toBeTrue();
    expect(Cache::has('settings.group.general'))->toBeTrue();
    expect(Cache::memo('array')->has('memo:settings.group.general'))->toBeTrue();

    $observer = new SettingObserver();
    $observer->saved($setting);

    expect(Cache::has('settings.test_key'))->toBeFalse();
    expect(Cache::memo('array')->has('memo:settings.test_key'))->toBeFalse();
    expect(Cache::has('settings.group.general'))->toBeFalse();
    expect(Cache::memo('array')->has('memo:settings.group.general'))->toBeFalse();
});

test('saved method only clears cache for the specific setting key', function () {
    $setting = new Setting([
        'key' => 'test_key',
    ]);

    Cache::put('settings.test_key', 'test_value', now()->addHour());
    Cache::memo('array')->put('memo:settings.test_key', 'test_value', now()->addHour());
    Cache::put('settings.other_key', 'other_value', now()->addHour());
    Cache::memo('array')->put('memo:settings.other_key', 'other_value', now()->addHour());

    expect(Cache::has('settings.test_key'))->toBeTrue();
    expect(Cache::memo('array')->has('memo:settings.test_key'))->toBeTrue();
    expect(Cache::has('settings.other_key'))->toBeTrue();
    expect(Cache::memo('array')->has('memo:settings.other_key'))->toBeTrue();

    $observer = new SettingObserver();
    $observer->saved($setting);

    expect(Cache::has('settings.test_key'))->toBeFalse();
    expect(Cache::memo('array')->has('memo:settings.test_key'))->toBeFalse();
    expect(Cache::has('settings.other_key'))->toBeTrue();
    expect(Cache::memo('array')->has('memo:settings.other_key'))->toBeTrue();
});

test('saved method only clears cache for the specific setting group', function () {
    $setting = new Setting([
        'key' => 'test_key',
        'group' => SettingGroup::General,
    ]);

    Cache::put('settings.group.general', 'test_value', now()->addHour());
    Cache::memo('array')->put('memo:settings.group.general', 'test_value', now()->addHour());
    Cache::put('settings.group.policy', 'other_value', now()->addHour());
    Cache::memo('array')->put('memo:settings.group.policy', 'other_value', now()->addHour());

    expect(Cache::has('settings.group.general'))->toBeTrue();
    expect(Cache::memo('array')->has('memo:settings.group.general'))->toBeTrue();
    expect(Cache::has('settings.group.policy'))->toBeTrue();
    expect(Cache::memo('array')->has('memo:settings.group.policy'))->toBeTrue();

    $observer = new SettingObserver();
    $observer->saved($setting);

    expect(Cache::has('settings.group.general'))->toBeFalse();
    expect(Cache::memo('array')->has('memo:settings.group.general'))->toBeFalse();
    expect(Cache::has('settings.group.policy'))->toBeTrue();
    expect(Cache::memo('array')->has('memo:settings.group.policy'))->toBeTrue();
});

test('saved method handles setting without group', function () {
    $setting = new Setting([
        'key' => 'test_key',
        'group' => null,
    ]);

    Cache::put('settings.test_key', 'test_value', now()->addHour());
    Cache::memo('array')->put('memo:settings.test_key', 'test_value', now()->addHour());
    Cache::put('settings.group.general', 'group_value', now()->addHour());
    Cache::memo('array')->put('memo:settings.group.general', 'group_value', now()->addHour());

    expect(Cache::has('settings.test_key'))->toBeTrue();
    expect(Cache::memo('array')->has('memo:settings.test_key'))->toBeTrue();
    expect(Cache::has('settings.group.general'))->toBeTrue();
    expect(Cache::memo('array')->has('memo:settings.group.general'))->toBeTrue();

    $observer = new SettingObserver();
    $observer->saved($setting);

    expect(Cache::has('settings.test_key'))->toBeFalse();
    expect(Cache::memo('array')->has('memo:settings.test_key'))->toBeFalse();
    expect(Cache::has('settings.group.general'))->toBeTrue(); // Should not be cleared
    expect(Cache::memo('array')->has('memo:settings.group.general'))->toBeTrue(); // Should not be cleared
});
