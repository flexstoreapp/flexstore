<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\StorefrontSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class ReorderStorefrontSectionsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['required', 'integer', Rule::exists(StorefrontSection::class, 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'ordered_ids' => mb_strtolower(__('Order')),
            'ordered_ids.*' => mb_strtolower(__('Section')),
        ];
    }
}
