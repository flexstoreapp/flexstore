<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\UpdateReviewInput;
use App\Models\Review;

final readonly class UpdateReviewAction
{
    public function handle(Review $review, UpdateReviewInput $input): Review
    {
        $review->update([
            'rating' => $input->has('rating') ? $input->rating : $review->rating,
            'title' => $input->has('title') ? $input->title : $review->title,
            'content' => $input->has('content') ? $input->content : $review->content,
        ]);

        return $review;
    }
}
