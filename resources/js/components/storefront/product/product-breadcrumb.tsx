import * as CategoryProductController from '@/actions/App/Http/Controllers/Storefront/CategoryProductController';
import * as HomepageController from '@/actions/App/Http/Controllers/Storefront/HomepageController';
import type { Crumb } from '@/components/storefront/breadcrumb';
import { PageHeader } from '@/components/storefront/page-header';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { ProductDetailData } from '@/types';

interface ProductBreadcrumbProps {
    category: ProductDetailData['category'];
    title: string;
}

export function ProductBreadcrumb({ category, title }: ProductBreadcrumbProps) {
    const crumbs: Crumb[] = [{ label: __('Home'), href: HomepageController.show() }];

    for (const ancestor of category?.ancestors ?? []) {
        crumbs.push({
            label: getTranslation(ancestor.name, 'Category'),
            href: CategoryProductController.show(ancestor.url_handle),
        });
    }

    if (category) {
        crumbs.push({
            label: getTranslation(category.name, 'Category'),
            href: CategoryProductController.show(category.url_handle),
        });
    }

    crumbs.push({ label: title });

    return <PageHeader crumbs={crumbs} />;
}
