<?php

declare(strict_types=1);

use App\Address\AddressFieldRules;
use App\Utilities\Translations;
use Illuminate\Validation\Rules\In;

covers(AddressFieldRules::class);

uses()->group('address');

test('resolves a state code to its full name', function (): void {
    expect(AddressFieldRules::stateName('US', 'CA'))->toBe('California')
        ->and(AddressFieldRules::stateName('CA', 'ON'))->toBe('Ontario')
        ->and(AddressFieldRules::stateName('AU', 'NSW'))->toBe('New South Wales');
});

test('returns the state value unchanged when it is not a code', function (): void {
    // India and Bangladesh store the full name as the value already.
    expect(AddressFieldRules::stateName('IN', 'Andhra Pradesh'))->toBe('Andhra Pradesh')
        ->and(AddressFieldRules::stateName('BD', 'Dhaka'))->toBe('Dhaka')
        // Countries without a fixed state list keep the free-text value.
        ->and(AddressFieldRules::stateName('GB', 'Greater London'))->toBe('Greater London')
        // An unknown code is left as-is rather than dropped.
        ->and(AddressFieldRules::stateName('US', 'ZZ'))->toBe('ZZ');
});

test('passes through empty or missing state and country', function (): void {
    expect(AddressFieldRules::stateName('US', null))->toBeNull()
        ->and(AddressFieldRules::stateName('US', ''))->toBe('')
        ->and(AddressFieldRules::stateName(null, 'CA'))->toBe('CA');
});

test('returns sensible defaults for an unknown country', function (): void {
    $format = AddressFieldRules::for('ZZ');

    expect($format['country_code'])->toBe('ZZ')
        ->and($format['city']['required'])->toBeTrue()
        ->and($format['city']['hidden'])->toBeFalse()
        ->and($format['state']['hidden'])->toBeFalse()
        ->and($format['state']['options'])->toBeNull()
        ->and($format['state']['required'])->toBeFalse()
        ->and($format['postal_code']['hidden'])->toBeFalse()
        ->and($format['postal_code']['required'])->toBeTrue();
});

test('merges US overrides with a state dropdown', function (): void {
    $format = AddressFieldRules::for('us');

    expect($format['state']['label'])->toBe('State')
        ->and($format['state']['required'])->toBeTrue()
        ->and($format['state']['options'])->toHaveCount(51)
        ->and($format['postal_code']['label'])->toBe('ZIP code')
        ->and($format['postal_code']['required'])->toBeTrue();
});

test('hides postal_code and offers districts for Bangladesh', function (): void {
    $format = AddressFieldRules::for('BD');

    expect($format['state']['label'])->toBe('District')
        ->and($format['state']['options'])->toHaveCount(64)
        ->and($format['postal_code']['hidden'])->toBeTrue()
        ->and($format['postal_code']['required'])->toBeFalse();
});

test('hides the state for the United Kingdom', function (): void {
    $format = AddressFieldRules::for('GB');

    expect($format['state']['hidden'])->toBeTrue()
        ->and($format['state']['required'])->toBeFalse()
        ->and($format['state']['options'])->toBeNull()
        ->and($format['postal_code']['label'])->toBe('Postal code');
});

test('hides the state for countries that do not use one', function (string $cc): void {
    $format = AddressFieldRules::for($cc);

    expect($format['state']['hidden'])->toBeTrue()
        ->and($format['state']['required'])->toBeFalse()
        ->and($format['state']['options'])->toBeNull();
})->with(['DE', 'FR', 'NL', 'AT', 'SE', 'NO', 'PL', 'DK']);

test('derives a required state dropdown for imported countries', function (): void {
    $mx = AddressFieldRules::for('MX');

    expect($mx['state']['label'])->toBe('State')
        ->and($mx['state']['hidden'])->toBeFalse()
        ->and($mx['state']['required'])->toBeTrue()
        ->and($mx['state']['options'])->toHaveCount(32);

    expect(AddressFieldRules::for('EG')['state']['label'])->toBe('Governorate')
        ->and(AddressFieldRules::attributes('EG'))->toMatchArray(['state' => 'governorate']);
});

test('uses locale-specific state labels', function (): void {
    expect(AddressFieldRules::for('CH')['state']['label'])->toBe('Canton')
        ->and(AddressFieldRules::for('CO')['state']['label'])->toBe('Department')
        ->and(AddressFieldRules::for('RO')['state']['label'])->toBe('County')
        ->and(AddressFieldRules::for('LV')['state']['label'])->toBe('Municipality');
});

