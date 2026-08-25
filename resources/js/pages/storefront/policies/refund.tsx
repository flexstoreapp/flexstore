import { PolicyPage } from '@/components/storefront/policy-page';
import { __ } from '@/lib/i18n';

export default function RefundPolicy({ content }: { content: string | null }) {
    return <PolicyPage title={__('Refund policy')} content={content} />;
}
