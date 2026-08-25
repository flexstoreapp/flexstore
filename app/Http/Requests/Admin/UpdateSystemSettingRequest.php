<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateSettingsInput;
use App\Rules\ValidIpList;
use Illuminate\Foundation\Http\FormRequest;
use Override;

final class UpdateSystemSettingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'maintenance_mode' => ['sometimes', 'required', 'boolean'],
            'maintenance_allowed_ips' => ['sometimes', 'nullable', new ValidIpList],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'maintenance_mode' => mb_strtolower(__('Maintenance mode')),
            'maintenance_allowed_ips' => mb_strtolower(__('Maintenance allowed IPs')),
        ];
    }

    #[Override]
    public function validated(mixed $key = null, mixed $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        if ($key !== null) {
            return $validated;
        }

        if (array_key_exists('maintenance_allowed_ips', $validated)) {
            $raw = $validated['maintenance_allowed_ips'] ?? '';
            $validated['maintenance_allowed_ips'] = array_values(
                array_filter(array_map(trim(...), explode("\n", (string) $raw)))
            );
        }

        return $validated;
    }

    public function toDto(): UpdateSettingsInput
    {
        return UpdateSettingsInput::fromArray($this->validated());
    }
}
