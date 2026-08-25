<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SettingGroup;
use App\Enums\SettingType;
use App\Observers\SettingObserver;
use Brick\Math\BigDecimal;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Override;

/**
 * @property-read string $key
 * @property-read mixed $value
 * @property-read SettingType $type
 * @property-read SettingGroup|null $group
 * @property-read bool $is_required
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 */
#[ObservedBy(SettingObserver::class)]
#[UseFactory(SettingFactory::class)]
final class Setting extends Model
{
    /** @use HasFactory<\Database\Factories\SettingFactory> */
    use HasFactory;

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $getValue = (fn (string $key, mixed $default = null): mixed => Cache::remember(
            key: "settings.{$key}",
            ttl: now()->addDay(),
            callback: fn () => self::query()
                ->where('key', $key)
                ->first()
                ->value ?? $default,
        ));

        return Cache::memo('array')->remember(
            key: "memo:settings.{$key}",
            ttl: now()->addMinute(),
            callback: fn (): mixed => $getValue($key, $default),
        );
    }

    /**
     * @return Collection<string, mixed>
     */
    public static function getByGroup(SettingGroup $group): Collection
    {
        $getByGroup = (fn (SettingGroup $group): array => Cache::remember(
            key: "settings.group.{$group->value}",
            ttl: now()->addDay(),
            callback: fn () => self::query()
                ->select(['key', 'value', 'type'])
                ->where('group', $group)
                ->get()
                ->pluck('value', 'key')
                ->all(),
        ));

        return Cache::memo('array')->remember(
            key: "memo:settings.group.{$group->value}",
            ttl: now()->addMinute(),
            callback: fn (): Collection => collect($getByGroup($group)),
        );
    }

    public static function setValue(string $key, mixed $value): ?self
    {
        $setting = self::query()->where('key', $key)->first();

        if ($setting) {
            $setting->update(['value' => $value]);
        }

        return $setting;
    }

    #[Override]
    public function casts(): array
    {
        return [
            'type' => SettingType::class,
            'group' => SettingGroup::class,
        ];
    }

    /**
     * @return Attribute<mixed, mixed>
     */
    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): mixed => match ($this->type ?? SettingType::Text) {
                SettingType::Boolean => (bool) $value,
                SettingType::Integer => is_null($value) ? null : (int) $value,
                SettingType::Decimal => is_null($value) ? null : (string) BigDecimal::of((string) $value),
                SettingType::Array => json_decode((string) $value, associative: true),
                SettingType::Asset => match (true) {
                    is_null($value) || $value === '' => null,
                    default => Media::query()->select(Media::displayColumns())->find((int) $value)?->toArray(),
                },
                SettingType::Encrypted => is_null($value) ? null : decrypt($value),
                default => $value,
            },
            set: fn (mixed $value): mixed => match ($this->type ?? SettingType::Text) {
                SettingType::Boolean => (bool) $value,
                SettingType::Integer => is_null($value) ? null : (int) $value,
                SettingType::Decimal => is_null($value) ? null : (string) BigDecimal::of((string) $value),
                SettingType::Array => json_encode($value),
                SettingType::Encrypted => is_null($value) ? null : encrypt($value),
                default => $value,
            },
        );
    }
}
