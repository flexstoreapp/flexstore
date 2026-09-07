<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\SetDefaultCustomerAddressAction;
use App\Http\Resources\Api\V1\CustomerAddressResource;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

final readonly class SetDefaultAddressController
{
    public function __invoke(
        CustomerAddress $address,
        #[CurrentUser] User $user,
        SetDefaultCustomerAddressAction $action,
    ): CustomerAddressResource {
        abort_unless($user->id === $address->user_id, 403);

        $action->handle($user, $address);

        return new CustomerAddressResource($address->refresh());
    }
}
