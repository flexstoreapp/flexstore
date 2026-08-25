<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Rules\NoCircularReference;
use App\Rules\NotSelfParent;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class ReorderCategoryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(#[RouteParameter('category')] Category $category): array
    {
        return [
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists(Category::class, 'id'),
                new NotSelfParent($category),
                new NoCircularReference($category),
            ],
            'position' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'parent_id' => mb_strtolower(__('Parent')),
            'position' => mb_strtolower(__('Position')),
        ];
    }
}
