<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Announcement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class ReorderAnnouncementsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['required', 'integer', Rule::exists(Announcement::class, 'id')],
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
            'ordered_ids.*' => mb_strtolower(__('Announcement')),
        ];
    }
}
