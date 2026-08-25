import { lazy, Suspense, useState } from 'react';

import { BrandPicker, type SelectableBrand } from '@/components/admin/brand-picker';
import { CategoryPicker, type SelectableItem } from '@/components/admin/category-picker';
import { ReactiveHiddenInput } from '@/components/admin/reactive-hidden-input';
import { ResourcePickerTrigger } from '@/components/admin/resource-picker';
import { Card, CardContent } from '@/components/ui/card';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { RichTextEditorSkeleton } from '@/components/ui/rich-text-editor-skeleton';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import { type Product } from '@/types';

const RichTextEditor = lazy(() =>
    import('@/components/ui/rich-text-editor').then((m) => ({ default: m.RichTextEditor })),
);

function getSelectableCategory(product: Product | undefined): SelectableItem | null {
    if (!product?.category) return null;
    return { id: product.category.id, name: product.category.name };
}

function getSelectableBrand(product: Product | undefined): SelectableBrand | null {
    if (!product?.brand) return null;
    return { id: product.brand.id, name: product.brand.name, image: product.brand.image };
}

interface ProductBasicInfoProps {
    product: Product | undefined;
    title: string;
    onTitleChange: (value: string) => void;
    description: string;
    onDescriptionChange: (value: string) => void;
    errors: Record<string, string>;
}

export function ProductBasicInfo({
    product,
    title,
    onTitleChange,
    description,
    onDescriptionChange,
    errors,
}: ProductBasicInfoProps) {
    const [categoryPickerOpen, setCategoryPickerOpen] = useState(false);
    const [category, setCategory] = useState<SelectableItem | null>(getSelectableCategory(product));
    const [brandPickerOpen, setBrandPickerOpen] = useState(false);
    const [brand, setBrand] = useState<SelectableBrand | null>(getSelectableBrand(product));

    return (
        <Card>
            <CardContent className="space-y-6">
                <Field>
                    <FieldLabel htmlFor="title">{__('Title')}</FieldLabel>
                    <Input
                        id="title"
                        name="title"
                        type="text"
                        value={title ?? getTranslation(product?.title)}
                        onChange={(e) => onTitleChange?.(e.target.value)}
                        required
                    />
                    <FieldError>{errors.title}</FieldError>
                </Field>

                <Field>
                    <FieldLabel>{__('Description')}</FieldLabel>
                    <Suspense fallback={<RichTextEditorSkeleton />}>
                        <RichTextEditor
                            content={description ?? getTranslation(product?.description)}
                            onChange={onDescriptionChange}
                        />
                    </Suspense>
                    <ReactiveHiddenInput name="description" value={description} />
                    <FieldError>{errors.description}</FieldError>
                </Field>

                <Field>
                    <FieldLabel htmlFor="category">{__('Category')}</FieldLabel>
                    <ResourcePickerTrigger
                        id="category"
                        name="category_id"
                        value={category?.id}
                        label={getTranslation(category?.name)}
                        placeholder={__('Select a category')}
                        onOpen={() => setCategoryPickerOpen(true)}
                        onRemove={() => setCategory(null)}
                    />
                    <CategoryPicker
                        open={categoryPickerOpen}
                        onOpenChange={setCategoryPickerOpen}
                        selectedItems={category ? [category] : []}
                        onSelectionChange={(item) => setCategory(item)}
                    />
                    <FieldError>{errors.category_id}</FieldError>
                </Field>

                <Field>
                    <FieldLabel htmlFor="brand">{__('Brand')}</FieldLabel>
                    <ResourcePickerTrigger
                        id="brand"
                        name="brand_id"
                        value={brand?.id}
                        label={getTranslation(brand?.name)}
                        placeholder={__('Select a brand')}
                        onOpen={() => setBrandPickerOpen(true)}
                        onRemove={() => setBrand(null)}
                    />
                    <BrandPicker
                        open={brandPickerOpen}
                        onOpenChange={setBrandPickerOpen}
                        selectedItems={brand ? [brand] : []}
                        onSelectionChange={(item) => setBrand(item)}
                    />
                    <FieldError>{errors.brand_id}</FieldError>
                </Field>
            </CardContent>
        </Card>
    );
}
