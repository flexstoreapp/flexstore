<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\UpdateSettingsInput;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class UpdateSettingsAction
{
    /**
     * @return Collection<int, Setting>
     */
    public function handle(UpdateSettingsInput $input): Collection
    {
        $settings = Setting::query()->whereIn('key', array_keys($input->values))->get();

        DB::transaction(function () use ($settings, $input): void {
            foreach ($input->values as $key => $value) {
                $setting = $settings->firstWhere('key', $key);

                if ($setting === null) {
                    continue;
                }

                $setting->update(['value' => $value]);
            }
        });

        return $settings;
    }
}
