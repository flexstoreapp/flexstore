<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateMenuItemInput;
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
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class UpdateMenuItemRequest extends FormRequest
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
    public function rules(#[RouteParameter('menuItem')] MenuItem $menuItem): array
    {
        return [
            'location' => ['sometimes', 'nullable', 'string', Rule::enum(MenuLocation::class)],
            'label' => ['sometimes', 'nullable', 'string', 'max:100'],
            'link_type' => ['sometimes', 'nullable', 'string', Rule::enum(MenuItemLinkType::class)],
            'brand_id' => [
                'sometimes',
                Rule::requiredIf($this->input('link_type') === MenuItemLinkType::Brand->value),
                'nullable',
                'integer',
                Rule::exists(Brand::class, 'id'),
            ],
            'category_id' => [
                'sometimes',
                Rule::requiredIf($this->input('link_type') === MenuItemLinkType::Category->value),
                'nullable',
                'integer',
                Rule::exists(Category::class, 'id'),
            ],
            'url' => [
                'sometimes',
                Rule::requiredIf($this->input('link_type') === MenuItemLinkType::Custom->value),
                'nullable',
                'string',
                'max:255',
            ],
            'page' => [
                'sometimes',
                Rule::requiredIf($this->input('link_type') === MenuItemLinkType::Page->value),
                'nullable',
                'string',
                Rule::enum(MenuPage::class),
            ],
            'target' => ['sometimes', 'nullable', 'string', Rule::in(['_self', '_blank'])],
            'parent_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists(MenuItem::class, 'id'),
                Rule::notIn([$menuItem->id]),
                new MenuItemMaxDepthRule(),
            ],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_mega_menu' => ['sometimes', 'nullable', 'boolean'],
            'featured_image_id' => ['sometimes', 'nullable', 'integer', new MediaRule(MediaType::Image)],
            'featured_title' => ['sometimes', 'nullable', 'string', 'max:150'],
            'featured_url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
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
            'sort_order' => mb_strtolower(__('Sort order')),
            'is_mega_menu' => mb_strtolower(__('Mega menu')),
            'featured_image_id' => mb_strtolower(__('Featured image')),
            'featured_title' => mb_strtolower(__('Featured title')),
            'featured_url' => mb_strtolower(__('Featured URL')),
            'is_active' => mb_strtolower(__('Active')),
        ];
    }

    public function toDto(): UpdateMenuItemInput
    {
        return UpdateMenuItemInput::fromArray($this->validated());
    }
}
