<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\StoreMediaAction;
use App\Http\Requests\Admin\StoreMediaRequest;
use Illuminate\Http\JsonResponse;

final readonly class MediaController
{
    public function store(StoreMediaRequest $request, StoreMediaAction $storeMediaAction): JsonResponse
    {
        $media = $storeMediaAction->handle(
            $request->file('file'),
            $request->safe()->boolean('generate_thumbnail', true),
            $request->safe()->boolean('preserve_format'),
        );

        return response()->json($media);
    }
}
