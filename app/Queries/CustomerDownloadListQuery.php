<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\PaymentStatus;
use App\Models\OrderItemDownload;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final readonly class CustomerDownloadListQuery
{
    /**
     * @return LengthAwarePaginator<int, OrderItemDownload>
     */
    public function execute(User $user, int $perPage = 10): LengthAwarePaginator
    {
        $downloads = OrderItemDownload::query()
            ->where('customer_id', $user->id)
            ->whereHas('order', fn (Builder $query): Builder => $query
                ->whereNull('canceled_at')
                ->whereNotIn('payment_status', [PaymentStatus::Refunded->value, PaymentStatus::Canceled->value]))
            ->select([
                'id', 'order_id', 'token', 'name', 'original_filename',
                'file_size', 'mime_type', 'download_count', 'created_at',
            ])
            ->with(['order:id,created_at,canceled_at,payment_status'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $downloads->getCollection()->each(fn (OrderItemDownload $download) => $download->append([
            'is_available',
        ]));

        return $downloads;
    }
}
