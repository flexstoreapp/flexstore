import * as HomepageController from '@/actions/App/Http/Controllers/Storefront/HomepageController';
import { ArticleBody } from '@/components/storefront/article-body';
import { Breadcrumb } from '@/components/storefront/breadcrumb';
import { Section } from '@/components/storefront/section';
import { __ } from '@/lib/i18n';

interface PolicyPageProps {
    title: string;
    content: string | null;
}

export function PolicyPage({ title, content }: PolicyPageProps) {
    return (
        <>
            <Section className="pt-6 pb-12">
                <article className="mx-auto max-w-2xl">
                    <Breadcrumb crumbs={[{ label: __('Home'), href: HomepageController.show() }, { label: title }]} />

                    <h1 className="mt-3 mb-8 text-7xl font-semibold text-ink">{title}</h1>

                    {content ? (
                        <ArticleBody html={content} />
                    ) : (
                        <p className="text-muted">{__('This policy has not been published yet.')}</p>
                    )}
                </article>
            </Section>
        </>
    );
}
