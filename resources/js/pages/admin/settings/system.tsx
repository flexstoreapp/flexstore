import { Head } from '@inertiajs/react';
import { TriangleAlertIcon } from 'lucide-react';

import * as SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import * as SystemSettingController from '@/actions/App/Http/Controllers/Admin/SystemSettingController';
import { Heading } from '@/components/admin/heading';
import { ProFeatureBanner } from '@/components/admin/pro/pro-feature-banner';
import { CacheSection } from '@/components/admin/system/cache-section';
import { MaintenanceModeSection } from '@/components/admin/system/maintenance-mode-section';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Separator } from '@/components/ui/separator';
import { __ } from '@/lib/i18n';

interface SystemSettings {
    maintenance_mode: boolean;
    maintenance_allowed_ips: string[];
}

interface SystemProps {
    settings: SystemSettings;
    currentVersion: string;
}

export default function System({ settings, currentVersion }: SystemProps) {
    const isDown = settings.maintenance_mode;

    return (
        <>
            <Head title={__('System')} />

            <div className="mx-auto max-w-4xl space-y-6">
                <Heading
                    title={__('System')}
                    description={__('System maintenance, cache, and updates')}
                    backHref={SettingController.index()}
                />

                {isDown && (
                    <Alert variant="warning">
                        <TriangleAlertIcon />
                        <AlertTitle>{__('Store is in maintenance mode')}</AlertTitle>
                        <AlertDescription>
                            {__(
                                'Customers cannot access your storefront right now. Only allowed IP addresses can bypass maintenance mode.',
                            )}
                        </AlertDescription>
                    </Alert>
                )}

                <div className="mb-6 space-y-12">
                    <MaintenanceModeSection isDown={isDown} allowedIps={settings.maintenance_allowed_ips} />

                    <Separator />

                    <CacheSection />

                    <Separator />

                    <div className="space-y-4">
                        <p className="text-sm text-muted-foreground">
                            {__('Version :version', { version: currentVersion })}
                        </p>

                        <ProFeatureBanner
                            title={__('Software updates')}
                            description={__(
                                'Get update notices, patch uploads and license management with FlexStore Pro.',
                            )}
                        />
                    </div>
                </div>
            </div>
        </>
    );
}

System.layout = {
    breadcrumbs: [
        { title: __('Settings'), href: SettingController.index() },
        { title: __('System'), href: SystemSettingController.show() },
    ],
};
