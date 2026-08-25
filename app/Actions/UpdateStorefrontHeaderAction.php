<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\UpdateSettingsInput;
use App\Enums\SettingGroup;
use App\Models\Setting;
use Illuminate\Support\Collection;

final readonly class UpdateStorefrontHeaderAction
{
    public function __construct(
        private UpdateSettingsAction $updateSettings,
    ) {
    }

    /**
     * @return Collection<int, Setting>
     */
    public function handle(UpdateSettingsInput $input): Collection
    {
        $values = $input->values;

        if (array_key_exists('storefront_header_browse_categories', $values) && is_array($values['storefront_header_browse_categories'])) {
            $values['storefront_header_browse_categories'] = $this->mergeBrowseCategories($values['storefront_header_browse_categories']);
        }

        return $this->updateSettings->handle(UpdateSettingsInput::fromArray($values));
    }

    /**
     * @param  array<int, array<string, mixed>>  $incoming
     * @return array<int, array<string, mixed>>
     */
    private function mergeBrowseCategories(array $incoming): array
    {
        $stored = Setting::getByGroup(SettingGroup::Storefront)->get('storefront_header_browse_categories', []);
        $existing = collect(is_array($stored) ? $stored : [])->keyBy('category_id');
        $locale = app()->getLocale();

        return array_map(function (array $item) use ($existing, $locale): array {
            $previous = $existing->get($item['category_id']);
            $titles = is_array($previous['featured_title'] ?? null) ? $previous['featured_title'] : [];

            $title = $item['featured_title'] ?? null;

            if ($title === null || $title === '') {
                unset($titles[$locale]);
            } else {
                $titles[$locale] = $title;
            }

            return [
                'category_id' => $item['category_id'],
                'is_mega_menu' => (bool) ($item['is_mega_menu'] ?? false),
                'featured_image_id' => $item['featured_image_id'] ?? null,
                'featured_title' => $titles,
                'featured_url' => $item['featured_url'] ?? null,
            ];
        }, $incoming);
    }
}
