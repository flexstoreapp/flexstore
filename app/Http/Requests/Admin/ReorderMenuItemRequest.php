<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\MenuItem;
use App\Rules\MenuItemMaxDepthRule;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class ReorderMenuItemRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(#[RouteParameter('menuItem')] MenuItem $menuItem): array
    {
        return [
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists(MenuItem::class, 'id'),
                Rule::notIn([$menuItem->id]),
                new MenuItemMaxDepthRule(),
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
