<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\CancelOrderInput;
use App\Enums\CancellationReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Override;

final class CancelOrderRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', new Enum(CancellationReason::class)],
            'reason_note' => ['nullable', 'string', 'max:500'],
            'refund' => ['sometimes', 'boolean'],
            'restock' => ['sometimes', 'boolean'],
            'notify_customer' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'reason' => mb_strtolower(__('Reason')),
            'reason_note' => mb_strtolower(__('Reason note')),
            'refund' => mb_strtolower(__('Refund')),
            'restock' => mb_strtolower(__('Restock')),
            'notify_customer' => mb_strtolower(__('Notify customer')),
        ];
    }

    public function toDto(): CancelOrderInput
    {
        return CancelOrderInput::fromArray($this->validated());
    }
}
