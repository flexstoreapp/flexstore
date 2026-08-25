<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\SettingGroup;
use App\Enums\SettingType;
use App\Models\Media;
use App\Models\Setting;
use App\Observers\SettingObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;

covers(Setting::class);

uses()->group('models', 'setting');

test('setting model is observed by SettingObserver', function () {
    $reflectionClass = new ReflectionClass(Setting::class);
    $attributes = $reflectionClass->getAttributes(ObservedBy::class);

    expect($attributes)->toHaveCount(1);

    $observerAttribute = $attributes[0];
    $arguments = $observerAttribute->getArguments();

    expect($arguments)->toContain(SettingObserver::class);
});

test('has factory', function () {
    expect(Setting::factory())->toBeInstanceOf(Factory::class);
});

test('casts attributes correctly', function () {
    $setting = new Setting();
    $casts = $setting->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('type', SettingType::class);
});

test('value accessor returns original value for text type', function () {
    $setting = new Setting([
        'type' => SettingType::Text,
        'value' => 'text value',
    ]);

    expect($setting->value)->toBeString()->toBe('text value');
});

test('value accessor correctly handles boolean type', function () {
    $setting = new Setting([
        'type' => SettingType::Boolean,
        'value' => '1',
    ]);

    expect($setting->value)->toBeBool()->toBeTrue();

    $setting = new Setting([
        'type' => SettingType::Boolean,
        'value' => '0',
    ]);

    expect($setting->value)->toBeBool()->toBeFalse();
});

test('value accessor correctly handles integer type', function () {
    $setting = new Setting([
        'type' => SettingType::Integer,
        'value' => '42',
    ]);

    expect($setting->value)->toBeInt()->toBe(42);

    $setting = new Setting([
        'type' => SettingType::Integer,
        'value' => '42.5',
    ]);

    expect($setting->value)->toBeInt()->toBe(42);
});

test('value accessor preserves null for integer type', function () {
    $setting = new Setting([
        'type' => SettingType::Integer,
        'value' => null,
    ]);

    expect($setting->value)->toBeNull();
});

test('value accessor normalizes decimal type through BigDecimal', function () {
    $setting = new Setting([
        'type' => SettingType::Decimal,
        'value' => '12.50',
    ]);

    expect($setting->value)->toBeString()->toBe('12.50');

    $setting = new Setting([
        'type' => SettingType::Decimal,
        'value' => '15',
    ]);

    expect($setting->value)->toBeString()->toBe('15');
});

test('value accessor preserves null for decimal type', function () {
    $setting = new Setting([
        'type' => SettingType::Decimal,
        'value' => null,
    ]);

    expect($setting->value)->toBeNull();
});

test('value accessor correctly handles array type', function () {
    $setting = new Setting();
    $reflectionClass = new ReflectionClass($setting);

    $attributes = $reflectionClass->getProperty('attributes');
    $attributes->setAccessible(true);

    $attributes->setValue($setting, [
        'type' => SettingType::Array,
        'value' => '{"key":"value","nested":{"item":true}}',
    ]);

    $arrayValue = $setting->value;

    expect($arrayValue)
        ->toBeArray()
        ->toHaveKey('key', 'value')
        ->toHaveKey('nested.item', true);
});

test('setting boolean value works correctly', function () {
    $setting = new Setting(['type' => SettingType::Boolean]);

    $reflectionClass = new ReflectionClass($setting);
    $valueAttribute = $reflectionClass->getProperty('attributes');
    $valueAttribute->setAccessible(true);

    $attributeSetter = $reflectionClass->getMethod('setAttribute');
    $attributeSetter->setAccessible(true);

    // Set value using setAttribute
    $attributeSetter->invoke($setting, 'value', true);

    // Get the raw attributes
    $attributes = $valueAttribute->getValue($setting);

    expect($attributes['value'])->toBeBool()->toBeTrue();

    $attributeSetter->invoke($setting, 'value', false);
    $attributes = $valueAttribute->getValue($setting);

    expect($attributes['value'])->toBeBool()->toBeFalse();
});

test('setting integer value works correctly', function () {
    $setting = new Setting(['type' => SettingType::Integer]);

    $reflectionClass = new ReflectionClass($setting);
    $valueAttribute = $reflectionClass->getProperty('attributes');
    $valueAttribute->setAccessible(true);

    $attributeSetter = $reflectionClass->getMethod('setAttribute');
    $attributeSetter->setAccessible(true);

    // Set value using setAttribute
    $attributeSetter->invoke($setting, 'value', '100');

    // Get the raw attributes
    $attributes = $valueAttribute->getValue($setting);

    expect($attributes['value'])->toBeInt()->toBe(100);

    $attributeSetter->invoke($setting, 'value', 42.9);
    $attributes = $valueAttribute->getValue($setting);

    expect($attributes['value'])->toBeInt()->toBe(42);

    $attributeSetter->invoke($setting, 'value', null);
    $attributes = $valueAttribute->getValue($setting);

    expect($attributes['value'])->toBeNull();
});

