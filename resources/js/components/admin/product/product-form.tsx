import { Form } from '@inertiajs/react';
import { useState } from 'react';

import * as ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import { FormSubmit } from '@/components/admin/form-submit';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import type { AdaptiveSelectOption } from '@/components/ui/adaptive-select';
import { useSeoAutofill } from '@/hooks/admin/use-seo-autofill';
import { useUrlHandleField } from '@/hooks/admin/use-url-handle-field';
import { __ } from '@/lib/i18n';
import { handleTransform } from '@/lib/product-form-utils';
import { getTranslation } from '@/lib/utils';
import type { Product, ProductType, ProductVariant, TaxCategoryOption } from '@/types';

import { ProductBasicInfo } from './product-basic-info';
import { ProductDigitalFiles } from './product-digital-files';
import { ProductInventory } from './product-inventory';
import { ProductMedia } from './product-media';
import { ProductPricing } from './product-pricing';
import { ProductSeo } from './product-seo';
import { ProductShipping } from './product-shipping';
import { ProductShopping } from './product-shopping';
import { ProductStatus } from './product-status';
import { ProductTax } from './product-tax';
import { ProductTypeSelect } from './product-type';
import { ProductVariants } from './product-variants';

interface ProductFormProps {
    product?: Product;
    productTypes: AdaptiveSelectOption[];
    taxCategories: TaxCategoryOption[];
    maxUploadSize: number;
}

export function ProductForm({ product, taxCategories, productTypes, maxUploadSize }: ProductFormProps) {
    const [title, setTitle] = useState(getTranslation(product?.title));
    const [description, setDescription] = useState(getTranslation(product?.description));
    const {
        urlHandle,
        onSourceChange: onUrlHandleSourceChange,
        onUrlHandleChange,
        reset: resetUrlHandle,
        needsServerGeneration: urlHandleNeedsServerGeneration,
    } = useUrlHandleField({
        initial: product?.url_handle,
    });
    const seo = useSeoAutofill({
        title: getTranslation(product?.seo_title),
        description: getTranslation(product?.seo_description),
        stripHtml: true,
    });
    const [type, setType] = useState<ProductType>(product?.type ?? 'physical');
    const isDigital = type === 'digital';
    const [variants, setVariants] = useState<ProductVariant[]>(product?.variants ?? []);

    const hasVariants = variants.length > 0;

    const handleTitleChange = (newTitle: string) => {
        setTitle(newTitle);
        seo.syncTitleFromSource(newTitle);

        if (newTitle.trim()) {
            onUrlHandleSourceChange(newTitle.trim());
        }
    };

    const handleDescriptionChange = (value: string) => {
        setDescription(value);
        seo.syncDescriptionFromSource(value);
    };

    const handleVariantsChange = (nextVariants: ProductVariant[]) => {
        setVariants(nextVariants);
    };

    const handleSuccess = () => {
        if (!product) {
            setTitle('');
            setDescription('');
            seo.reset();
            resetUrlHandle();
            setVariants([]);
            setType('physical');
        }
    };

    return (
        <Form
            {...(product ? ProductController.update.form({ product: product.id }) : ProductController.store.form())}
            options={{ preserveScroll: true, only: ['product'] }}
            transform={handleTransform}
            resetOnSuccess={!product}
            setDefaultsOnSuccess
            onSuccess={handleSuccess}
        >
            {({ processing, errors, recentlySuccessful }) => (
                <div className="space-y-6">
                    <UnsavedChangesAlert />
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <div className="space-y-6 lg:col-span-2">
                            <ProductBasicInfo
                                product={product}
                                title={title}
                                onTitleChange={handleTitleChange}
                                description={description}
                                onDescriptionChange={handleDescriptionChange}
                                errors={errors}
                            />

                            <ProductMedia product={product} maxUploadSize={maxUploadSize} />

                            {!hasVariants && <ProductPricing product={product} errors={errors} />}

                            <ProductVariants
                                product={product}
                                errors={errors}
                                isDigital={isDigital}
                                onVariantsChange={handleVariantsChange}
                            />

                            {isDigital && (
                                <ProductDigitalFiles
                                    product={product}
                                    variants={variants}
                                    maxUploadSize={maxUploadSize}
                                    errors={errors}
                                />
                            )}
                        </div>

                        <div className="space-y-6">
                            <ProductTypeSelect value={type} onChange={setType} productTypes={productTypes} />

                            <ProductStatus product={product} errors={errors} />
                            <ProductTax product={product} errors={errors} taxCategories={taxCategories} />

                            {!isDigital && !hasVariants && <ProductInventory product={product} errors={errors} />}

                            {!isDigital && (
                                <ProductShipping product={product} errors={errors} hasVariants={hasVariants} />
                            )}

                            <ProductShopping />

                            <ProductSeo
                                errors={errors}
                                seoTitle={seo.seoTitle}
                                seoDescription={seo.seoDescription}
                                urlHandle={urlHandle}
                                urlHandleNeedsServerGeneration={urlHandleNeedsServerGeneration}
                                onSeoTitleChange={seo.handleSeoTitleChange}
                                onSeoDescriptionChange={seo.handleSeoDescriptionChange}
                                onUrlHandleChange={onUrlHandleChange}
                            />

                            <FormSubmit
                                showAddMore={!product}
                                processing={processing}
                                recentlySuccessful={recentlySuccessful}
                            >
                                {product ? __('Update product') : __('Add product')}
                            </FormSubmit>
                        </div>
                    </div>
                </div>
            )}
        </Form>
    );
}
