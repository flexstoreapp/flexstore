<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Enums\Country;
use App\Enums\DisplayTaxTotals;
use App\Enums\Locale;
use App\Enums\StorefrontAccent;
use App\Models\Setting;
use Illuminate\View\View;

final readonly class EmailLayoutComposer
{
    public function compose(View $view): void
    {
        $view->with([
            'storeName' => Setting::getValue('store_name', config('app.name')),
            'storeLogo' => Setting::getValue('store_logo'),
            'storeLogoDark' => Setting::getValue('store_logo_dark'),
            'themeColor' => StorefrontAccent::hexFor(Setting::getValue('storefront_theme_color')),
            'themeColorDark' => StorefrontAccent::darkHexFor(Setting::getValue('storefront_theme_color')),
            'storeEmail' => Setting::getValue('store_email'),
            'storePhone' => Setting::getValue('store_phone'),
            'storeAddressLines' => $this->addressLines(),
            'socialLinks' => $this->socialLinks(),
            'storefrontUrl' => route('home'),
            'locale' => app()->getLocale(),
            'isRtl' => Locale::isRtl(app()->getLocale()),
            'displayTaxTotals' => DisplayTaxTotals::from(Setting::getValue('display_tax_totals', 'single')),
        ]);
    }

    /**
     * @return list<string>
     */
    private function addressLines(): array
    {
        $street = Setting::getValue('store_street_address');
        $city = Setting::getValue('store_city');
        $state = Setting::getValue('store_state');
        $postal_code = Setting::getValue('store_postal_code');
        $countryCode = Setting::getValue('store_country_code');

        $cityLine = mb_trim($city . (($city && $state) ? ', ' : '') . $state . (($postal_code) ? ' ' . $postal_code : ''));

        $countryName = null;
        if ($countryCode) {
            $countryName = Country::fromNameOrValue((string) $countryCode)->value ?? (string) $countryCode;
        }

        return array_values(array_filter([
            $street ? (string) $street : null,
            $cityLine !== '' ? $cityLine : null,
            $countryName,
        ]));
    }

    /**
     * @return array<string, string>
     */
    private function socialLinks(): array
    {
        $links = [
            'Facebook' => Setting::getValue('store_social_facebook'),
            'Instagram' => Setting::getValue('store_social_instagram'),
            'X' => Setting::getValue('store_social_x'),
            'TikTok' => Setting::getValue('store_social_tiktok'),
            'Pinterest' => Setting::getValue('store_social_pinterest'),
            'YouTube' => Setting::getValue('store_social_youtube'),
        ];

        return array_filter(array_map(static fn ($v): ?string => $v ? (string) $v : null, $links));
    }
}
