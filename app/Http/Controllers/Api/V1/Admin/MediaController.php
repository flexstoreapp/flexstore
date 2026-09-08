<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\StoreMediaAction;
use App\Http\Requests\Admin\StoreMediaRequest;
use App\Http\Resources\Api\V1\Admin\AdminMediaResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

final readonly class MediaController
{
    public function store(StoreMediaRequest $request, StoreMediaAction $action): JsonResponse
    {
        $file = $request->safe()->file('file');
        assert($file instanceof UploadedFile);

        $media = $action->handle(
            $file,
            $request->safe()->boolean('generate_thumbnail', true),
            $request->safe()->boolean('preserve_format'),
        );

        return (new AdminMediaResource($media))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
