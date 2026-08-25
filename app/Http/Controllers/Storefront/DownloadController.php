<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\ConsumeOrderItemDownloadAction;
use App\Models\OrderItemDownload;
use App\Models\User;
use App\Queries\CustomerDownloadListQuery;
use App\Utilities\StorefrontHead;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class DownloadController
{
    public function index(#[CurrentUser] User $user, CustomerDownloadListQuery $query): Response
    {
        StorefrontHead::page(__('Downloads'));

        return Inertia::render('storefront/account/downloads/list', [
            'downloads' => $query->execute($user),
        ]);
    }

    public function show(
        Request $request,
        OrderItemDownload $download,
        ConsumeOrderItemDownloadAction $consumeDownload,
    ): StreamedResponse {
        $isOwner = $download->customer_id !== null && $request->user()?->id === $download->customer_id;

        abort_unless($request->hasValidSignature() || $isOwner, 403);
        abort_if($download->isRevoked(), 403, __('This download is no longer available.'));
        abort_unless(Storage::exists($download->file_path), 404);

        abort_unless($consumeDownload->handle($download), 403, __('This download is no longer available.'));

        return Storage::download($download->file_path, $download->original_filename, [
            'Content-Type' => $download->mime_type ?? 'application/octet-stream',
        ]);
    }
}
