<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\StoreStorefrontSectionInput;
use App\Enums\MediaType;
use App\Enums\ProductSource;
use App\Enums\StorefrontSectionType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Rules\MediaRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class StoreStorefrontSectionRequest extends FormRequest
{
    private const array TEXT_ALIGNMENTS = [
        'top-left', 'top-center', 'top-right',
        'left', 'center', 'right',
        'bottom-left', 'bottom-center', 'bottom-right',
    ];

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'type' => mb_strtolower(__('Type')),
            'title' => mb_strtolower(__('Title')),
            'is_active' => mb_strtolower(__('Active')),
            'settings' => mb_strtolower(__('Settings')),
            'settings.product_source' => mb_strtolower(__('Product source')),
            'settings.product_limit' => mb_strtolower(__('Product limit')),
            'settings.category_id' => mb_strtolower(__('Category')),
            'settings.brand_id' => mb_strtolower(__('Brand')),
            'settings.product_ids' => mb_strtolower(__('Products')),
            'settings.product_ids.*' => mb_strtolower(__('Product')),
            'settings.view_all_url' => mb_strtolower(__('URL')),
            'settings.view_all_text' => mb_strtolower(__('Link text')),
            'settings.subtitle' => mb_strtolower(__('Subtitle')),
            'settings.button_text' => mb_strtolower(__('Button text')),
            'settings.slides' => mb_strtolower(__('Slides')),
            'settings.slides.*.image' => mb_strtolower(__('Image')),
            'settings.slides.*.headline' => mb_strtolower(__('Headline')),
            'settings.slides.*.subtext' => mb_strtolower(__('Subtext')),
            'settings.slides.*.button_text' => mb_strtolower(__('Button text')),
            'settings.slides.*.button_url' => mb_strtolower(__('Button URL')),
            'settings.slides.*.text_color' => mb_strtolower(__('Text color')),
            'settings.slides.*.text_align' => mb_strtolower(__('Text alignment')),
            'settings.side_tiles' => mb_strtolower(__('Side tiles')),
            'settings.side_tiles.*.image' => mb_strtolower(__('Image')),
            'settings.side_tiles.*.title' => mb_strtolower(__('Title')),
            'settings.side_tiles.*.subtitle' => mb_strtolower(__('Subtitle')),
            'settings.side_tiles.*.url' => mb_strtolower(__('URL')),
            'settings.side_tiles.*.text_color' => mb_strtolower(__('Text color')),
            'settings.side_tiles.*.text_align' => mb_strtolower(__('Text alignment')),
            'settings.autoplay' => mb_strtolower(__('Autoplay')),
            'settings.autoplay_speed' => mb_strtolower(__('Autoplay speed')),
            'settings.transition' => mb_strtolower(__('Transition')),
            'settings.show_dots' => mb_strtolower(__('Show dots')),
            'settings.items' => mb_strtolower(__('Items')),
            'settings.items.*.icon_name' => mb_strtolower(__('Icon')),
            'settings.items.*.title' => mb_strtolower(__('Title')),
            'settings.items.*.subtitle' => mb_strtolower(__('Subtitle')),
            'settings.categories' => mb_strtolower(__('Categories')),
            'settings.categories.*.category_id' => mb_strtolower(__('Category')),
            'settings.categories.*.image' => mb_strtolower(__('Image')),
            'settings.categories.*.text_color' => mb_strtolower(__('Text color')),
            'settings.end_date' => mb_strtolower(__('End date')),
            'settings.show_countdown' => mb_strtolower(__('Show countdown')),
            'settings.tabs' => mb_strtolower(__('Tabs')),
            'settings.tabs.*.label' => mb_strtolower(__('Label')),
            'settings.tabs.*.product_source' => mb_strtolower(__('Product source')),
            'settings.tabs.*.category_id' => mb_strtolower(__('Category')),
            'settings.tabs.*.product_ids' => mb_strtolower(__('Products')),
            'settings.tabs.*.product_ids.*' => mb_strtolower(__('Product')),
            'settings.tabs.*.product_limit' => mb_strtolower(__('Product limit')),
            'settings.banners' => mb_strtolower(__('Banners')),
            'settings.banners.*.image' => mb_strtolower(__('Image')),
            'settings.banners.*.title' => mb_strtolower(__('Title')),
            'settings.banners.*.subtitle' => mb_strtolower(__('Subtitle')),
            'settings.banners.*.url' => mb_strtolower(__('URL')),
            'settings.banners.*.text_align' => mb_strtolower(__('Text alignment')),
            'settings.banners.*.text_color' => mb_strtolower(__('Text color')),
            'settings.columns' => mb_strtolower(__('Columns')),
            'settings.columns.*.heading' => mb_strtolower(__('Heading')),
            'settings.columns.*.product_source' => mb_strtolower(__('Product source')),
            'settings.columns.*.category_id' => mb_strtolower(__('Category')),
            'settings.columns.*.product_ids' => mb_strtolower(__('Products')),
            'settings.columns.*.product_ids.*' => mb_strtolower(__('Product')),
            'settings.columns.*.product_limit' => mb_strtolower(__('Product limit')),
            'settings.brand_ids' => mb_strtolower(__('Brands')),
            'settings.brand_ids.*' => mb_strtolower(__('Brand')),
            'settings.grayscale' => mb_strtolower(__('Grayscale')),
            'settings.testimonials' => mb_strtolower(__('Testimonials')),
            'settings.testimonials.*.quote' => mb_strtolower(__('Quote')),
            'settings.testimonials.*.author_name' => mb_strtolower(__('Author name')),
            'settings.testimonials.*.rating' => mb_strtolower(__('Rating')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::enum(StorefrontSectionType::class)],
            'title' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'settings' => ['nullable', 'array'],

            'settings.product_source' => ['nullable', 'string', Rule::enum(ProductSource::class)],
            'settings.product_limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'settings.category_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')],
            'settings.brand_id' => ['nullable', 'integer', Rule::exists(Brand::class, 'id')],
            'settings.product_ids' => ['nullable', 'array', 'max:50'],
            'settings.product_ids.*' => ['required', 'integer', Rule::exists(Product::class, 'id')],
            'settings.view_all_url' => ['nullable', 'string', 'max:255'],
            'settings.view_all_text' => ['nullable', 'string', 'max:100'],
            'settings.subtitle' => ['nullable', 'string', 'max:255'],
            'settings.button_text' => ['nullable', 'string', 'max:100'],

            'settings.slides' => ['nullable', 'array', 'max:5'],
            'settings.slides.*.image' => ['nullable', 'integer', new MediaRule(MediaType::Image)],
            'settings.slides.*.headline' => ['nullable', 'string', 'max:255'],
            'settings.slides.*.subtext' => ['nullable', 'string', 'max:500'],
            'settings.slides.*.button_text' => ['nullable', 'string', 'max:100'],
            'settings.slides.*.button_url' => ['nullable', 'string', 'max:255'],
            'settings.slides.*.text_color' => ['nullable', 'string', Rule::in(['dark', 'light'])],
            'settings.slides.*.text_align' => ['nullable', 'string', Rule::in(self::TEXT_ALIGNMENTS)],
            'settings.side_tiles' => ['nullable', 'array', 'max:2'],
            'settings.side_tiles.*.image' => ['nullable', 'integer', new MediaRule(MediaType::Image)],
            'settings.side_tiles.*.title' => ['nullable', 'string', 'max:255'],
            'settings.side_tiles.*.subtitle' => ['nullable', 'string', 'max:255'],
            'settings.side_tiles.*.url' => ['nullable', 'string', 'max:255'],
            'settings.side_tiles.*.text_color' => ['nullable', 'string', Rule::in(['dark', 'light'])],
            'settings.side_tiles.*.text_align' => ['nullable', 'string', Rule::in(self::TEXT_ALIGNMENTS)],
            'settings.autoplay' => ['nullable', 'boolean'],
            'settings.autoplay_speed' => ['nullable', 'integer', 'min:1000', 'max:15000'],
            'settings.transition' => ['nullable', 'string', Rule::in(['fade', 'slide'])],
            'settings.show_dots' => ['nullable', 'boolean'],

            'settings.items' => ['nullable', 'array', 'max:4'],
            'settings.items.*.icon_name' => ['required', 'string', 'max:100'],
            'settings.items.*.title' => ['required', 'string', 'max:100'],
            'settings.items.*.subtitle' => ['nullable', 'string', 'max:255'],

            'settings.categories' => ['nullable', 'array', 'max:12'],
            'settings.categories.*.category_id' => ['required', 'integer', Rule::exists(Category::class, 'id')],
            'settings.categories.*.image' => ['nullable', 'integer', new MediaRule(MediaType::Image)],
            'settings.categories.*.text_color' => ['nullable', 'string', Rule::in(['dark', 'light'])],

            'settings.end_date' => ['nullable', 'date'],
            'settings.show_countdown' => ['nullable', 'boolean'],

            'settings.tabs' => ['nullable', 'array', 'max:5'],
            'settings.tabs.*.label' => ['required', 'string', 'max:100'],
            'settings.tabs.*.product_source' => ['nullable', 'string', Rule::enum(ProductSource::class)],
            'settings.tabs.*.category_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')],
            'settings.tabs.*.product_ids' => ['nullable', 'array', 'max:50'],
            'settings.tabs.*.product_ids.*' => ['required', 'integer', Rule::exists(Product::class, 'id')],
            'settings.tabs.*.product_limit' => ['nullable', 'integer', 'min:1', 'max:50'],

            'settings.banners' => ['nullable', 'array', 'max:2'],
            'settings.banners.*.image' => ['nullable', 'integer', new MediaRule(MediaType::Image)],
            'settings.banners.*.title' => ['nullable', 'string', 'max:255'],
            'settings.banners.*.subtitle' => ['nullable', 'string', 'max:255'],
            'settings.banners.*.url' => ['nullable', 'string', 'max:255'],
            'settings.banners.*.text_align' => ['nullable', 'string', Rule::in(self::TEXT_ALIGNMENTS)],
            'settings.banners.*.text_color' => ['nullable', 'string', Rule::in(['dark', 'light'])],

            'settings.columns' => ['nullable', 'array', 'max:3'],
            'settings.columns.*.heading' => ['required', 'string', 'max:100'],
            'settings.columns.*.product_source' => ['nullable', 'string', Rule::enum(ProductSource::class)],
            'settings.columns.*.category_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')],
            'settings.columns.*.product_ids' => ['nullable', 'array', 'max:50'],
            'settings.columns.*.product_ids.*' => ['required', 'integer', Rule::exists(Product::class, 'id')],
            'settings.columns.*.product_limit' => ['nullable', 'integer', 'min:1', 'max:20'],

            'settings.brand_ids' => ['nullable', 'array', 'max:20'],
            'settings.brand_ids.*' => ['required', 'integer', Rule::exists(Brand::class, 'id')],
            'settings.grayscale' => ['nullable', 'boolean'],

            'settings.testimonials' => ['nullable', 'array', 'max:6'],
            'settings.testimonials.*.quote' => ['required', 'string', 'max:500'],
            'settings.testimonials.*.author_name' => ['required', 'string', 'max:100'],
            'settings.testimonials.*.rating' => ['required', 'integer', 'min:1', 'max:5'],

        ];
    }

    public function toDto(): StoreStorefrontSectionInput
    {
        return StoreStorefrontSectionInput::fromArray($this->validated());
    }
}
