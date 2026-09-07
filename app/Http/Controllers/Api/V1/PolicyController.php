<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\StorePolicy;
use App\Http\Resources\Api\V1\PolicyResource;
use App\Models\Setting;

final readonly class PolicyController
{
    public function show(StorePolicy $policy): PolicyResource
    {
        $content = Setting::getValue($policy->settingKey());

        abort_unless(is_string($content) && mb_trim($content) !== '', 404);

        return new PolicyResource(['policy' => $policy, 'content' => $content]);
    }
}
