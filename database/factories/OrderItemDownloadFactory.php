<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Media;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductDownload;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\OrderItemDownload>
 */
final class OrderItemDownloadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->slug(3) . '.zip';

        return [
            'order_id' => Order::factory()->paid(),
            'order_item_id' => OrderItem::factory(),
            'product_download_id' => ProductDownload::factory(),
            'media_id' => Media::factory()->file(),
            'customer_id' => null,
            'token' => Str::random(64),
            'name' => fake()->words(2, true),
            'file_path' => 'downloads/' . Str::random(40),
            'original_filename' => $filename,
            'file_size' => fake()->numberBetween(1_000, 50_000_000),
            'mime_type' => 'application/octet-stream',
            'download_count' => 0,
            'last_downloaded_at' => null,
        ];
    }
}
