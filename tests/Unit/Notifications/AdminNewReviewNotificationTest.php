<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Review;
use App\Notifications\AdminNewReviewNotification;
use Illuminate\Notifications\Messages\MailMessage;

covers(AdminNewReviewNotification::class);

uses()->group('notifications');

test('uses mail channel', function () {
    $review = Review::factory()->create();
    $review->load('product');
    $notification = new AdminNewReviewNotification($review);

    expect($notification->via($review))->toBe(['mail']);
});

test('mail contains product title in subject', function () {
    $product = Product::factory()->create(['title' => 'Amazing Gadget']);
    $review = Review::factory()->create(['product_id' => $product->id, 'rating' => 4]);
    $review->load('product');
    $notification = new AdminNewReviewNotification($review);

    $mail = $notification->toMail($review);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toContain('Amazing Gadget');
});

test('mail contains rating and product title', function () {
    $product = Product::factory()->create(['title' => 'Cool Product']);
    $review = Review::factory()->create(['product_id' => $product->id, 'rating' => 5]);
    $review->load('product');
    $notification = new AdminNewReviewNotification($review);

    $rendered = $notification->toMail($review)->render();

    expect($rendered)->toContain('Cool Product');
});

test('mail contains link to reviews page', function () {
    $review = Review::factory()->create();
    $review->load('product');
    $notification = new AdminNewReviewNotification($review);

    $rendered = $notification->toMail($review)->render();

    expect($rendered)->toContain(route('admin.reviews.index'));
});
