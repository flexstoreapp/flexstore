import * as ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import { Heading } from '@/components/admin/heading';
import { ProductForm } from '@/components/admin/product/product-form';
import type { AdaptiveSelectOption } from '@/components/ui/adaptive-select';
import { __ } from '@/lib/i18n';
import type { TaxCategoryOption } from '@/types';

interface ProductCreateProps {
    taxCategories: TaxCategoryOption[];
    productTypes: AdaptiveSelectOption[];
    maxUploadSize: number;
}

export default function ProductCreate({ taxCategories, productTypes, maxUploadSize }: ProductCreateProps) {
    return (
        <>
            <Heading
                title={__('Add product')}
                description={__('Add a new product to your store')}
                backHref={ProductController.index()}
            />
            <ProductForm taxCategories={taxCategories} productTypes={productTypes} maxUploadSize={maxUploadSize} />
        </>
    );
}

ProductCreate.layout = {
    breadcrumbs: [
        { title: __('Products'), href: ProductController.index() },
        { title: __('Add product'), href: ProductController.create() },
    ],
};
