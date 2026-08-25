import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import * as MenuItemController from '@/actions/App/Http/Controllers/Admin/MenuItemController';
import { BrandPicker, type SelectableBrand } from '@/components/admin/brand-picker';
import { CategoryPicker, type SelectableItem } from '@/components/admin/category-picker';
import { FormSubmit } from '@/components/admin/form-submit';
import { InlineImageUploader } from '@/components/admin/inline-image-uploader';
import { ResourcePickerTrigger } from '@/components/admin/resource-picker';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import { AdaptiveSelect, type AdaptiveSelectOption } from '@/components/ui/adaptive-select';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { useStorefrontBuilder } from '@/layouts/admin/storefront-builder-layout';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { LinkType, MenuItem, MenuPage } from '@/types';

interface MenuItemFormProps {
    menuItem?: MenuItem;
    location: 'header' | 'footer';
}

type PageOption = AdaptiveSelectOption & { value: MenuPage };

const PAGE_OPTIONS: PageOption[] = [
    { value: 'home', label: __('Home') },
    { value: 'products', label: __('Products') },
    { value: 'categories', label: __('Categories') },
    { value: 'brands', label: __('Brands') },
    { value: 'wishlist', label: __('Wishlist') },
    { value: 'order_tracking', label: __('Track order') },
    { value: 'refund_policy', label: __('Refund policy') },
    { value: 'privacy_policy', label: __('Privacy policy') },
    { value: 'terms_of_service', label: __('Terms of service') },
];

