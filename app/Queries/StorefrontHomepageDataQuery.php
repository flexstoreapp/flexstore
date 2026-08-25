<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\StorefrontSectionType;
use App\Models\Media;
use App\Models\StorefrontSection;
use Illuminate\Support\Collection;

final readonly class StorefrontHomepageDataQuery
{
    public function __construct(
        private ResolveSectionSettingsQuery $resolveSectionSettingsQuery,
        private ResolveSectionMediaQuery $resolveSectionMediaQuery,
        private SectionProductsQuery $sectionProductsQuery,
    ) {
    }

    /**
     * @return list<array{id: int, type: string, title: array<string, string>, settings: array<string, mixed>}>
     */
    public function execute(): array
    {
        $sections = $this->loadActiveSections();
        $media = $this->resolveSectionMediaQuery->preload($sections);

        /** @var list<array{id: int, type: string, title: array<string, string>, settings: array<string, mixed>}> $result */
        $result = $sections
            ->map(fn (StorefrontSection $section): array => [
                'id' => $section->id,
                'type' => $section->type->value,
                'title' => $section->getTranslations('title'),
                'settings' => $this->resolveSettings($section, $media),
            ])
            ->all();

        return $result;
    }

    /**
     * @return Collection<int, StorefrontSection>
     */
    private function loadActiveSections(): Collection
    {
        return StorefrontSection::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'type', 'title', 'settings', 'sort_order']);
    }

    /**
     * @param  Collection<int, Media>  $media
     * @return array<string, mixed>
     */
    private function resolveSettings(StorefrontSection $section, Collection $media): array
    {
        $settings = $section->settings ?? [];

        return match ($section->type) {
            StorefrontSectionType::FeaturedProducts,
            StorefrontSectionType::ProductCarousel => $this->withProducts($settings),
            StorefrontSectionType::ProductTabs => $this->withGroupedProducts($settings, 'tabs'),
            StorefrontSectionType::ProductColumns => $this->withGroupedProducts($settings, 'columns'),
            StorefrontSectionType::CategoryGrid,
            StorefrontSectionType::BrandStrip => $this->resolveSectionSettingsQuery->execute($section, $media),
            default => $this->resolveSectionMediaQuery->execute($section->type, $settings, $media),
        };
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function withProducts(array $settings): array
    {
        $settings['products'] = $this->sectionProductsQuery->execute($settings);

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function withGroupedProducts(array $settings, string $groupKey): array
    {
        if (! isset($settings[$groupKey]) || ! is_array($settings[$groupKey])) {
            return $settings;
        }

        foreach ($settings[$groupKey] as &$group) {
            if (is_array($group)) {
                $group['products'] = $this->sectionProductsQuery->execute($group);
            }
        }

        unset($group);

        return $settings;
    }
}
