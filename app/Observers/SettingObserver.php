<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

final readonly class SettingObserver
{
    public function saved(Setting $setting): void
    {
        Cache::forget("settings.{$setting->key}");
        Cache::memo('array')->forget("memo:settings.{$setting->key}");

        if ($setting->group) {
            Cache::forget("settings.group.{$setting->group->value}");
            Cache::memo('array')->forget("memo:settings.group.{$setting->group->value}");
        }
    }
}
