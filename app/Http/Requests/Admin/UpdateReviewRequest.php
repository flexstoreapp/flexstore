<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateReviewInput;
use Illuminate\Foundation\Http\FormRequest;
use Override;

final class UpdateReviewRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rating' => ['sometimes', 'required', 'integer', 'min:1', 'max:5'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'rating' => mb_strtolower(__('Rating')),
            'title' => mb_strtolower(__('Title')),
            'content' => mb_strtolower(__('Review')),
        ];
    }

    public function toDto(): UpdateReviewInput
    {
        return UpdateReviewInput::fromArray($this->validated());
    }
}
