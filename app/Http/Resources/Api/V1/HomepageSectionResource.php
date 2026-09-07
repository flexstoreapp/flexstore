<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Media;
use App\Utilities\LocalizedText;
use App\Utilities\StorefrontLinkTarget;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * Wraps one section from StorefrontHomepageDataQuery. Section settings are a
 * free-form tree, so translated, media and link values are converted by key
 * rather than by guessing from shape.
 */
final class HomepageSectionResource extends JsonResource
{
    /** @var list<string> */
    private const array TRANSLATED_KEYS = [
        'title', 'subtitle', 'headline', 'subtext', 'button_text', 'view_all_text',
        'label', 'heading', 'quote', 'author_name', 'name', 'excerpt', 'description',
    ];

    /** @var array<string, string> Link key to the key its resolved target is published under. */
    private const array LINK_KEYS = [
        'url' => 'target',
        'view_all_url' => 'view_all_target',
        'button_url' => 'button_target',
    ];

    /** @var list<string> */
    private const array MEDIA_KEYS = ['image', 'featured_media'];

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var array{id: int, type: string, title: mixed, settings: array<string, mixed>} $section */
        $section = $this->resource;

        return [
            'id' => $section['id'],
            'type' => $section['type'],
            'title' => LocalizedText::resolve($section['title']),
            'settings' => $this->resolveSettings($section['settings']),
        ];
    }

    /**
     * @param  array<array-key, mixed>  $settings
     * @return array<array-key, mixed>
     */
    private function resolveSettings(array $settings): array
    {
        $result = [];

        foreach ($settings as $key => $value) {
            if (in_array($key, self::MEDIA_KEYS, true)) {
                $result[$key] = $this->media($value);

                continue;
            }

            if (in_array($key, self::TRANSLATED_KEYS, true)) {
                $result[$key] = LocalizedText::resolve($value);

                continue;
            }

            $result[$key] = is_array($value) ? $this->resolveSettings($value) : $value;

            if (isset(self::LINK_KEYS[$key])) {
                $result[self::LINK_KEYS[$key]] = StorefrontLinkTarget::for($value);
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function media(mixed $value): ?array
    {
        if ($value instanceof Media) {
            $value = $value->toArray();
        }

        if (! is_array($value)) {
            return null;
        }

        return [
            'id' => $value['id'] ?? null,
            'type' => $value['type'] ?? null,
            'url' => $value['url'] ?? null,
            'thumbnail_url' => $value['thumbnail_url'] ?? null,
            'small_thumbnail_url' => $value['small_thumbnail_url'] ?? null,
            'alt' => $value['alt'] ?? null,
            'width' => $value['width'] ?? null,
            'height' => $value['height'] ?? null,
        ];
    }
}