test('offers a required state dropdown for Italy', function (): void {
    $format = AddressFieldRules::for('IT');

    expect($format['state']['label'])->toBe('Province')
        ->and($format['state']['hidden'])->toBeFalse()
        ->and($format['state']['required'])->toBeTrue()
        ->and($format['state']['options'])->toHaveCount(107);

    $rules = AddressFieldRules::rules('IT');
    expect($rules['state'])->toContain('required');

    $inRule = collect($rules['state'])->first(fn (mixed $r): bool => $r instanceof In);
    expect($inRule)->not->toBeNull();
});

test('keeps the postal_code visible but optional where the locale allows', function (): void {
    $format = AddressFieldRules::for('CO');

    expect($format['postal_code']['hidden'])->toBeFalse()
        ->and($format['postal_code']['required'])->toBeFalse();

    expect(AddressFieldRules::rules('CO')['postal_code'])->toContain('nullable');
});

test('exposes a state dropdown for every country that has one', function (string $country, int $count, string $type): void {
    $options = AddressFieldRules::stateOptions($country);

    expect($options)->toHaveCount($count);

    foreach ($options as $option) {
        expect($option)->toHaveKeys(['value', 'label']);
        expect($option['value'])->not->toBe('');
        expect($option['label'])->not->toBe('');

        if ($type === 'named') {
            expect($option['value'])->toBe($option['label']);
        } else {
            expect($option['value'])->not->toBe($option['label']);
        }
    }
})->with([
    'AE — emirates' => ['AE', 7, 'named'],
    'AU — states and territories' => ['AU', 8, 'coded'],
    'BD — districts' => ['BD', 64, 'named'],
    'BR — states' => ['BR', 27, 'named'],
    'CA — states and territories' => ['CA', 13, 'coded'],
    'CH — cantons' => ['CH', 26, 'named'],
    'CL — regions' => ['CL', 16, 'named'],
    'CN — states' => ['CN', 33, 'named'],
    'CO — departments' => ['CO', 33, 'named'],
    'ES — states' => ['ES', 50, 'named'],
    'HK — regions' => ['HK', 3, 'named'],
    'HU — counties' => ['HU', 20, 'named'],
    'ID — states' => ['ID', 38, 'named'],
    'IN — states and union territories' => ['IN', 36, 'named'],
    'IT — states' => ['IT', 107, 'named'],
    'JP — prefectures' => ['JP', 47, 'named'],
    'LV — municipalities' => ['LV', 43, 'named'],
    'MY — states' => ['MY', 16, 'named'],
    'NG — states' => ['NG', 37, 'named'],
    'RO — counties' => ['RO', 42, 'named'],
    'SA — regions' => ['SA', 13, 'named'],
    'TR — states' => ['TR', 81, 'named'],
    'US — states' => ['US', 51, 'coded'],
    'ZA — states' => ['ZA', 9, 'named'],
]);

test('maps coded states to code/name pairs', function (): void {
    expect(AddressFieldRules::stateOptions('US'))->toContainEqual(['value' => 'CA', 'label' => 'California'])
        ->and(AddressFieldRules::stateOptions('CA'))->toContainEqual(['value' => 'ON', 'label' => 'Ontario'])
        ->and(AddressFieldRules::stateOptions('AU'))->toContainEqual(['value' => 'NSW', 'label' => 'New South Wales']);
});

test('maps named states to identical value and label', function (): void {
    expect(AddressFieldRules::stateOptions('BD'))->toContainEqual(['value' => 'Dhaka', 'label' => 'Dhaka'])
        ->and(AddressFieldRules::stateOptions('AE'))->toContainEqual(['value' => 'Dubai', 'label' => 'Dubai']);
});

test('has no state dropdown for unknown or hidden-state countries', function (string $cc): void {
    expect(AddressFieldRules::stateOptions($cc))->toBeNull();
})->with(['ZZ', 'GB', 'DE', 'FR', 'NL']);

test('resolves the country code case-insensitively', function (): void {
    expect(AddressFieldRules::stateOptions('us'))->toBe(AddressFieldRules::stateOptions('US'));
});

test('imports states and locale labels for additional countries', function (string $cc, int $count, string $label): void {
    expect(AddressFieldRules::stateOptions($cc))->toHaveCount($count)
        ->and(AddressFieldRules::for($cc)['state']['label'])->toBe($label);
})->with([
    'MX — states' => ['MX', 32, 'State'],
    'AR — states' => ['AR', 24, 'Province'],
    'EG — governorates' => ['EG', 27, 'Governorate'],
    'TH — states' => ['TH', 78, 'Province'],
    'KR — states' => ['KR', 17, 'Province'],
]);

