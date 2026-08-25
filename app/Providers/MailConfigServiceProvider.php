<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\SettingGroup;
use App\Installer\Contracts\InstallationState;
use App\Models\Setting;
use Illuminate\Support\ServiceProvider;

final class MailConfigServiceProvider extends ServiceProvider
{
    private const array MAP = [
        'mail.mailers.smtp.host' => 'mail_host',
        'mail.mailers.smtp.port' => 'mail_port',
        'mail.mailers.smtp.encryption' => 'mail_encryption',
        'mail.mailers.smtp.username' => 'mail_username',
        'mail.mailers.smtp.password' => 'mail_password',
        'mail.from.address' => 'mail_from_address',
        'mail.from.name' => 'mail_from_name',
    ];

    public function boot(InstallationState $installation): void
    {
        if (! $installation->isInstalled() || ! $installation->databaseIsMigrated()) {
            return;
        }

        $settings = Setting::getByGroup(SettingGroup::Mail);

        foreach (self::MAP as $configKey => $settingKey) {
            $value = $settings->get($settingKey);
            if (filled($value)) {
                config()->set($configKey, $value);
            }
        }

        if (filled($settings->get('mail_host'))) {
            config()->set('mail.default', 'smtp');
        }
    }
}
