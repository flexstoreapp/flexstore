<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\StorefrontSectionType;
use App\Models\Media;
use App\Models\StorefrontSection;
use Illuminate\Support\Collection;

final readonly class ResolveSectionMediaQuery
{
    /**
     * @param  Collection<int, StorefrontSection>  $sections
     * @return Collection<int, Media>
     */
    public function preload(Collection $sections): Collection
    {
        $ids = $sections
            ->flatMap(fn (StorefrontSection $section): array => $section->type->extractMediaIds($section->settings ?? []))
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return collect();
        }

        return Media::query()->whereKey($ids)->get(Media::displayColumns())->keyBy('id');
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  Collection<int, Media>  $media  media keyed by id, preloaded via preload()
     * @return array<string, mixed>
     */
    public function execute(StorefrontSectionType $type, array $settings, Collection $media): array
    {
        $map = $type->mediaFields();

        if ($map['fields'] === [] && $map['lists'] === []) {
            return $settings;
        }

        $ids = $type->extractMediaIds($settings);

        if ($ids === []) {
            return $settings;
        }

        foreach ($map['fields'] as $field) {
            if (array_key_exists($field, $settings)) {
                $settings[$field] = $this->resolve($settings[$field], $media);
            }
        }

        foreach ($map['lists'] as $listKey => $imageKeys) {
            if (! isset($settings[$listKey])) {
                continue;
            }
            if (! is_array($settings[$listKey])) {
                continue;
            }
            foreach ($settings[$listKey] as &$item) {
                if (! is_array($item)) {
                    continue;
                }

                foreach ($imageKeys as $imageKey) {
                    if (array_key_exists($imageKey, $item)) {
                        $item[$imageKey] = $this->resolve($item[$imageKey], $media);
                    }
                }
            }

            unset($item);
        }

        return $settings;
    }

    /**
     * @param  Collection<int, Media>  $media
     * @return array<string, mixed>|null
     */
    private function resolve(mixed $value, Collection $media): ?array
    {
        if (! is_numeric($value)) {
            return null;
        }

        return $media->get((int) $value)?->toArray();
    }
}
