<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\ConsumeOrderItemDownloadAction;
use App\Http\Resources\Api\V1\OrderDownloadResource;
use App\Models\OrderItemDownload;
use App\Models\User;
use App\Queries\CustomerDownloadListQuery;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class DownloadController
{
    public function index(#[CurrentUser] User $user, CustomerDownloadListQuery $query): AnonymousResourceCollection
    {
        return OrderDownloadResource::collection($query->execute($user));
    }

    public function show(
        OrderItemDownload $download,
        #[CurrentUser] User $user,
        ConsumeOrderItemDownloadAction $consumeDownload,
    ): StreamedResponse {
        abort_unless($download->customer_id === $user->id, 403);
        abort_if($download->isRevoked(), 403, __('This download is no longer available.'));
        abort_unless(Storage::exists($download->file_path), 404);

        abort_unless($consumeDownload->handle($download), 403, __('You have reached the download limit for this file.'));

        return Storage::download($download->file_path, $download->original_filename, [
            'Content-Type' => $download->mime_type ?? 'application/octet-stream',
        ]);
    }
}
