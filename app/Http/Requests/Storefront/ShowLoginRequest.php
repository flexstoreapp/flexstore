<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use App\Rules\LocalRedirectPath;
use Illuminate\Foundation\Http\FormRequest;
use Override;

final class ShowLoginRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'redirect_to' => ['sometimes', 'nullable', 'string', 'max:2048', new LocalRedirectPath()],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'redirect_to' => mb_strtolower(__('Redirect url')),
        ];
    }
}
