<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateCategoryInput;
use App\Models\Category;
use App\Rules\NoCircularReference;
use App\Rules\NotSelfParent;
use App\Rules\SlugRule;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class UpdateCategoryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(#[RouteParameter('category')] Category $category): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'url_handle' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                new SlugRule(),
                Rule::unique(Category::class, 'url_handle')->ignore($category),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'seo_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seo_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'parent_id' => [
                'sometimes',
                'nullable',
                Rule::exists(Category::class, 'id'),
                new NotSelfParent($category),
                new NoCircularReference($category),
            ],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => mb_strtolower(__('Name')),
            'url_handle' => mb_strtolower(__('URL handle')),
            'description' => mb_strtolower(__('Description')),
            'seo_title' => mb_strtolower(__('SEO title')),
            'seo_description' => mb_strtolower(__('SEO description')),
            'parent_id' => mb_strtolower(__('Parent category')),
            'is_active' => mb_strtolower(__('Active')),
        ];
    }

    #[Override]
    public function prepareForValidation(): void
    {
        if ($this->input('google_product_category') === '') {
            $this->merge(['google_product_category' => null]);
        }
    }

    public function toDto(): UpdateCategoryInput
    {
        return UpdateCategoryInput::fromArray($this->validated());
    }
}
