<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\StoreAnnouncementInput;
use Illuminate\Foundation\Http\FormRequest;
use Override;

final class StoreAnnouncementRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'content' => mb_strtolower(__('Content')),
            'url' => mb_strtolower(__('URL')),
            'is_active' => mb_strtolower(__('Active')),
            'starts_at' => mb_strtolower(__('Start date & time')),
            'ends_at' => mb_strtolower(__('End date & time')),
        ];
    }

    public function toDto(): StoreAnnouncementInput
    {
        return StoreAnnouncementInput::fromArray($this->validated());
    }
}
