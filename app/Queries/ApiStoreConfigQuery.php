<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\DisplayTaxTotals;
use App\Enums\SettingGroup;
use App\Enums\StorePolicy;
use App\Models\Currency;
use App\Models\Setting;

final readonly class ApiStoreConfigQuery
{
    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        $store = Setting::getByGroup(SettingGroup::Store);
        $locale = Setting::getByGroup(SettingGroup::Locale);
        $logo = $store->get('store_logo');

        return [
            'store' => [
                'name' => $store->get('store_name'),
                'email' => $store->get('store_email'),
                'phone' => $store->get('store_phone'),
                'country_code' => $store->get('store_country_code'),
                'logo_url' => is_array($logo) ? ($logo['url'] ?? null) : null,
            ],
            'locales' => [
                'default' => $locale->get('default_locale', config('app.locale')),
                'available' => array_values((array) $locale->get('available_locales', [config('app.locale')])),
            ],
            'currencies' => [
                'base' => Setting::getValue('base_currency', 'USD'),
                'available' => Currency::query()
                    ->where('is_active', true)
                    ->get(['code', 'symbol', 'symbol_position', 'decimal_places', 'thousands_separator', 'decimal_separator', 'exchange_rate'])
                    ->map(fn (Currency $currency): array => [
                        'code' => $currency->code,
                        'symbol' => $currency->symbol,
                        'symbol_position' => $currency->symbol_position->value,
                        'decimal_places' => $currency->decimal_places,
                        'thousands_separator' => $currency->thousands_separator,
                        'decimal_separator' => $currency->decimal_separator,
                        'exchange_rate' => $currency->exchange_rate,
                    ])
                    ->all(),
            ],
            'checkout' => [
                'guest_checkout_enabled' => (bool) Setting::getValue('guest_checkout_enabled', true),
                'prices_include_tax' => (bool) Setting::getValue('prices_include_tax', false),
                'display_tax_totals' => (DisplayTaxTotals::tryFrom((string) Setting::getValue('display_tax_totals'))
                    ?? DisplayTaxTotals::Single)->value,
            ],
            'policies' => array_values(array_map(
                fn (StorePolicy $policy): string => $policy->value,
                array_filter(
                    StorePolicy::cases(),
                    fn (StorePolicy $policy): bool => filled(Setting::getValue($policy->settingKey())),
                ),
            )),
        ];
    }
}
