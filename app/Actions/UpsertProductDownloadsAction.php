<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\ProductDownloadInput;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductDownload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class UpsertProductDownloadsAction
{
    /**
     * @param  list<ProductDownloadInput>  $downloads
     */
    public function handle(Product $product, array $downloads): void
    {
        DB::transaction(function () use ($product, $downloads): void {
            $existing = $product->downloads()->get()->keyBy('id');
            $validVariantIds = $product->variants()->pluck('id')->all();

            $mediaById = Media::query()
                ->whereIn('id', array_map(fn (ProductDownloadInput $download): int => $download->mediaId, $downloads))
                ->get()
                ->keyBy('id');

            $keptIds = [];

            foreach ($downloads as $index => $download) {
                $id = $download->id !== null && $existing->has($download->id)
                    ? $download->id
                    : Str::uuid7()->toString();
                $keptIds[] = $id;

                $variantId = $download->variantId !== null && in_array($download->variantId, $validVariantIds, true)
                    ? $download->variantId
                    : null;

                $name = $download->name !== ''
                    ? $download->name
                    : ($mediaById->get($download->mediaId)->original_filename ?? '');

                ProductDownload::query()->updateOrCreate(
                    ['id' => $id],
                    [
                        'product_id' => $product->id,
                        'product_variant_id' => $variantId,
                        'media_id' => $download->mediaId,
                        'name' => $name,
                        'sort_order' => $index,
                    ],
                );
            }

            $removedMediaIds = $existing
                ->reject(fn (ProductDownload $download): bool => in_array($download->id, $keptIds, true))
                ->pluck('media_id')
                ->all();

            $product->downloads()->whereNotIn('id', $keptIds)->delete();

            Media::deleteUnreferenced($removedMediaIds);
        });
    }
}
