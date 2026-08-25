<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\MergeGuestWishlistAction;
use App\Models\User;
use Illuminate\Auth\Events\Login;

final readonly class MergeGuestWishlistOnLogin
{
    public function __construct(
        private MergeGuestWishlistAction $mergeGuestWishlistAction,
    ) {
    }

    public function handle(Login $event): void
    {
        $wishlistId = request()->cookie('wishlist_id');

        if (! is_string($wishlistId) || ! $event->user instanceof User) {
            return;
        }

        $this->mergeGuestWishlistAction->handle($wishlistId, $event->user);
    }
}