test('setting array value works correctly', function () {
    $setting = new Setting(['type' => SettingType::Array]);

    $reflectionClass = new ReflectionClass($setting);
    $valueAttribute = $reflectionClass->getProperty('attributes');
    $valueAttribute->setAccessible(true);

    $attributeSetter = $reflectionClass->getMethod('setAttribute');
    $attributeSetter->setAccessible(true);

    $testArray = ['foo' => 'bar', 'items' => [1, 2, 3]];
    $attributeSetter->invoke($setting, 'value', $testArray);

    $attributes = $valueAttribute->getValue($setting);

    expect($attributes['value'])->toBeString()->toBe(json_encode($testArray));
});

test('setting text value works correctly', function () {
    $setting = new Setting(['type' => SettingType::Text]);

    $reflectionClass = new ReflectionClass($setting);
    $valueAttribute = $reflectionClass->getProperty('attributes');
    $valueAttribute->setAccessible(true);

    $attributeSetter = $reflectionClass->getMethod('setAttribute');
    $attributeSetter->setAccessible(true);

    $attributeSetter->invoke($setting, 'value', 'custom text');

    $attributes = $valueAttribute->getValue($setting);

    expect($attributes['value'])->toBeString()->toBe('custom text');
});

test('value accessor correctly handles asset type', function () {
    $media = Media::factory()->create([
        'disk' => 'public',
        'path' => 'images/logo.png',
        'external_url' => null,
    ]);

    $setting = new Setting([
        'type' => SettingType::Asset,
        'value' => (string) $media->id,
    ]);

    expect($setting->value)->toMatchArray([
        'id' => $media->id,
        'type' => $media->type->value,
        'alt' => $media->alt,
        'url' => Storage::disk('public')->url('images/logo.png'),
        'thumbnail_url' => $media->thumbnail_url,
    ]);
});

test('value accessor returns null for asset type when media does not exist', function () {
    $setting = new Setting([
        'type' => SettingType::Asset,
        'value' => '999999',
    ]);

    expect($setting->value)->toBeNull();
});

test('value accessor preserves null for asset type', function () {
    $setting = new Setting([
        'type' => SettingType::Asset,
        'value' => null,
    ]);

    expect($setting->value)->toBeNull();
});

test('value accessor correctly handles encrypted type', function () {
    $setting = new Setting([
        'type' => SettingType::Encrypted,
    ]);

    $reflectionClass = new ReflectionClass($setting);
    $attributes = $reflectionClass->getProperty('attributes');
    $attributes->setAccessible(true);

    $originalValue = 'secret-value';
    $encryptedValue = encrypt($originalValue);
    $attributes->setValue($setting, [
        'type' => SettingType::Encrypted,
        'value' => $encryptedValue,
    ]);

    expect($setting->value)->toBe($originalValue);

    $attributes->setValue($setting, [
        'type' => SettingType::Encrypted,
        'value' => null,
    ]);

    expect($setting->value)->toBeNull();
});

test('setting encrypted value works correctly', function () {
    $setting = new Setting(['type' => SettingType::Encrypted]);

    $reflectionClass = new ReflectionClass($setting);
    $valueAttribute = $reflectionClass->getProperty('attributes');
    $valueAttribute->setAccessible(true);

    $attributeSetter = $reflectionClass->getMethod('setAttribute');
    $attributeSetter->setAccessible(true);

    $secretValue = 'top-secret-data';
    $attributeSetter->invoke($setting, 'value', $secretValue);

    $attributes = $valueAttribute->getValue($setting);

    expect($attributes['value'])->toBeString()->not->toBe($secretValue);
    expect(decrypt($attributes['value']))->toBe($secretValue);

    $attributeSetter->invoke($setting, 'value', null);
    $attributes = $valueAttribute->getValue($setting);

    expect($attributes['value'])->toBeNull();
});

test('getValue returns and caches setting value', function () {
    Setting::factory()->create([
        'key' => 'test_key_unique',
        'value' => 'TestValue',
        'type' => SettingType::Text,
    ]);

    $value = Setting::getValue('test_key_unique');

    expect($value)->toBe('TestValue');
    expect(Cache::has('settings.test_key_unique'))->toBeTrue();
});

test('getValue refreshes cache when setting is updated', function () {
    $setting = Setting::factory()->create([
        'key' => 'test_key_unique',
        'value' => 'InitialValue',
        'type' => SettingType::Text,
    ]);

    // First call should cache the value
    $initialValue = Setting::getValue('test_key_unique');
    expect($initialValue)->toBe('InitialValue');

    // Update the setting - this should trigger the observer to clear the cache
    $setting->update(['value' => 'UpdatedValue']);

    // Next call should get the fresh value
    $updatedValue = Setting::getValue('test_key_unique');
    expect($updatedValue)->toBe('UpdatedValue');
});

