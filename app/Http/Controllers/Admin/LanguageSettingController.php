<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateSettingsAction;
use App\Enums\Locale;
use App\Enums\SettingGroup;
use App\Http\Requests\Admin\UpdateLanguageSettingRequest;
use App\Models\Setting;
use App\Utilities\Translations;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class LanguageSettingController
{
    public function show(): Response
    {
        $languageSettings = Setting::getByGroup(SettingGroup::Locale);

        return Inertia::render('admin/settings/language', [
            'settings' => $languageSettings,
            'availableLanguages' => $this->getAvailableLanguages(),
        ]);
    }

    public function update(UpdateLanguageSettingRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }

    /**
     * @return array<int, array{code: string, name: string}>
     */
    private function getAvailableLanguages(): array
    {
        return array_map(
            fn (string $code): array => [
                'code' => $code,
                'name' => Locale::nativeName($code),
            ],
            Translations::availableLocales(),
        );
    }
}
