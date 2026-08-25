<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\MediaType;
use App\Models\Media;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class MediaRule implements ValidationRule
{
    public function __construct(private MediaType $type)
    {
    }

    /**
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = Media::query()
            ->whereKey($value)
            ->where('type', $this->type->value)
            ->exists();

        if (! $exists) {
            $fail(__('The :attribute is not a valid media selection.'));
        }
    }
}
