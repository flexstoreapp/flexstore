<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\StoreCategoryInput;
use App\Models\Category;
use App\Rules\SlugRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Override;

final class StoreCategoryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url_handle' => ['nullable', 'string', 'max:255', new SlugRule(), Rule::unique(Category::class, 'url_handle')],
            'description' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', Rule::exists(Category::class, 'id')],
            'is_active' => ['required', 'boolean'],
            'add_more' => ['sometimes', 'boolean'],
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
            'add_more' => mb_strtolower(__('Add more')),
        ];
    }

    #[Override]
    public function prepareForValidation(): void
    {
        $defaults = [];

        if ($this->filled('name') && ! $this->filled('url_handle')) {
            $defaults['url_handle'] = Str::slug($this->input('name'));
        }

        if ($defaults !== []) {
            $this->merge($defaults);
        }
    }

    public function toDto(): StoreCategoryInput
    {
        return StoreCategoryInput::fromArray($this->validated());
    }
}
