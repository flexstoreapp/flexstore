<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Override;

final class VerifyEmailRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'hash' => ['required', 'string'],
            'expires' => ['required', 'integer'],
            'signature' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'id' => mb_strtolower(__('Account')),
            'hash' => mb_strtolower(__('Verification hash')),
            'expires' => mb_strtolower(__('Expiry')),
            'signature' => mb_strtolower(__('Signature')),
        ];
    }
}
