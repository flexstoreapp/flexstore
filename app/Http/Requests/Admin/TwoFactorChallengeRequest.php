<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\TwoFactorChallengeInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Override;

final class TwoFactorChallengeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'digits:6'],
            'recovery_code' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'code' => mb_strtolower(__('Authentication code')),
            'recovery_code' => mb_strtolower(__('Recovery code')),
        ];
    }

    public function toDto(): TwoFactorChallengeInput
    {
        return TwoFactorChallengeInput::fromArray($this->validated());
    }

    /**
     * @throws ValidationException
     */
    #[Override]
    protected function passedValidation(): void
    {
        $code = $this->input('code');
        $recoveryCode = $this->input('recovery_code');

        if (empty($code) && empty($recoveryCode)) {
            throw ValidationException::withMessages([
                'code' => [__('Please provide either an authentication code or a recovery code.')],
            ]);
        }

        if (! empty($code) && ! empty($recoveryCode)) {
            throw ValidationException::withMessages([
                'code' => [__('Please provide either an authentication code or a recovery code, not both.')],
            ]);
        }
    }
}
