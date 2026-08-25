import { CheckCircle2Icon } from 'lucide-react';
import { useState } from 'react';

import * as IntegrationSettingController from '@/actions/App/Http/Controllers/Admin/IntegrationSettingController';
import * as SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import { Heading } from '@/components/admin/heading';
import { AnalyticsField } from '@/components/admin/integration/analytics-field';
import {
    IntegrationDialog,
    type IntegrationDialogRenderProps,
} from '@/components/admin/integration/integration-dialog';
import { ProBadge } from '@/components/admin/pro/pro-badge';
import { useProUpgrade } from '@/components/admin/pro/pro-upgrade-context';
import { SectionHeading } from '@/components/admin/section-heading';
import {
    GoogleAnalyticsIcon,
    GoogleIcon,
    GoogleMerchantIcon,
    GoogleTagManagerIcon,
    MetaIcon,
    PinterestIcon,
    TikTokIcon,
} from '@/components/brand-icons';
import { Button } from '@/components/ui/button';
import { HelpBlock } from '@/components/ui/help-block';
import { __ } from '@/lib/i18n';
import type { IntegrationSettings } from '@/types';

interface IntegrationItem {
    key: string;
    name: string;
    description: string;
    icon: (props: { className?: string }) => React.ReactNode;
    action?: 'connect' | 'enable';
    isConnected: (settings: IntegrationSettings) => boolean;
    dialog: (props: IntegrationDialogRenderProps & { settings: IntegrationSettings }) => React.ReactNode;
}

interface ProIntegrationItem {
    key: string;
    name: string;
    description: string;
    icon: (props: { className?: string }) => React.ReactNode;
}

interface IntegrationGroup {
    heading: string;
    items: IntegrationItem[];
}

interface ProIntegrationGroup {
    heading: string;
    items: ProIntegrationItem[];
}

const analytics: IntegrationItem[] = [
    {
        key: 'google_analytics',
        name: 'Google Analytics',
        description: __('Track visitor behavior, traffic sources, and conversions'),
        icon: GoogleAnalyticsIcon,
        isConnected: (settings) => !!settings.integration_google_analytics_id,
        dialog: ({ settings, errors }) => (
            <AnalyticsField
                name="integration_google_analytics_id"
                label={__('Measurement ID')}
                placeholder="G-XXXXXXXXXX"
                defaultValue={settings.integration_google_analytics_id}
                error={errors.integration_google_analytics_id}
            />
        ),
    },
    {
        key: 'google_tag_manager',
        name: 'Google Tag Manager',
        description: __('Manage all tracking scripts and tags from a single place'),
        icon: GoogleTagManagerIcon,
        isConnected: (settings) => !!settings.integration_google_tag_manager_id,
        dialog: ({ settings, errors }) => (
            <AnalyticsField
                name="integration_google_tag_manager_id"
                label={__('Container ID')}
                placeholder="GTM-XXXXXXX"
                defaultValue={settings.integration_google_tag_manager_id}
                error={errors.integration_google_tag_manager_id}
            />
        ),
    },
    {
        key: 'facebook_pixel',
        name: 'Meta Pixel',
        description: __('Track conversions for Facebook and Instagram advertising'),
        icon: MetaIcon,
        isConnected: (settings) => !!settings.integration_meta_pixel_id,
        dialog: ({ settings, errors }) => (
            <AnalyticsField
                name="integration_meta_pixel_id"
                label={__('Pixel ID')}
                placeholder="XXXXXXXXXXXXXXX"
                defaultValue={settings.integration_meta_pixel_id}
                error={errors.integration_meta_pixel_id}
            />
        ),
    },
    {
        key: 'tiktok_pixel',
        name: 'TikTok Pixel',
        description: __('Measure TikTok ad effectiveness and track customer actions'),
        icon: TikTokIcon,
        isConnected: (settings) => !!settings.integration_tiktok_pixel_id,
        dialog: ({ settings, errors }) => (
            <AnalyticsField
                name="integration_tiktok_pixel_id"
                label={__('Pixel ID')}
                placeholder="XXXXXXXXXXXXX"
                defaultValue={settings.integration_tiktok_pixel_id}
                error={errors.integration_tiktok_pixel_id}
            />
        ),
    },
    {
        key: 'pinterest_tag',
        name: 'Pinterest Tag',
        description: __('Track conversions and build audiences for Pinterest ads'),
        icon: PinterestIcon,
        isConnected: (settings) => !!settings.integration_pinterest_tag_id,
        dialog: ({ settings, errors }) => (
            <AnalyticsField
                name="integration_pinterest_tag_id"
                label={__('Tag ID')}
                placeholder="XXXXXXXXXXXXX"
                defaultValue={settings.integration_pinterest_tag_id}
                error={errors.integration_pinterest_tag_id}
            />
        ),
    },
];

