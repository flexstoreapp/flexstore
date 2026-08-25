<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\SettingGroup;
use App\Models\Announcement;
use App\Models\Setting;
use App\Utilities\LocalizedText;
use Illuminate\Support\Collection;

final readonly class StorefrontLayoutDataQuery
{
    private const array SOCIAL_PLATFORMS = ['facebook', 'instagram', 'x', 'youtube'];

    private const array PAYMENT_PRESETS = [
        'all' => ['visa', 'mastercard', 'amex', 'discover', 'jcb', 'unionpay', 'mada', 'paypal', 'apple_pay', 'google_pay', 'upi', 'ideal'],
        'credit_cards' => ['visa', 'mastercard', 'amex', 'discover', 'jcb', 'unionpay', 'mada'],
        'digital_wallets' => ['paypal', 'apple_pay', 'google_pay', 'upi', 'ideal'],
    ];

    public function __construct(
        private StorefrontBrowseCategoriesQuery $browseCategoriesQuery,
        private StorefrontHeaderMenuQuery $headerMenuQuery,
        private StorefrontFooterMenuQuery $footerMenuQuery,
    ) {
    }

    /**
     * @return array{
     *     customCss: string,
     *     customJs: string,
     *     announcements: array<int, array{id: int, content: array<string, string>, url: string|null}>,
     *     trendingSearches: array<int, string>,
     *     header: array{sticky: bool},
     *     headerMenu: array<int, array<string, mixed>>,
     *     browseCategories: array<int, array<string, mixed>>,
     *     footer: array<string, mixed>,
     *     store_email: string|null,
     *     store_phone: string|null,
     * }
     */
    public function execute(): array
    {
        $storefrontSettings = Setting::getByGroup(SettingGroup::Storefront);

        return [
            'store_email' => Setting::getValue('store_email') ?: null,
            'store_phone' => Setting::getValue('store_phone') ?: null,
            'customCss' => (string) $storefrontSettings->get('storefront_custom_css', ''),
            'customJs' => (string) $storefrontSettings->get('storefront_custom_js', ''),
            'announcements' => $this->announcements(),
            'trendingSearches' => [],
            'header' => [
                'sticky' => (bool) $storefrontSettings->get('storefront_header_sticky', false),
            ],
            'headerMenu' => $this->headerMenuQuery->execute(),
            'browseCategories' => $this->browseCategoriesQuery->execute(),
            'footer' => $this->footer($storefrontSettings),
        ];
    }

    /**
     * @param  Collection<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function footer(Collection $settings): array
    {
        $store = Setting::getByGroup(SettingGroup::Store);

        return [
            'menu' => $this->footerMenuQuery->execute(),
            'description' => LocalizedText::resolve($store->get('store_description')),
            'showSocialLinks' => (bool) $settings->get('storefront_footer_show_social_links', true),
            'showPaymentBadges' => (bool) $settings->get('storefront_footer_show_payment_badges', true),
            'paymentMethods' => $this->paymentMethods($settings),
            'showCopyright' => (bool) $settings->get('storefront_footer_show_copyright', true),
            'copyrightText' => $settings->get('storefront_footer_copyright_text') ?: null,
            'showPoweredBy' => true,
            'socialLinks' => $this->socialLinks($store),
        ];
    }

    /**
     * @param  Collection<string, mixed>  $settings
     * @return array<int, string>
     */
    private function paymentMethods(Collection $settings): array
    {
        $preset = (string) $settings->get('storefront_footer_payment_method_preset', 'all');

        if ($preset === 'custom') {
            $methods = $settings->get('storefront_footer_payment_methods', []);

            return is_array($methods) ? array_values($methods) : [];
        }

        return self::PAYMENT_PRESETS[$preset] ?? self::PAYMENT_PRESETS['all'];
    }

    /**
     * @param  Collection<string, mixed>  $store
     * @return array<string, string>
     */
    private function socialLinks(Collection $store): array
    {
        $links = [];

        foreach (self::SOCIAL_PLATFORMS as $platform) {
            $url = $store->get("store_social_{$platform}");

            if (is_string($url) && $url !== '') {
                $links[$platform] = $url;
            }
        }

        return $links;
    }

    /**
     * @return array<int, array{id: int, content: array<string, string>, url: string|null}>
     */
    private function announcements(): array
    {
        return Announcement::query()
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Announcement $announcement): array => [
                'id' => $announcement->id,
                'content' => $announcement->getTranslations('content'),
                'url' => $announcement->url,
            ])
            ->all();
    }
}
