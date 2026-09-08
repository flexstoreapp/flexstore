<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Api\V1\Admin\SearchUserRequest;
use App\Http\Resources\Api\V1\Admin\UserOptionResource;
use App\Queries\UserSearchQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class UserSearchController
{
    public function __invoke(SearchUserRequest $request, UserSearchQuery $query): AnonymousResourceCollection
    {
        return UserOptionResource::collection($query->execute($request->validated()));
    }
}
