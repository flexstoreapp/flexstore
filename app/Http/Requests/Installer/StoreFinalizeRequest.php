<?php

declare(strict_types=1);

namespace App\Http\Requests\Installer;

use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class StoreFinalizeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'string', Rule::in(DateTimeZone::listIdentifiers())],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'store_name' => mb_strtolower(__('Store name')),
            'timezone' => mb_strtolower(__('Timezone')),
        ];
    }
}
