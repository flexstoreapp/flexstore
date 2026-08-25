<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\StoreDigitalFileAction;
use App\Http\Requests\Admin\StoreDigitalFileRequest;
use Illuminate\Http\JsonResponse;

final readonly class DigitalFileController
{
    public function store(StoreDigitalFileRequest $request, StoreDigitalFileAction $storeDigitalFileAction): JsonResponse
    {
        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('file');

        $media = $storeDigitalFileAction->handle($file);

        return response()->json($media->toArray() + [
            'name' => $media->original_filename,
        ]);
    }
}
