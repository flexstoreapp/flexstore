import { Link } from '@inertiajs/react';

import * as CategoryProductController from '@/actions/App/Http/Controllers/Storefront/CategoryProductController';
import * as HomepageController from '@/actions/App/Http/Controllers/Storefront/HomepageController';
import { PageHeader } from '@/components/storefront/page-header';
import { Section } from '@/components/storefront/section';
import { __, transChoice } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { CategoryIndexItem } from '@/types';

interface CategoryListProps {
    categories: CategoryIndexItem[];
}

export default function CategoryList({ categories }: CategoryListProps) {
    return (
        <>
            <PageHeader
                crumbs={[{ label: __('Home'), href: HomepageController.show() }, { label: __('Categories') }]}
                heading={__('All categories')}
            />

            <Section className="mt-6 pb-12" aria-label={__('Categories')}>
                <div className="relative overflow-hidden rounded-md bg-surface after:pointer-events-none after:absolute after:inset-0 after:rounded-md after:border after:border-line">
                    <ul className="grid list-none grid-cols-1 p-0 md:grid-cols-2 xl:grid-cols-3">
                        {categories.map((category) => (
                            <li
                                key={category.id}
                                className="group flex flex-col gap-4 border-e border-b border-line p-7"
                            >
                                <Link
                                    href={CategoryProductController.show(category.url_handle)}
                                    className="group/title flex w-fit flex-col gap-1"
                                >
                                    <h2 className="m-0 text-5xl leading-tight font-semibold text-ink transition group-hover/title:text-primary">
                                        {getTranslation(category.name)}
                                    </h2>
                                    <span className="text-sm text-muted">
                                        {transChoice(':count product|:count products', category.product_count)}
                                    </span>
                                </Link>

                                {category.children.length > 0 && (
                                    <nav aria-label={getTranslation(category.name)} className="flex flex-col gap-2">
                                        {category.children.map((child) => (
                                            <Link
                                                key={child.id}
                                                href={CategoryProductController.show(child.url_handle)}
                                                className="w-fit text-muted transition-colors hover:text-primary"
                                            >
                                                {getTranslation(child.name)}
                                            </Link>
                                        ))}
                                    </nav>
                                )}
                            </li>
                        ))}
                    </ul>
                </div>
            </Section>
        </>
    );
}
