<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StoreReviewInput;
use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\Setting;
use App\Notifications\AdminNewReviewNotification;

final readonly class StoreReviewAction
{
    public function __construct(
        private SendAdminNotificationAction $sendAdminNotificationAction,
    ) {
    }

    public function handle(StoreReviewInput $input): Review
    {
        $review = Review::query()->create([
            'product_id' => $input->productId,
            'user_id' => $input->userId,
            'rating' => $input->rating,
            'title' => $input->title,
            'content' => $input->content,
            'status' => ReviewStatus::Pending,
        ]);

        $review->load('product');

        if (Setting::getValue('notification_admin_new_review')) {
            $this->sendAdminNotificationAction->handle(new AdminNewReviewNotification($review));
        }

        return $review;
    }
}