test('getValue returns null for non-existent setting', function () {
    $value = Setting::getValue('non_existent');

    expect($value)->toBeNull();
});

test('getByGroup returns settings for a specific group', function () {
    Setting::factory()->create([
        'group' => SettingGroup::General,
        'key' => 'site_name',
        'value' => 'FlexStore',
        'type' => SettingType::Text,
    ]);

    Setting::factory()->create([
        'group' => SettingGroup::General,
        'key' => 'site_description',
        'value' => 'Online Store',
        'type' => SettingType::Text,
    ]);

    Setting::factory()->create([
        'group' => SettingGroup::Policy,
        'key' => 'shipping_policy',
        'value' => 'Shipping Policy',
        'type' => SettingType::Text,
    ]);

    $generalSettings = Setting::getByGroup(SettingGroup::General);

    expect($generalSettings)->toBeInstanceOf(Collection::class);
    expect($generalSettings->has('site_name'))->toBeTrue();
    expect($generalSettings->has('site_description'))->toBeTrue();
    expect($generalSettings->get('site_name'))->toBe('FlexStore');
    expect($generalSettings->get('site_description'))->toBe('Online Store');

    $policySettings = Setting::getByGroup(SettingGroup::Policy);

    expect($policySettings)->toBeInstanceOf(Collection::class);
    expect($policySettings->has('shipping_policy'))->toBeTrue();
    expect($policySettings->get('shipping_policy'))->toBe('Shipping Policy');
});

test('setValue updates existing setting and returns the setting', function () {
    $setting = Setting::factory()->create([
        'key' => 'test_setting',
        'value' => 'original_value',
        'type' => SettingType::Text,
    ]);

    $result = Setting::setValue('test_setting', 'updated_value');

    expect($result)->toBeInstanceOf(Setting::class);
    expect($result->id)->toBe($setting->id);
    expect($result->fresh()->value)->toBe('updated_value');
});

test('setValue returns null for non-existent setting', function () {
    $result = Setting::setValue('non_existent_key', 'some_value');

    expect($result)->toBeNull();
    expect(Setting::where('key', 'non_existent_key')->exists())->toBeFalse();
});

test('setValue works with different data types', function () {
    $textSetting = Setting::factory()->create([
        'key' => 'text_setting',
        'value' => 'old_text',
        'type' => SettingType::Text,
    ]);

    $booleanSetting = Setting::factory()->create([
        'key' => 'boolean_setting',
        'value' => false,
        'type' => SettingType::Boolean,
    ]);

    $integerSetting = Setting::factory()->create([
        'key' => 'integer_setting',
        'value' => '10',
        'type' => SettingType::Integer,
    ]);

    // Test text type
    Setting::setValue('text_setting', 'new_text');
    expect($textSetting->fresh()->value)->toBe('new_text');

    // Test boolean type
    Setting::setValue('boolean_setting', true);
    expect($booleanSetting->fresh()->value)->toBeTrue();

    // Test integer type
    Setting::setValue('integer_setting', 42);
    expect($integerSetting->fresh()->value)->toBe(42);
});

test('setValue clears cache when setting is updated', function () {
    $setting = Setting::factory()->create([
        'key' => 'cached_setting',
        'value' => 'initial_value',
        'type' => SettingType::Text,
    ]);

    // First call to getValue should cache the value
    $initialValue = Setting::getValue('cached_setting');
    expect($initialValue)->toBe('initial_value');
    expect(Cache::has('settings.cached_setting'))->toBeTrue();

    // Update using setValue
    Setting::setValue('cached_setting', 'updated_value');

    // Cache should be cleared and getValue should return the updated value
    $updatedValue = Setting::getValue('cached_setting');
    expect($updatedValue)->toBe('updated_value');
});

test('setValue handles array type correctly', function () {
    $setting = Setting::factory()->create([
        'key' => 'array_setting',
        'value' => json_encode(['old' => 'data']),
        'type' => SettingType::Array,
    ]);

    $newArray = ['new' => 'data', 'items' => [1, 2, 3]];
    Setting::setValue('array_setting', $newArray);

    expect($setting->fresh()->value)->toBe($newArray);
});

test('setValue handles encrypted type correctly', function () {
    $setting = Setting::factory()->create([
        'key' => 'encrypted_setting',
        'value' => 'old_secret',
        'type' => SettingType::Encrypted,
    ]);

    Setting::setValue('encrypted_setting', 'new_secret');

    expect($setting->fresh()->value)->toBe('new_secret');
});