test('every state dropdown has a label and unique, non-empty option values', function (): void {
    /** @var array<string, array{state?: array{label?: string, options?: list<array{value: string, label: string}>}}> $data */
    $data = json_decode((string) file_get_contents(resource_path('data/address-field-rules.json')), true);

    expect($data)->not->toBeEmpty();

    foreach ($data as $entry) {
        $options = $entry['state']['options'] ?? null;

        if ($options === null) {
            continue;
        }

        $values = array_column($options, 'value');

        expect($entry['state']['label'] ?? '')->not->toBe('')
            ->and($values)->not->toBeEmpty()
            ->and($values)->toBe(array_values(array_unique($values)));
    }
});

test('builds prefixed validation rules for Bangladesh', function (): void {
    $rules = AddressFieldRules::rules('BD', 'shipping_address');

    expect($rules)->toHaveKeys([
        'shipping_address.city',
        'shipping_address.state',
        'shipping_address.postal_code',
    ])
        ->and($rules['shipping_address.postal_code'])->toContain('nullable')
        ->and($rules['shipping_address.postal_code'])->not->toContain('required')
        ->and($rules['shipping_address.state'])->toContain('required');

    $inRule = collect($rules['shipping_address.state'])->first(fn (mixed $r): bool => $r instanceof In);
    expect($inRule)->not->toBeNull();
});

test('requires postal_code for the United States', function (): void {
    $rules = AddressFieldRules::rules('US');

    expect($rules['postal_code'])->toContain('required')
        ->and($rules['state'])->toContain('required');

    $inRule = collect($rules['state'])->first(fn (mixed $r): bool => $r instanceof In);
    expect($inRule)->not->toBeNull();
});

test('applies a conditional presence clause without forcing required', function (): void {
    $rules = AddressFieldRules::rules('US', 'billing_address', 'required_if:different_billing_address,true');

    expect($rules['billing_address.state'])->toContain('required_if:different_billing_address,true')
        ->and($rules['billing_address.state'])->toContain('nullable');
});

test('remaps rule keys and enforces the postal code max when keys are overridden', function (): void {
    $rules = AddressFieldRules::rules('US', presence: 'sometimes', keys: [
        'city' => 'store_city',
        'state' => 'store_state',
        'postal_code' => 'store_postal_code',
    ]);

    expect($rules)->toHaveKeys(['store_city', 'store_state', 'store_postal_code'])
        ->and($rules)->not->toHaveKey('postal_code')
        ->and($rules['store_postal_code'])->toContain('sometimes')
        ->and($rules['store_postal_code'])->toContain('required')
        ->and($rules['store_postal_code'])->toContain('max:20');
});

test('remaps attribute keys when keys are overridden', function (): void {
    expect(AddressFieldRules::attributes('US', keys: [
        'state' => 'store_state',
        'postal_code' => 'store_postal_code',
    ]))
        ->toMatchArray([
            'store_state' => 'state',
            'store_postal_code' => 'zip code',
        ])
        ->and(AddressFieldRules::attributes('US', keys: ['state' => 'store_state']))
        ->not->toHaveKey('state');
});

test('produces translatable, country-correct attribute names', function (): void {
    expect(AddressFieldRules::attributes('US'))
        ->toMatchArray([
            'city' => 'city',
            'state' => 'state',
            'postal_code' => 'zip code',
        ])
        ->and(AddressFieldRules::attributes('BD'))
        ->toMatchArray([
            'state' => 'district',
        ]);
});

test('has every shipped label translated in the common bundle for all locales', function (): void {
    $labels = ['City', 'Province', 'State', 'County', 'Department', 'Canton', 'Municipality', 'District', 'Prefecture', 'Emirate', 'Governorate', 'Parish', 'Region', 'Postal code', 'ZIP code', 'Postal code', 'PIN code'];

    foreach (Translations::availableLocales() as $locale) {
        $translations = json_decode((string) file_get_contents(lang_path("common/{$locale}.json")), true);

        foreach ($labels as $label) {
            expect($translations)->toHaveKey($label);
        }
    }
});

test('every field label in the dataset is translated in the common bundle for all locales', function (): void {
    /** @var array<string, array{city?: array{label?: string}, state?: array{label?: string}, postal_code?: array{label?: string}}> $data */
    $data = json_decode((string) file_get_contents(resource_path('data/address-field-rules.json')), true);

    $labels = collect($data)
        ->flatMap(fn (array $entry): array => [
            $entry['city']['label'] ?? null,
            $entry['state']['label'] ?? null,
            $entry['postal_code']['label'] ?? null,
        ])
        ->filter()
        ->unique()
        ->values();

    foreach (Translations::availableLocales() as $locale) {
        $translations = json_decode((string) file_get_contents(lang_path("common/{$locale}.json")), true);

        foreach ($labels as $label) {
            expect($translations)->toHaveKey($label);
        }
    }
});
