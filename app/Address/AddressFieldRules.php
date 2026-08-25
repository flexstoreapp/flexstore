<?php

declare(strict_types=1);

namespace App\Address;

use Illuminate\Validation\Rule;

final class AddressFieldRules
{
    private const int CITY_MAX = 255;

    private const int STATE_MAX = 255;

    private const int POSTAL_CODE_MAX = 20;

    /**
     * @var array<string, array{city?: array{label?: string}, state?: array{label?: string, hidden?: bool, required?: bool, options?: list<array{value: string, label: string}>}, postal_code?: array{label?: string, hidden?: bool, required?: bool}}>|null
     */
    private static ?array $data = null;

    private static ?string $version = null;

    /**
     * @return array{country_code: string, city: array{label: string, required: bool, hidden: bool, options: null}, state: array{label: string, required: bool, hidden: bool, options: list<array{value: string, label: string}>|null}, postal_code: array{label: string, required: bool, hidden: bool, options: null}}
     */
    public static function for(string $countryCode): array
    {
        $cc = mb_strtoupper($countryCode);
        $entry = self::data()[$cc] ?? [];

        $state = $entry['state'] ?? [];
        $postal_code = $entry['postal_code'] ?? [];
        $city = $entry['city'] ?? [];

        $stateHidden = $state['hidden'] ?? false;
        $stateOptions = $stateHidden ? null : ($state['options'] ?? null);
        $postalCodeHidden = $postal_code['hidden'] ?? false;

        return [
            'country_code' => $cc,
            'city' => [
                'label' => $city['label'] ?? 'City',
                'required' => true,
                'hidden' => false,
                'options' => null,
            ],
            'state' => [
                'label' => $state['label'] ?? 'State',
                'required' => $stateHidden ? false : ($state['required'] ?? ($stateOptions !== null)),
                'hidden' => $stateHidden,
                'options' => $stateOptions,
            ],
            'postal_code' => [
                'label' => $postal_code['label'] ?? 'Postal code',
                'required' => $postalCodeHidden ? false : ($postal_code['required'] ?? true),
                'hidden' => $postalCodeHidden,
                'options' => null,
            ],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>|null
     */
    public static function stateOptions(string $countryCode): ?array
    {
        return self::for($countryCode)['state']['options'];
    }

    public static function stateName(?string $countryCode, ?string $state): ?string
    {
        if ($countryCode === null || $state === null || $state === '') {
            return $state;
        }

        $options = self::stateOptions($countryCode);

        if ($options === null) {
            return $state;
        }

        foreach ($options as $option) {
            if ($option['value'] === $state) {
                return $option['label'];
            }
        }

        return $state;
    }

    public static function version(): string
    {
        return self::$version ??= (string) filemtime(resource_path('data/address-field-rules.json'));
    }

    /**
     * @param  string  $presence  'required' | 'sometimes' | a conditional clause
     *                            (e.g., 'required_if:different_billing_address,true', 'required_with:shipping_address')
     * @param  list<'city'|'state'|'postal_code'>  $only  which slots to emit rules for
     * @param  array<string, string>  $keys  per-field output key overrides (e.g., ['state' => 'store_state'])
     * @return array<string, list<mixed>>
     */
    public static function rules(
        string $countryCode,
        string $prefix = '',
        string $presence = 'required',
        array $only = ['city', 'state', 'postal_code'],
        array $keys = [],
    ): array {
        $format = self::for($countryCode);
        $max = ['city' => self::CITY_MAX, 'state' => self::STATE_MAX, 'postal_code' => self::POSTAL_CODE_MAX];

        $rules = [];

        foreach ($only as $field) {
            $spec = $format[$field];
            $key = $keys[$field] ?? ($prefix === '' ? $field : "{$prefix}.{$field}");
            $required = ! $spec['hidden'] && $spec['required'];

            $set = self::presenceRules($presence, $required);
            $set[] = 'string';
            $set[] = 'max:' . $max[$field];

            if ($field === 'state' && $spec['options'] !== null) {
                $set[] = Rule::in(array_column($spec['options'], 'value'));
            }

            $rules[$key] = $set;
        }

        return $rules;
    }

    /**
     * @param  list<'city'|'state'|'postal_code'>  $only
     * @param  array<string, string>  $keys  per-field output key overrides (e.g., ['state' => 'store_state'])
     * @return array<string, string>
     */
    public static function attributes(
        string $countryCode,
        string $prefix = '',
        array $only = ['city', 'state', 'postal_code'],
        array $keys = [],
    ): array {
        $format = self::for($countryCode);

        $attributes = [];

        foreach ($only as $field) {
            $key = $keys[$field] ?? ($prefix === '' ? $field : "{$prefix}.{$field}");
            $attributes[$key] = mb_strtolower(__($format[$field]['label']));
        }

        return $attributes;
    }

    /**
     * @return list<string>
     */
    private static function presenceRules(string $presence, bool $required): array
    {
        if ($presence === 'sometimes') {
            return ['sometimes', $required ? 'required' : 'nullable'];
        }

        if ($presence === 'required') {
            return [$required ? 'required' : 'nullable'];
        }

        return $required ? [$presence, 'nullable'] : ['nullable'];
    }

    /**
     * @return array<string, array{city?: array{label?: string}, state?: array{label?: string, hidden?: bool, required?: bool, options?: list<array{value: string, label: string}>}, postal_code?: array{label?: string, hidden?: bool, required?: bool}}>
     */
    private static function data(): array
    {
        if (self::$data === null) {
            /** @var array<string, array{city?: array{label?: string}, state?: array{label?: string, hidden?: bool, required?: bool, options?: list<array{value: string, label: string}>}, postal_code?: array{label?: string, hidden?: bool, required?: bool}}> $data */
            $data = json_decode((string) file_get_contents(resource_path('data/address-field-rules.json')), true, flags: JSON_THROW_ON_ERROR);
            self::$data = $data;
        }

        return self::$data;
    }
}
