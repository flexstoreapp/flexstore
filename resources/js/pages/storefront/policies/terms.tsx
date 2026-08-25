import { PolicyPage } from '@/components/storefront/policy-page';
import { __ } from '@/lib/i18n';

export default function TermsOfService({ content }: { content: string | null }) {
    return <PolicyPage title={__('Terms of service')} content={content} />;
}
