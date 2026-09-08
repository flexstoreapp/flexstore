<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\SettingGroup;
use App\Models\Currency;
use App\Models\Setting;

final readonly class AdminStoreConfigQuery
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
                    ->orderBy('code')
                    ->get()
                    ->map(fn (Currency $currency): array => [
                        'code' => $currency->code,
                        'symbol' => $currency->symbol,
                        'symbol_position' => $currency->symbol_position->value,
                        'decimal_places' => $currency->decimal_places,
                        'thousands_separator' => $currency->thousands_separator,
                        'decimal_separator' => $currency->decimal_separator,
                        'exchange_rate' => $currency->exchange_rate,
                        'is_active' => $currency->is_active,
                    ])
                    ->all(),
            ],
        ];
    }
}
