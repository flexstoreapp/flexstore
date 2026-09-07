<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\OrderItemDownload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin OrderItemDownload
 */
final class OrderDownloadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->token,
            'order_id' => $this->order_id,
            'order_item_id' => $this->order_item_id,
            'name' => $this->name,
            'original_filename' => $this->original_filename,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'download_count' => $this->download_count,
            'is_available' => $this->is_available,
            'url' => route('api.v1.account.downloads.show', $this->token),
        ];
    }
}
