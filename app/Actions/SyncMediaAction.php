<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Media;
use App\Models\Mediable;
use Illuminate\Database\Eloquent\Model;

final readonly class SyncMediaAction
{
    /**
     * @param  array<int>  $mediaIds
     */
    public function handle(Model $model, array $mediaIds): void
    {
        $type = $model->getMorphClass();
        $id = (string) $model->getKey();
        $mediaIds = array_values($mediaIds);

        $removedMediaIds = Mediable::query()
            ->where('mediable_type', $type)
            ->where('mediable_id', $id)
            ->whereNotIn('media_id', $mediaIds)
            ->pluck('media_id')
            ->all();

        Mediable::query()
            ->where('mediable_type', $type)
            ->where('mediable_id', $id)
            ->whereNotIn('media_id', $mediaIds)
            ->delete();

        foreach ($mediaIds as $index => $mediaId) {
            Mediable::query()->updateOrCreate(
                [
                    'mediable_type' => $type,
                    'mediable_id' => $id,
                    'media_id' => $mediaId,
                ],
                ['sort_order' => $index],
            );
        }

        Media::deleteUnreferenced($removedMediaIds);
    }
}
