<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateSettingsAction;
use App\Enums\SettingGroup;
use App\Http\Requests\Admin\UpdateStoreSettingRequest;
use App\Models\Media;
use App\Models\Setting;
use App\Utilities\LocalizedText;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class StoreSettingController
{
    public function show(): Response
    {
        $settings = Setting::getByGroup(SettingGroup::Store);
        $settings['store_description'] = LocalizedText::resolve($settings->get('store_description'));

        return Inertia::render('admin/settings/store', [
            'settings' => $settings,
            'assetMedia' => $this->assetMedia(),
        ]);
    }

    public function update(UpdateStoreSettingRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }

    /**
     * @return array<string, Media|null>
     */
    private function assetMedia(): array
    {
        $keys = ['store_logo', 'store_logo_dark', 'store_favicon'];

        $ids = Setting::query()
            ->whereIn('key', $keys)
            ->toBase()
            ->pluck('value', 'key')
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->map(fn (mixed $value): int => (int) $value);

        $media = Media::query()
            ->select(Media::displayColumns())
            ->whereIn('id', $ids->values())
            ->get()
            ->keyBy('id');

        return collect($keys)
            ->mapWithKeys(fn (string $key): array => [$key => $media->get($ids->get($key))])
            ->all();
    }
}
