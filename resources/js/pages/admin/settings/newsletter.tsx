import * as NewsletterSettingController from '@/actions/App/Http/Controllers/Admin/NewsletterSettingController';
import * as SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import { Heading } from '@/components/admin/heading';
import { Beehiiv } from '@/components/admin/newsletter/beehiiv';
import { Kit } from '@/components/admin/newsletter/kit';
import { Mailchimp } from '@/components/admin/newsletter/mailchimp';
import { Mailerlite } from '@/components/admin/newsletter/mailerlite';
import { __ } from '@/lib/i18n';

export default function Newsletter() {
    return (
        <>
            <Heading
                title={__('Newsletter')}
                description={__('Integration with newsletter providers')}
                backHref={SettingController.index()}
            />

            <div className="grid grid-cols-2 gap-6 lg:grid-cols-3 xl:grid-cols-4">
                <Mailchimp />
                <Mailerlite />
                <Kit />
                <Beehiiv />
            </div>
        </>
    );
}

Newsletter.layout = {
    breadcrumbs: [
        { title: __('Settings'), href: SettingController.index() },
        { title: __('Newsletter'), href: NewsletterSettingController.show() },
    ],
};
