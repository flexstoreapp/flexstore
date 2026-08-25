import { Section } from '@/components/storefront/section';
import { __ } from '@/lib/i18n';

export function HomepageEmptyState() {
    return (
        <Section className="flex min-h-[55vh] items-center justify-center py-24 text-center">
            <h1 className="m-0 font-head text-4xl font-bold text-ink sm:text-5xl">{__('Homepage not configured')}</h1>
        </Section>
    );
}
