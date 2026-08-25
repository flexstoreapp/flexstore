<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MediaType;
use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Media>
 */
#[UseModel(Media::class)]
final class MediaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => MediaType::Image,
            'disk' => 'public',
            'path' => null,
            'external_url' => 'https://picsum.photos/' . fake()->numberBetween(200, 800) . '/400',
            'thumbnail_path' => null,
            'small_thumbnail_path' => null,
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(10_000, 2_000_000),
            'width' => fake()->numberBetween(200, 2000),
            'height' => fake()->numberBetween(200, 2000),
            'duration' => null,
            'original_filename' => fake()->slug() . '.jpg',
        ];
    }

    public function uploaded(): static
    {
        return $this->state(function (array $attributes): array {
            $filename = Str::random(40) . '.webp';

            return [
                'path' => "images/{$filename}",
                'external_url' => null,
                'thumbnail_path' => "thumbnails/{$filename}",
                'small_thumbnail_path' => "thumbnails/small-{$filename}",
                'mime_type' => 'image/webp',
                'original_filename' => fake()->slug() . '.webp',
            ];
        });
    }

    public function file(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => MediaType::File,
            'external_url' => null,
            'disk' => config('filesystems.default'),
            'path' => 'downloads/' . Str::random(40),
            'thumbnail_path' => null,
            'small_thumbnail_path' => null,
            'mime_type' => 'application/octet-stream',
            'width' => null,
            'height' => null,
            'original_filename' => fake()->slug() . '.zip',
        ]);
    }
}
