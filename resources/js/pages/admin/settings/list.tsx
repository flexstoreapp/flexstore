import { Head, Link } from '@inertiajs/react';
import {
    BellIcon,
    BlocksIcon,
    DollarSignIcon,
    GlobeIcon,
    MailIcon,
    MonitorIcon,
    PercentIcon,
    SearchIcon,
    SendIcon,
    Settings2Icon,
    ShieldCheckIcon,
    ShoppingCartIcon,
    StoreIcon,
    TruckIcon,
    WalletIcon,
} from 'lucide-react';

import * as CheckoutSettingController from '@/actions/App/Http/Controllers/Admin/CheckoutSettingController';
import * as CurrencySettingController from '@/actions/App/Http/Controllers/Admin/CurrencySettingController';
import * as GeneralSettingController from '@/actions/App/Http/Controllers/Admin/GeneralSettingController';
import * as IntegrationSettingController from '@/actions/App/Http/Controllers/Admin/IntegrationSettingController';
import * as LanguageSettingController from '@/actions/App/Http/Controllers/Admin/LanguageSettingController';
import * as MailSettingController from '@/actions/App/Http/Controllers/Admin/MailSettingController';
import * as NewsletterSettingController from '@/actions/App/Http/Controllers/Admin/NewsletterSettingController';
import * as NotificationSettingController from '@/actions/App/Http/Controllers/Admin/NotificationSettingController';
import * as PaymentSettingController from '@/actions/App/Http/Controllers/Admin/PaymentSettingController';
import * as PolicySettingController from '@/actions/App/Http/Controllers/Admin/PolicySettingController';
import * as SeoSettingController from '@/actions/App/Http/Controllers/Admin/SeoSettingController';
import * as SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import * as ShippingSettingController from '@/actions/App/Http/Controllers/Admin/ShippingSettingController';
import * as StoreSettingController from '@/actions/App/Http/Controllers/Admin/StoreSettingController';
import * as SystemSettingController from '@/actions/App/Http/Controllers/Admin/SystemSettingController';
import * as TaxSettingController from '@/actions/App/Http/Controllers/Admin/TaxSettingController';
import { Heading } from '@/components/admin/heading';
import { Card, CardContent } from '@/components/ui/card';
import { HelpBlock } from '@/components/ui/help-block';
import { usePermissions } from '@/hooks/admin/use-permissions';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import type { SettingGroup } from '@/types';

const settingGroups: SettingGroup[] = [
    {
        title: __('General'),
        description: __('General settings and preferences'),
        icon: Settings2Icon,
        href: GeneralSettingController.show(),
        permission: Permission.SettingsGeneralConfigure,
    },
    {
        title: __('Store'),
        description: __('Store settings and preferences'),
        icon: StoreIcon,
        href: StoreSettingController.show(),
        permission: Permission.SettingsStoreConfigure,
    },
    {
        title: __('Language'),
        description: __('Language settings and preferences'),
        icon: GlobeIcon,
        href: LanguageSettingController.show(),
        permission: Permission.SettingsLanguageConfigure,
    },
    {
        title: __('Currency'),
        description: __('Currency settings and preferences'),
        icon: DollarSignIcon,
        href: CurrencySettingController.show(),
        permission: Permission.SettingsCurrencyConfigure,
    },
    {
        title: __('Shipping'),
        description: __('Shipping carriers and rates'),
        icon: TruckIcon,
        href: ShippingSettingController.show(),
        permission: Permission.SettingsShippingConfigure,
    },
    {
        title: __('Tax'),
        description: __('Tax settings and preferences'),
        icon: PercentIcon,
        href: TaxSettingController.show(),
        permission: Permission.SettingsTaxConfigure,
    },
    {
        title: __('Payment'),
        description: __('Integration with payment gateways'),
        icon: WalletIcon,
        href: PaymentSettingController.show(),
        permission: Permission.SettingsPaymentConfigure,
    },
    {
        title: __('Checkout'),
        description: __('Checkout behavior and payment links'),
        icon: ShoppingCartIcon,
        href: CheckoutSettingController.show(),
        permission: Permission.SettingsCheckoutConfigure,
    },
    {
        title: __('Newsletter'),
        description: __('Integration with newsletter providers'),
        icon: MailIcon,
        href: NewsletterSettingController.show(),
        permission: Permission.SettingsNewsletterConfigure,
    },
    {
        title: __('Mail'),
        description: __('SMTP server and sender details'),
        icon: SendIcon,
        href: MailSettingController.show(),
        permission: Permission.SettingsMailConfigure,
    },
    {
        title: __('Notification'),
        description: __('Configure email notifications'),
        icon: BellIcon,
        href: NotificationSettingController.show(),
        permission: Permission.SettingsNotificationConfigure,
    },
    {
        title: __('Policy'),
        description: __('Returns and legal policies'),
        icon: ShieldCheckIcon,
        href: PolicySettingController.show(),
        permission: Permission.SettingsPolicyConfigure,
    },
    {
        title: __('SEO'),
        description: __('Improve your site visibility'),
        icon: SearchIcon,
        href: SeoSettingController.show(),
        permission: Permission.SettingsSeoConfigure,
    },
    {
        title: __('Integration'),
        description: __('Connect third-party services'),
        icon: BlocksIcon,
        href: IntegrationSettingController.show(),
        permission: Permission.SettingsIntegrationConfigure,
    },
    {
        title: __('System'),
        description: __('System maintenance, cache, and updates'),
        icon: MonitorIcon,
        href: SystemSettingController.show(),
        permission: Permission.SettingsSystemConfigure,
    },
];

export default function SettingList() {
    const { hasPermission } = usePermissions();

    const visibleSettingGroups = settingGroups.filter((settingGroup) => {
        if (!settingGroup.permission) return true;
        return hasPermission(settingGroup.permission);
    });

    return (
        <>
            <Head title={__('Settings')} />
            <Heading title={__('Settings')} description={__('Manage settings and preferences')} />

            <div className="grid grid-cols-2 gap-6 lg:grid-cols-3 xl:grid-cols-4">
                {visibleSettingGroups.map((settingGroup) => (
                    <Link key={settingGroup.title} href={settingGroup.href} prefetch>
                        <Card className="h-38 shadow-xs transition-colors hover:bg-accent/50 md:h-45 dark:hover:bg-accent">
                            <CardContent className="flex flex-1 flex-col items-center justify-center text-center">
                                <div className="mb-2 flex size-12 items-center justify-center rounded-full bg-muted">
                                    <settingGroup.icon />
                                </div>
                                <p className="font-semibold">{settingGroup.title}</p>
                                <HelpBlock className="mt-1 hidden text-xs md:block">
                                    {settingGroup.description}
                                </HelpBlock>
                            </CardContent>
                        </Card>
                    </Link>
                ))}
            </div>
        </>
    );
}

SettingList.layout = {
    breadcrumbs: [{ title: __('Settings'), href: SettingController.index() }],
};
