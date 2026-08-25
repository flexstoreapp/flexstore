<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateSettingsInput;
use Illuminate\Foundation\Http\FormRequest;
use Override;

final class UpdateNotificationSettingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notification_admin_new_order' => ['sometimes', 'required', 'boolean'],
            'notification_admin_order_canceled' => ['sometimes', 'required', 'boolean'],
            'notification_admin_low_stock' => ['sometimes', 'required', 'boolean'],
            'notification_admin_new_customer' => ['sometimes', 'required', 'boolean'],
            'notification_admin_new_review' => ['sometimes', 'required', 'boolean'],
            'notification_customer_order_confirmed' => ['sometimes', 'required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'notification_admin_new_order' => mb_strtolower(__('New order notification')),
            'notification_admin_order_canceled' => mb_strtolower(__('Order canceled notification')),
            'notification_admin_low_stock' => mb_strtolower(__('Low stock notification')),
            'notification_admin_new_customer' => mb_strtolower(__('New customer notification')),
            'notification_admin_new_review' => mb_strtolower(__('New review notification')),
            'notification_customer_order_confirmed' => mb_strtolower(__('Order confirmed notification')),
        ];
    }

    public function toDto(): UpdateSettingsInput
    {
        return UpdateSettingsInput::fromArray($this->validated());
    }
}