const catalogs: ProIntegrationItem[] = [
    {
        key: 'google_merchant',
        name: __('Google Merchant Center'),
        description: __('List your catalog in Google Shopping'),
        icon: GoogleMerchantIcon,
    },
    {
        key: 'meta_catalog',
        name: __('Meta Catalog'),
        description: __('List your catalog on Facebook and Instagram'),
        icon: MetaIcon,
    },
];

const socialLogin: ProIntegrationItem[] = [
    {
        key: 'google_login',
        name: 'Google',
        description: __('Let customers sign in with their Google account'),
        icon: GoogleIcon,
    },
];

const proGroups: ProIntegrationGroup[] = [
    { heading: __('Product catalogs'), items: catalogs },
    { heading: __('Social login'), items: socialLogin },
];

const groups: IntegrationGroup[] = [{ heading: __('Analytics & tracking'), items: analytics }];

export default function Integration({ settings }: { settings: IntegrationSettings }) {
    const [activeDialog, setActiveDialog] = useState<string | null>(null);
    const { open: openProUpgrade } = useProUpgrade();

    return (
        <div className="mx-auto max-w-3xl space-y-6">
            <Heading
                title={__('Integration')}
                description={__('Connect third-party services')}
                backHref={SettingController.index()}
            />

            {groups.map((group) => (
                <div key={group.heading} className="space-y-4">
                    <SectionHeading>{group.heading}</SectionHeading>
                    <div className="divide-y rounded-lg border shadow-xs">
                        {group.items.map((item) => {
                            const isConnected = item.isConnected(settings);
                            const activateLabel = item.action === 'enable' ? __('Enable') : __('Connect');
                            const activeLabel = item.action === 'enable' ? __('Enabled') : __('Connected');

                            return (
                                <div key={item.key} className="flex items-center gap-4 p-4">
                                    <div className="flex size-10 shrink-0 items-center justify-center rounded-lg border bg-background">
                                        <item.icon className="size-5" />
                                    </div>

                                    <div className="min-w-0 flex-1 space-y-0.5 text-sm">
                                        <p className="font-medium">{item.name}</p>
                                        <HelpBlock className="truncate">{item.description}</HelpBlock>
                                    </div>

                                    <div className="shrink-0">
                                        <Button variant="outline" onClick={() => setActiveDialog(item.key)}>
                                            {isConnected && <CheckCircle2Icon className="text-emerald-500" />}
                                            {isConnected ? activeLabel : activateLabel}
                                        </Button>
                                    </div>

                                    <IntegrationDialog
                                        open={activeDialog === item.key}
                                        onOpenChange={(open) => setActiveDialog(open ? item.key : null)}
                                        title={item.name}
                                        description={item.description}
                                        isConnected={isConnected}
                                        activateLabel={activateLabel}
                                    >
                                        {(props) => item.dialog({ ...props, settings })}
                                    </IntegrationDialog>
                                </div>
                            );
                        })}
                    </div>
                </div>
            ))}

            {proGroups.map((group) => (
                <div key={group.heading} className="space-y-4">
                    <SectionHeading>{group.heading}</SectionHeading>
                    <div className="divide-y rounded-lg border shadow-xs">
                        {group.items.map((item) => (
                            <div key={item.key} className="flex items-center gap-4 p-4">
                                <div className="flex size-10 shrink-0 items-center justify-center rounded-lg border bg-background opacity-60">
                                    <item.icon className="size-5" />
                                </div>

                                <div className="min-w-0 flex-1 space-y-0.5 text-sm">
                                    <p className="flex items-center gap-2 font-medium">
                                        {item.name}
                                        <ProBadge />
                                    </p>
                                    <HelpBlock className="truncate">{item.description}</HelpBlock>
                                </div>

                                <div className="shrink-0">
                                    <Button variant="outline" onClick={() => openProUpgrade(item.name)}>
                                        {__('Learn more')}
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            ))}
        </div>
    );
}

Integration.layout = {
    breadcrumbs: [
        { title: __('Settings'), href: SettingController.index() },
        { title: __('Integration'), href: IntegrationSettingController.show() },
    ],
};