export function MenuItemForm({ menuItem, location }: MenuItemFormProps) {
    const [linkType, setLinkType] = useState<LinkType>(menuItem?.link_type ?? 'custom');
    const [brandPickerOpen, setBrandPickerOpen] = useState(false);
    const [selectedBrand, setSelectedBrand] = useState<SelectableBrand | null>(
        menuItem?.brand ? { id: menuItem.brand.id, name: menuItem.brand.name, image: menuItem.brand.image } : null,
    );
    const [categoryPickerOpen, setCategoryPickerOpen] = useState(false);
    const [selectedCategory, setSelectedCategory] = useState<SelectableItem | null>(
        menuItem?.category ? { id: menuItem.category.id, name: menuItem.category.name } : null,
    );
    const [selectedPage, setSelectedPage] = useState<MenuPage | null>(menuItem?.page ?? null);
    const [isMegaMenu, setIsMegaMenu] = useState(menuItem?.is_mega_menu ?? false);
    const { reloadIframe } = useStorefrontBuilder();

    const isFooter = location === 'footer';
    const isHeaderParentItem = location === 'header' && !menuItem?.parent_id;

    const handleTransform = (data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> => {
        data.is_mega_menu = data.is_mega_menu === 'on';
        data.is_active = data.is_active === 'on';
        data.location = location;

        data.brand_id = linkType === 'brand' ? (selectedBrand?.id ?? null) : null;
        data.category_id = linkType === 'category' ? (selectedCategory?.id ?? null) : null;
        data.url = linkType === 'custom' ? (data.url ?? null) : null;
        data.page = linkType === 'page' ? (selectedPage ?? null) : null;

        return data;
    };

    const buttonLabel = useMemo(() => {
        if (menuItem) {
            return isFooter ? __('Update link') : __('Update menu item');
        }
        return isFooter ? __('Add link') : __('Add menu item');
    }, [menuItem, isFooter]);

    return (
        <Form
            {...(menuItem ? MenuItemController.update.form(menuItem) : MenuItemController.store.form())}
            options={{ preserveScroll: true, only: ['menuItem', 'location'] }}
            onSuccess={() => reloadIframe()}
            transform={handleTransform}
            setDefaultsOnSuccess
        >
            {({ processing, errors, recentlySuccessful }) => (
                <div className="mb-6 space-y-6 p-4 text-sm">
                    <UnsavedChangesAlert />
                    <Field>
                        <FieldLabel htmlFor="label">{__('Label')}</FieldLabel>
                        <Input
                            id="label"
                            name="label"
                            type="text"
                            placeholder={isFooter ? __('e.g., Privacy policy') : __('e.g., Shop')}
                            defaultValue={getTranslation(menuItem?.label)}
                            required
                        />
                        <FieldError>{errors.label}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel>{__('Link type')}</FieldLabel>
                        <RadioGroup
                            name="link_type"
                            defaultValue={linkType}
                            onValueChange={(value) => setLinkType(value as LinkType)}
                            className="flex flex-wrap gap-4"
                        >
                            <div className="flex items-center gap-x-2">
                                <RadioGroupItem value="custom" id="link-type-custom" />
                                <FieldLabel htmlFor="link-type-custom" className="font-normal">
                                    {__('Custom URL')}
                                </FieldLabel>
                            </div>
                            <div className="flex items-center gap-x-2">
                                <RadioGroupItem value="brand" id="link-type-brand" />
                                <FieldLabel htmlFor="link-type-brand" className="font-normal">
                                    {__('Brand')}
                                </FieldLabel>
                            </div>
                            <div className="flex items-center gap-x-2">
                                <RadioGroupItem value="category" id="link-type-category" />
                                <FieldLabel htmlFor="link-type-category" className="font-normal">
                                    {__('Category')}
                                </FieldLabel>
                            </div>
                            <div className="flex items-center gap-x-2">
                                <RadioGroupItem value="page" id="link-type-page" />
                                <FieldLabel htmlFor="link-type-page" className="font-normal">
                                    {__('Page')}
                                </FieldLabel>
                            </div>
                        </RadioGroup>
                        <FieldError>{errors.link_type}</FieldError>
                    </Field>

                    {linkType === 'brand' && (
                        <Field>
                            <FieldLabel htmlFor="brand_id">{__('Brand')}</FieldLabel>
                            <ResourcePickerTrigger
                                id="brand_id"
                                name="brand_id"
                                value={selectedBrand?.id}
                                label={getTranslation(selectedBrand?.name)}
                                placeholder={__('Select a brand')}
                                onOpen={() => setBrandPickerOpen(true)}
                                onRemove={() => setSelectedBrand(null)}
                            />
                            <BrandPicker
                                open={brandPickerOpen}
                                onOpenChange={setBrandPickerOpen}
                                selectedItems={selectedBrand ? [selectedBrand] : []}
                                onSelectionChange={(item) => setSelectedBrand(item)}
                            />
                            <FieldError>{errors.brand_id}</FieldError>
                        </Field>
                    )}

                    {linkType === 'category' && (
                        <Field>
                            <FieldLabel htmlFor="category_id">{__('Category')}</FieldLabel>
                            <ResourcePickerTrigger
                                id="category_id"
                                name="category_id"
                                value={selectedCategory?.id}
                                label={getTranslation(selectedCategory?.name)}
                                placeholder={__('Select a category')}
                                onOpen={() => setCategoryPickerOpen(true)}
                                onRemove={() => setSelectedCategory(null)}
                            />
                            <CategoryPicker
                                open={categoryPickerOpen}
                                onOpenChange={setCategoryPickerOpen}
                                selectedItems={selectedCategory ? [selectedCategory] : []}
                                onSelectionChange={(item) => setSelectedCategory(item)}
                            />
                            <FieldError>{errors.category_id}</FieldError>
                        </Field>
                    )}

                    {linkType === 'custom' && (
                        <Field>
                            <FieldLabel htmlFor="url">{__('URL')}</FieldLabel>
                            <Input
                                id="url"
                                name="url"
                                type="text"
                                placeholder="/about"
                                defaultValue={menuItem?.url ?? ''}
                                required
                            />
                            <FieldError>{errors.url}</FieldError>
                        </Field>
                    )}

                    {linkType === 'page' && (
                        <Field>
                            <FieldLabel htmlFor="page">{__('Page')}</FieldLabel>
                            <AdaptiveSelect
                                id="page"
                                name="page"
                                value={selectedPage ?? ''}
                                onValueChange={(value) => setSelectedPage(value as MenuPage)}
                                options={PAGE_OPTIONS.map((option) => ({
                                    value: option.value,
                                    label: option.label,
                                }))}
                                placeholder={__('Select a page')}
                            />
                            <FieldError>{errors.page}</FieldError>
                        </Field>
                    )}

                    <Field>
                        <FieldLabel htmlFor="target">{__('Open in')}</FieldLabel>
                        <AdaptiveSelect
                            id="target"
                            name="target"
                            defaultValue={menuItem?.target ?? '_self'}
                            options={[
                                { value: '_self', label: __('Same window') },
                                { value: '_blank', label: __('New tab') },
                            ]}
                        />
                        <FieldError>{errors.target}</FieldError>
                    </Field>

                    {isHeaderParentItem && (
                        <>
                            <Field orientation="horizontal">
                                <Checkbox
                                    id="is_mega_menu"
                                    name="is_mega_menu"
                                    checked={isMegaMenu}
                                    onCheckedChange={(checked) => setIsMegaMenu(checked === true)}
                                />
                                <FieldLabel htmlFor="is_mega_menu">{__('Display as mega menu')}</FieldLabel>
                            </Field>

                            {isMegaMenu && (
                                <>
                                    <Field>
                                        <FieldLabel htmlFor="featured_image_id">{__('Featured image')}</FieldLabel>
                                        <InlineImageUploader
                                            id="featured_image_id"
                                            name="featured_image_id"
                                            defaultValue={menuItem?.featured_image ?? null}
                                            size="lg"
                                            aspectRatio="square"
                                            error={errors.featured_image_id}
                                        />
                                    </Field>

                                    <Field>
                                        <FieldLabel htmlFor="featured_title">{__('Featured title')}</FieldLabel>
                                        <Input
                                            id="featured_title"
                                            name="featured_title"
                                            type="text"
                                            placeholder={__('e.g., New arrivals just dropped')}
                                            defaultValue={getTranslation(menuItem?.featured_title)}
                                        />
                                        <FieldError>{errors.featured_title}</FieldError>
                                    </Field>

                                    <Field>
                                        <FieldLabel htmlFor="featured_url">{__('Featured URL')}</FieldLabel>
                                        <Input
                                            id="featured_url"
                                            name="featured_url"
                                            type="text"
                                            placeholder="/shop"
                                            defaultValue={menuItem?.featured_url ?? ''}
                                        />
                                        <FieldError>{errors.featured_url}</FieldError>
                                    </Field>
                                </>
                            )}
                        </>
                    )}

                    <Field orientation="horizontal">
                        <Checkbox id="is_active" name="is_active" defaultChecked={menuItem?.is_active ?? true} />
                        <FieldLabel htmlFor="is_active">{__('Show on storefront')}</FieldLabel>
                    </Field>

                    <FormSubmit processing={processing} recentlySuccessful={recentlySuccessful}>
                        {buttonLabel}
                    </FormSubmit>
                </div>
            )}
        </Form>
    );
}
