<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasMedia;
use App\Enums\StorefrontSectionType;
use Database\Factories\StorefrontSectionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Override;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * @property-read int $id
 * @property-read StorefrontSectionType $type
 * @property-read string $title
 * @property-read array<string, mixed>|null $settings
 * @property-read int $sort_order
 * @property-read bool $is_active
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 */
#[Translatable('title')]
#[UseFactory(StorefrontSectionFactory::class)]
final class StorefrontSection extends Model
{
    /** @use HasFactory<\Database\Factories\StorefrontSectionFactory> */
    use HasFactory;

    use HasMedia;
    use HasTranslations {
        setAttribute as protected setTranslatableAttribute;
    }

    /**
     * @return list<string>
     */
    public static function translatableSettingsPaths(StorefrontSectionType $type): array
    {
        return match ($type) {
            StorefrontSectionType::HeroSlider => ['slides.*.headline', 'slides.*.subtext', 'slides.*.button_text', 'side_tiles.*.title', 'side_tiles.*.subtitle'],
            StorefrontSectionType::InfoStrip => ['items.*.title', 'items.*.subtitle'],
            StorefrontSectionType::CategoryGrid => ['view_all_text'],
            StorefrontSectionType::FeaturedProducts => ['view_all_text'],
            StorefrontSectionType::ProductTabs => ['tabs.*.label'],
            StorefrontSectionType::PromoBanners => ['banners.*.title', 'banners.*.subtitle'],
            StorefrontSectionType::ProductCarousel => ['view_all_text'],
            StorefrontSectionType::ProductColumns => ['columns.*.heading'],
            StorefrontSectionType::Testimonials => ['testimonials.*.quote', 'testimonials.*.author_name'],
            default => [],
        };
    }

    /**
     * @return MorphToMany<Media, $this>
     */
    public function media(): MorphToMany
    {
        return $this->morphMedia();
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function casts(): array
    {
        return [
            'type' => StorefrontSectionType::class,
            'settings' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    #[Override]
    public function setAttribute(mixed $key, mixed $value): mixed
    {
        if ($key === 'settings' && is_array($value)) {
            $typeValue = $this->attributes['type'] ?? $this->getRawOriginal('type');

            if ($typeValue !== null) {
                $type = $typeValue instanceof StorefrontSectionType
                    ? $typeValue
                    : StorefrontSectionType::tryFrom($typeValue);

                if ($type !== null) {
                    $paths = self::translatableSettingsPaths($type);

                    if ($paths !== []) {
                        $rawOriginal = $this->getRawOriginal('settings');
                        $existing = is_string($rawOriginal) ? (json_decode($rawOriginal, true) ?? []) : [];
                        $value = $this->wrapTranslatableSettings($value, $paths, app()->getLocale(), $existing);
                    }
                }
            }
        }

        return $this->setTranslatableAttribute($key, $value);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  list<string>  $paths
     * @param  array<string, mixed>  $existing
     * @return array<string, mixed>
     */
    private function wrapTranslatableSettings(array $settings, array $paths, string $locale, array $existing): array
    {
        foreach ($paths as $path) {
            if (str_contains($path, '.*.')) {
                [$arrayKey, $fieldKey] = explode('.*.', $path, 2);
                if (! isset($settings[$arrayKey])) {
                    continue;
                }
                if (! is_array($settings[$arrayKey])) {
                    continue;
                }

                foreach ($settings[$arrayKey] as $index => $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    if (! array_key_exists($fieldKey, $item)) {
                        continue;
                    }
                    $newValue = $item[$fieldKey];

                    if (is_string($newValue) || is_null($newValue)) {
                        $existingTranslations = $existing[$arrayKey][$index][$fieldKey] ?? [];
                        $merged = is_array($existingTranslations) ? $existingTranslations : [];
                        $merged[$locale] = $newValue;
                        $settings[$arrayKey][$index][$fieldKey] = $merged;
                    }
                }
            } else {
                if (! array_key_exists($path, $settings)) {
                    continue;
                }

                $newValue = $settings[$path];

                if (is_string($newValue) || is_null($newValue)) {
                    $existingTranslations = $existing[$path] ?? [];
                    $merged = is_array($existingTranslations) ? $existingTranslations : [];
                    $merged[$locale] = $newValue;
                    $settings[$path] = $merged;
                }
            }
        }

        return $settings;
    }
}
