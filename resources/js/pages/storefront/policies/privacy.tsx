import { PolicyPage } from '@/components/storefront/policy-page';
import { __ } from '@/lib/i18n';

export default function PrivacyPolicy({ content }: { content: string | null }) {
    return <PolicyPage title={__('Privacy policy')} content={content} />;
}
