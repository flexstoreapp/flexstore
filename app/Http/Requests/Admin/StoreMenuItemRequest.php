<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\StoreMenuItemInput;
use App\Enums\MediaType;
use App\Enums\MenuItemLinkType;
use App\Enums\MenuLocation;
use App\Enums\MenuPage;
use App\Enums\Permission;
use App\Models\Brand;
use App\Models\Category;
use App\Models\MenuItem;
use App\Rules\MediaRule;
use App\Rules\MenuItemMaxDepthRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return match ($this->input('link_type')) {
            MenuItemLinkType::Brand->value => $this->user()?->can(Permission::BrandsView->value) ?? false,
            MenuItemLinkType::Category->value => $this->user()?->can(Permission::CategoriesView->value) ?? false,
            default => true,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'location' => ['required', 'string', Rule::enum(MenuLocation::class)],
            'label' => ['required', 'string', 'max:100'],
            'link_type' => ['required', 'string', Rule::enum(MenuItemLinkType::class)],
            'brand_id' => [
                Rule::requiredIf($this->input('link_type') === MenuItemLinkType::Brand->value),
                'nullable',
                'integer',
                Rule::exists(Brand::class, 'id'),
            ],
            'category_id' => [
                Rule::requiredIf($this->input('link_type') === MenuItemLinkType::Category->value),
                'nullable',
                'integer',
                Rule::exists(Category::class, 'id'),
            ],
            'url' => [
                Rule::requiredIf($this->input('link_type') === MenuItemLinkType::Custom->value),
                'nullable',
                'string',
                'max:255',
            ],
            'page' => [
                Rule::requiredIf($this->input('link_type') === MenuItemLinkType::Page->value),
                'nullable',
                'string',
                Rule::enum(MenuPage::class),
            ],
            'target' => ['nullable', 'string', Rule::in(['_self', '_blank'])],
            'parent_id' => ['nullable', 'integer', Rule::exists(MenuItem::class, 'id'), new MenuItemMaxDepthRule()],
            'is_mega_menu' => ['nullable', 'boolean'],
            'featured_image_id' => ['nullable', 'integer', new MediaRule(MediaType::Image)],
            'featured_title' => ['nullable', 'string', 'max:150'],
            'featured_url' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'location' => mb_strtolower(__('Location')),
            'label' => mb_strtolower(__('Label')),
            'link_type' => mb_strtolower(__('Link type')),
            'brand_id' => mb_strtolower(__('Brand')),
            'category_id' => mb_strtolower(__('Category')),
            'url' => mb_strtolower(__('URL')),
            'page' => mb_strtolower(__('Page')),
            'target' => mb_strtolower(__('Target')),
            'parent_id' => mb_strtolower(__('Parent')),
            'is_mega_menu' => mb_strtolower(__('Mega menu')),
            'featured_image_id' => mb_strtolower(__('Featured image')),
            'featured_title' => mb_strtolower(__('Featured title')),
            'featured_url' => mb_strtolower(__('Featured URL')),
            'is_active' => mb_strtolower(__('Active')),
        ];
    }

    public function toDto(): StoreMenuItemInput
    {
        return StoreMenuItemInput::fromArray($this->validated());
    }
}
