<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateSettingsInput;
use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class UpdateStorefrontProductDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'storefront_product_detail_show_info_strip' => [
                'sometimes',
                'boolean',
            ],
            'storefront_product_detail_info_strip' => [
                'sometimes',
                'array',
                'max:3',
            ],
            'storefront_product_detail_info_strip.*.icon_name' => [
                'required_with:storefront_product_detail_info_strip',
                'string',
                'max:50',
            ],
            'storefront_product_detail_info_strip.*.title' => [
                'required_with:storefront_product_detail_info_strip',
                'string',
                'max:100',
            ],
            'storefront_product_detail_info_strip.*.subtitle' => [
                'required_with:storefront_product_detail_info_strip',
                'string',
                'max:255',
            ],
            'storefront_product_detail_show_related_products' => [
                'sometimes',
                'boolean',
            ],
            'storefront_product_detail_related_products_count' => [
                'sometimes',
                'integer',
                Rule::in([5, 10, 15, 20]),
            ],
            'storefront_product_detail_show_reviews' => [
                'sometimes',
                'boolean',
            ],
            'storefront_product_detail_reviews_per_page' => [
                'sometimes',
                'integer',
                Rule::in([5, 10, 15, 20]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'storefront_product_detail_show_info_strip' => mb_strtolower(__('Show info strip')),
            'storefront_product_detail_info_strip' => mb_strtolower(__('Info strip')),
            'storefront_product_detail_info_strip.*.icon_name' => mb_strtolower(__('Icon name')),
            'storefront_product_detail_info_strip.*.title' => mb_strtolower(__('Title')),
            'storefront_product_detail_info_strip.*.subtitle' => mb_strtolower(__('Subtitle')),
            'storefront_product_detail_show_related_products' => mb_strtolower(__('Show related products')),
            'storefront_product_detail_related_products_count' => mb_strtolower(__('Number of products')),
            'storefront_product_detail_show_reviews' => mb_strtolower(__('Show reviews')),
            'storefront_product_detail_reviews_per_page' => mb_strtolower(__('Reviews per page')),
        ];
    }

    /**
     * @param  array-key|null  $key
     * @return ($key is null ? array<string, mixed> : mixed)
     */
    #[Override]
    public function validated(mixed $key = null, mixed $default = null): mixed
    {
        /** @var array<string, mixed> $validated */
        $validated = parent::validated();

        if (isset($validated['storefront_product_detail_info_strip']) && is_array($validated['storefront_product_detail_info_strip'])) {
            $validated['storefront_product_detail_info_strip'] = $this->wrapInfoStripAsTranslatable(
                $validated['storefront_product_detail_info_strip'],
            );
        }

        if ($key === null) {
            return $validated;
        }

        return data_get($validated, $key, $default);
    }

    public function toDto(): UpdateSettingsInput
    {
        return UpdateSettingsInput::fromArray($this->validated());
    }

    /**
     * @param  array<int, array{icon_name: string, title: string, subtitle: string}>  $items
     * @return list<array{icon_name: string, title: array<string, string>, subtitle: array<string, string>}>
     */
    private function wrapInfoStripAsTranslatable(array $items): array
    {
        $locale = app()->getLocale();
        $existing = Setting::getValue('storefront_product_detail_info_strip', []);
        $existing = is_array($existing) ? $existing : [];

        return array_map(function (array $item, int $index) use ($locale, $existing): array {
            $existingTitle = $existing[$index]['title'] ?? [];
            $existingSubtitle = $existing[$index]['subtitle'] ?? [];

            return [
                'icon_name' => $item['icon_name'],
                'title' => [
                    ...(is_array($existingTitle) ? $existingTitle : []),
                    $locale => $item['title'],
                ],
                'subtitle' => [
                    ...(is_array($existingSubtitle) ? $existingSubtitle : []),
                    $locale => $item['subtitle'],
                ],
            ];
        }, $items, array_keys($items));
    }
}
