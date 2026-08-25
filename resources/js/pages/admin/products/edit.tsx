import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';

import * as ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import { Heading } from '@/components/admin/heading';
import { DuplicateProductDialog } from '@/components/admin/product/duplicate-product-dialog';
import { ProductForm } from '@/components/admin/product/product-form';
import type { AdaptiveSelectOption } from '@/components/ui/adaptive-select';
import { Button } from '@/components/ui/button';
import { Can } from '@/components/ui/can';
import { useFormatDate } from '@/hooks/use-format-date';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import { getTranslation } from '@/lib/utils';
import type { Product, TaxCategoryOption } from '@/types';

interface ProductEditProps {
    product: Product;
    taxCategories: TaxCategoryOption[];
    productTypes: AdaptiveSelectOption[];
    maxUploadSize: number;
}

export default function ProductEdit({ product, taxCategories, productTypes, maxUploadSize }: ProductEditProps) {
    const [duplicateDialogOpen, setDuplicateDialogOpen] = useState(false);
    const page = usePage();

    const formatDate = useFormatDate();

    return (
        <>
            <Head title={getTranslation(product.title)} />

            <Heading
                title={getTranslation(product.title)}
                description={__('Last updated on :datetime', { datetime: formatDate(product.updated_at) })}
                backHref={ProductController.index()}
            >
                <Can permission={Permission.ProductsManage}>
                    <Button variant="secondary" onClick={() => setDuplicateDialogOpen(true)}>
                        {__('Duplicate')}
                    </Button>
                </Can>
            </Heading>

            <ProductForm
                key={page.url}
                product={product}
                taxCategories={taxCategories}
                productTypes={productTypes}
                maxUploadSize={maxUploadSize}
            />

            <DuplicateProductDialog
                open={duplicateDialogOpen}
                onOpenChange={setDuplicateDialogOpen}
                product={product}
            />
        </>
    );
}

ProductEdit.layout = ({ product }: ProductEditProps) => ({
    breadcrumbs: [
        { title: __('Products'), href: ProductController.index() },
        { title: getTranslation(product.title), href: ProductController.edit(product) },
    ],
});
