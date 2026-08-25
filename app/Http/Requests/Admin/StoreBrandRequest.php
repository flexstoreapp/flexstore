<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\StoreBrandInput;
use App\Enums\MediaType;
use App\Models\Brand;
use App\Rules\MediaRule;
use App\Rules\SlugRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Override;

final class StoreBrandRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url_handle' => ['nullable', 'string', 'max:255', new SlugRule(), Rule::unique(Brand::class, 'url_handle')],
            'description' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'image_id' => ['nullable', 'integer', new MediaRule(MediaType::Image)],
            'is_active' => ['required', 'boolean'],
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
            'image_id' => mb_strtolower(__('Image')),
            'is_active' => mb_strtolower(__('Active')),
        ];
    }

    #[Override]
    public function prepareForValidation(): void
    {
        if ($this->filled('name') && ! $this->filled('url_handle')) {
            $this->merge([
                'url_handle' => Str::slug($this->input('name')),
            ]);
        }
    }

    public function toDto(): StoreBrandInput
    {
        return StoreBrandInput::fromArray($this->validated());
    }
}
