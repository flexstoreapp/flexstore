<?php

declare(strict_types=1);

namespace App\Utilities;

final readonly class InputConverter
{
    public function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(mb_strtolower($value), ['true', '1', 'yes', 'on'], true);
        }

        return (bool) $value;
    }

    public function toNullableBoolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->toBoolean($value);
    }

    public function toNullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return mb_trim((string) $value) ?: null;
    }

    public function toNullableNumeric(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $string = (string) $value;

        return is_numeric($string) ? $string : null;
    }

    /**
     * @return list<int>
     */
    public function toIdList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $raw = is_array($value) ? $value : explode(',', (string) $value);

        return array_values(array_filter(array_map(intval(...), $raw)));
    }
}
