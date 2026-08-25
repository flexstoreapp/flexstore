<?php

declare(strict_types=1);

use App\Enums\Currency;
use App\Enums\CurrencySymbolPosition;
use App\Enums\DisplayTaxTotals;
use App\Enums\ListLoadingMethod;
use App\Enums\ProductSortOption;
use App\Enums\SettingGroup;
use App\Enums\SettingType;
use App\Enums\TaxBasedOn;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type');
            $table->string('group')->index();
            $table->timestamps();
        });

        Setting::query()->insert(
            array_map(fn (array $setting): array => [
                ...$setting,
                'created_at' => now(),
                'updated_at' => now(),
            ], $this->getDefaultSettings())
        );
    }

    /**
     * @return list<array{key: string, value: mixed, type: SettingType, group: SettingGroup}>
     */
    private function getDefaultSettings(): array
    {
        return [
            // general
            [
                'key' => 'default_low_stock_threshold',
                'value' => 10,
                'type' => SettingType::Integer,
                'group' => SettingGroup::General,
            ],
            [
                'key' => 'auto_approve_reviews',
                'value' => false,
                'type' => SettingType::Boolean,
                'group' => SettingGroup::General,
            ],

            // store
            [
                'key' => 'store_name',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Store,
            ],
            [
                'key' => 'store_description',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Store,
            ],
            [
                'key' => 'store_email',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Store,
            ],
            [
                'key' => 'store_phone',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Store,
            ],
            [
                'key' => 'store_street_address',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Store,
            ],
            [
                'key' => 'store_city',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Store,
            ],
            [
                'key' => 'store_state',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Store,
            ],
            [
                'key' => 'store_postal_code',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Store,
            ],
            [
                'key' => 'store_country_code',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Store,
            ],
            [
                'key' => 'selling_countries',
                'value' => json_encode([]),
                'type' => SettingType::Array,
                'group' => SettingGroup::Store,
            ],
            [
                'key' => 'store_logo',
                'value' => null,
                'type' => SettingType::Asset,
                'group' => SettingGroup::Store,
            ],
            [
                'key' => 'store_logo_dark',
                'value' => null,
                'type' => SettingType::Asset,
                'group' => SettingGroup::Store,
            ],
            [
                'key' => 'store_favicon',
                'value' => null,
                'type' => SettingType::Asset,
                'group' => SettingGroup::Store,
            ],
            [
                'key' => 'store_social_facebook',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Store,
            ],
            [
                'key' => 'store_social_instagram',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Store,
            ],
            [
                'key' => 'store_social_x',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Store,
            ],
            [
                'key' => 'store_social_tiktok',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Store,
            ],
            [
                'key' => 'store_social_pinterest',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Store,
            ],
            [
                'key' => 'store_social_youtube',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Store,
            ],

            // checkout
            [
                'key' => 'guest_checkout_enabled',
                'value' => true,
                'type' => SettingType::Boolean,
                'group' => SettingGroup::Checkout,
            ],
            [
                'key' => 'checkout_reservation_minutes',
                'value' => 10,
                'type' => SettingType::Integer,
                'group' => SettingGroup::Checkout,
            ],

            // locale
            [
                'key' => 'default_locale',
                'value' => 'en',
                'type' => SettingType::Text,
                'group' => SettingGroup::Locale,
            ],
            [
                'key' => 'available_locales',
                'value' => json_encode(['en']),
                'type' => SettingType::Array,
                'group' => SettingGroup::Locale,
            ],

            // currency
            [
                'key' => 'base_currency',
                'value' => Currency::USD->name,
                'type' => SettingType::Text,
                'group' => SettingGroup::Currency,
            ],
            [
                'key' => 'currency_symbol_position',
                'value' => CurrencySymbolPosition::Before->value,
                'type' => SettingType::Text,
                'group' => SettingGroup::Currency,
            ],
            [
                'key' => 'thousands_separator',
                'value' => ',',
                'type' => SettingType::Text,
                'group' => SettingGroup::Currency,
            ],
            [
                'key' => 'decimal_separator',
                'value' => '.',
                'type' => SettingType::Text,
                'group' => SettingGroup::Currency,
            ],

            // tax
            [
                'key' => 'default_tax_rate',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Tax,
            ],
            [
                'key' => 'tax_based_on',
                'value' => TaxBasedOn::Shipping->value,
                'type' => SettingType::Text,
                'group' => SettingGroup::Tax,
            ],
            [
                'key' => 'prices_include_tax',
                'value' => false,
                'type' => SettingType::Boolean,
                'group' => SettingGroup::Tax,
            ],
            [
                'key' => 'shipping_is_taxable',
                'value' => false,
                'type' => SettingType::Boolean,
                'group' => SettingGroup::Tax,
            ],
            [
                'key' => 'display_tax_totals',
                'value' => DisplayTaxTotals::Itemized->value,
                'type' => SettingType::Text,
                'group' => SettingGroup::Tax,
            ],

            // mail
            [
                'key' => 'mail_host',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Mail,
            ],
            [
                'key' => 'mail_port',
                'value' => null,
                'type' => SettingType::Integer,
                'group' => SettingGroup::Mail,
            ],
            [
                'key' => 'mail_encryption',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Mail,
            ],
            [
                'key' => 'mail_username',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Mail,
            ],
            [
                'key' => 'mail_password',
                'value' => null,
                'type' => SettingType::Encrypted,
                'group' => SettingGroup::Mail,
            ],
            [
                'key' => 'mail_from_address',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Mail,
            ],
            [
                'key' => 'mail_from_name',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Mail,
            ],

            // notification - admin
            [
                'key' => 'notification_admin_new_order',
                'value' => true,
                'type' => SettingType::Boolean,
                'group' => SettingGroup::Notification,
            ],
            [
                'key' => 'notification_admin_order_canceled',
                'value' => true,
                'type' => SettingType::Boolean,
                'group' => SettingGroup::Notification,
            ],
            [
                'key' => 'notification_admin_low_stock',
                'value' => true,
                'type' => SettingType::Boolean,
                'group' => SettingGroup::Notification,
            ],
            [
                'key' => 'notification_admin_new_customer',
                'value' => false,
                'type' => SettingType::Boolean,
                'group' => SettingGroup::Notification,
            ],
            [
                'key' => 'notification_admin_new_review',
                'value' => true,
                'type' => SettingType::Boolean,
                'group' => SettingGroup::Notification,
            ],

            // notification - customer
            [
                'key' => 'notification_customer_order_confirmed',
                'value' => true,
                'type' => SettingType::Boolean,
                'group' => SettingGroup::Notification,
            ],

            // policy
            [
                'key' => 'refund_policy',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Policy,
            ],
            [
                'key' => 'privacy_policy',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Policy,
            ],
            [
                'key' => 'terms_of_service',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Policy,
            ],

            // seo
            [
                'key' => 'seo_homepage_meta_title',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Seo,
            ],
            [
                'key' => 'seo_homepage_meta_description',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Seo,
            ],
            [
                'key' => 'seo_shop_meta_title',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Seo,
            ],
            [
                'key' => 'seo_shop_meta_description',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Seo,
            ],
            [
                'key' => 'seo_robots_indexing',
                'value' => true,
                'type' => SettingType::Boolean,
                'group' => SettingGroup::Seo,
            ],

            // integration
            [
                'key' => 'integration_google_analytics_id',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Integration,
            ],
            [
                'key' => 'integration_google_tag_manager_id',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Integration,
            ],
            [
                'key' => 'integration_meta_pixel_id',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Integration,
            ],
            [
                'key' => 'integration_tiktok_pixel_id',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Integration,
            ],
            [
                'key' => 'integration_pinterest_tag_id',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Integration,
            ],

            // system
            [
                'key' => 'maintenance_mode',
                'value' => false,
                'type' => SettingType::Boolean,
                'group' => SettingGroup::System,
            ],
            [
                'key' => 'maintenance_allowed_ips',
                'value' => json_encode([]),
                'type' => SettingType::Array,
                'group' => SettingGroup::System,
            ],
            [
                'key' => 'flexstore_version',
                'value' => '1.0.0',
                'type' => SettingType::Text,
                'group' => SettingGroup::System,
            ],

            // storefront - theme & appearance
            [
                'key' => 'storefront_theme_color',
                'value' => 'neutral',
                'type' => SettingType::Text,
                'group' => SettingGroup::Storefront,
            ],
            [
                'key' => 'storefront_appearance',
                'value' => 'light',
                'type' => SettingType::Text,
                'group' => SettingGroup::Storefront,
            ],
            [
                'key' => 'storefront_custom_css',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Storefront,
            ],
            [
                'key' => 'storefront_custom_js',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Storefront,
            ],

            // storefront - header
            [
                'key' => 'storefront_header_browse_categories',
                'value' => json_encode([]),
                'type' => SettingType::Array,
                'group' => SettingGroup::Storefront,
            ],
            [
                'key' => 'storefront_header_sticky',
                'value' => true,
                'type' => SettingType::Boolean,
                'group' => SettingGroup::Storefront,
            ],

            // storefront - footer
            [
                'key' => 'storefront_footer_show_copyright',
                'value' => true,
                'type' => SettingType::Boolean,
                'group' => SettingGroup::Storefront,
            ],
            [
                'key' => 'storefront_footer_show_social_links',
                'value' => true,
                'type' => SettingType::Boolean,
                'group' => SettingGroup::Storefront,
            ],
            [
                'key' => 'storefront_footer_show_payment_badges',
                'value' => true,
                'type' => SettingType::Boolean,
                'group' => SettingGroup::Storefront,
            ],
            [
                'key' => 'storefront_footer_payment_method_preset',
                'value' => 'all',
                'type' => SettingType::Text,
                'group' => SettingGroup::Storefront,
            ],
            [
                'key' => 'storefront_footer_payment_methods',
                'value' => json_encode(['visa', 'mastercard', 'amex', 'paypal', 'apple_pay', 'google_pay']),
                'type' => SettingType::Array,
                'group' => SettingGroup::Storefront,
            ],
            [
                'key' => 'storefront_footer_copyright_text',
                'value' => null,
                'type' => SettingType::Text,
                'group' => SettingGroup::Storefront,
            ],

            // storefront - product list
            [
                'key' => 'storefront_product_list_loading_method',
                'value' => ListLoadingMethod::Pagination,
                'type' => SettingType::Text,
                'group' => SettingGroup::Storefront,
            ],
            [
                'key' => 'storefront_product_list_default_per_page',
                'value' => 24,
                'type' => SettingType::Integer,
                'group' => SettingGroup::Storefront,
            ],
            [
                'key' => 'storefront_product_list_default_sort',
                'value' => ProductSortOption::Latest,
                'type' => SettingType::Text,
                'group' => SettingGroup::Storefront,
            ],

            // storefront - product detail
            [
                'key' => 'storefront_product_detail_show_info_strip',
                'value' => true,
                'type' => SettingType::Boolean,
                'group' => SettingGroup::Storefront,
            ],
            [
                'key' => 'storefront_product_detail_info_strip',
                'value' => json_encode([]),
                'type' => SettingType::Array,
                'group' => SettingGroup::Storefront,
            ],
            [
                'key' => 'storefront_product_detail_show_related_products',
                'value' => true,
                'type' => SettingType::Boolean,
                'group' => SettingGroup::Storefront,
            ],
            [
                'key' => 'storefront_product_detail_related_products_count',
                'value' => 10,
                'type' => SettingType::Integer,
                'group' => SettingGroup::Storefront,
            ],
            [
                'key' => 'storefront_product_detail_show_reviews',
                'value' => true,
                'type' => SettingType::Boolean,
                'group' => SettingGroup::Storefront,
            ],
            [
                'key' => 'storefront_product_detail_reviews_per_page',
                'value' => 10,
                'type' => SettingType::Integer,
                'group' => SettingGroup::Storefront,
            ],
        ];
    }
};
