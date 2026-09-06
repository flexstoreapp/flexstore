<?php

declare(strict_types=1);

use App\Enums\SettingGroup;
use App\Enums\SettingType;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $existing = Setting::query()
            ->whereIn('key', array_column($this->settings(), 'key'))
            ->pluck('key')
            ->all();

        $rows = array_values(array_filter(
            $this->settings(),
            fn (array $setting): bool => ! in_array($setting['key'], $existing, true),
        ));

        if ($rows === []) {
            return;
        }

        Setting::query()->insert(
            array_map(fn (array $setting): array => [
                ...$setting,
                'created_at' => now(),
                'updated_at' => now(),
            ], $rows)
        );
    }

    /**
     * @return list<array{key: string, value: mixed, type: SettingType, group: SettingGroup}>
     */
    private function settings(): array
    {
        return [
            [
                'key' => 'storefront_cart_show_cross_sells',
                'value' => true,
                'type' => SettingType::Boolean,
                'group' => SettingGroup::Storefront,
            ],
            [
                'key' => 'storefront_product_detail_show_up_sells',
                'value' => true,
                'type' => SettingType::Boolean,
                'group' => SettingGroup::Storefront,
            ],
        ];
    }
};
