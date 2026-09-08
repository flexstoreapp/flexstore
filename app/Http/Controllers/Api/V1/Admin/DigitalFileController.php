<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\StoreDigitalFileAction;
use App\Http\Requests\Admin\StoreDigitalFileRequest;
use App\Http\Resources\Api\V1\Admin\DigitalFileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

final readonly class DigitalFileController
{
    public function store(StoreDigitalFileRequest $request, StoreDigitalFileAction $action): JsonResponse
    {
        $file = $request->safe()->file('file');
        assert($file instanceof UploadedFile);

        return (new DigitalFileResource($action->handle($file)))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
