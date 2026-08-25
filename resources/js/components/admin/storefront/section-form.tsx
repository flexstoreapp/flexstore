import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

import * as StorefrontSectionController from '@/actions/App/Http/Controllers/Admin/StorefrontSectionController';
import { FormSubmit } from '@/components/admin/form-submit';
import { BrandStripFields } from '@/components/admin/storefront/brand-strip-fields';
import { CategoryGridFields } from '@/components/admin/storefront/category-grid-fields';
import { FeaturedProductsFields } from '@/components/admin/storefront/featured-products-fields';
import { HeroSliderFields } from '@/components/admin/storefront/hero-slider-fields';
import { InfoStripFields } from '@/components/admin/storefront/info-strip-fields';
import { ProductCarouselFields } from '@/components/admin/storefront/product-carousel-fields';
import { ProductColumnsFields } from '@/components/admin/storefront/product-columns-fields';
import { ProductTabsFields } from '@/components/admin/storefront/product-tabs-fields';
import { PromoBannersFields } from '@/components/admin/storefront/promo-banners-fields';
import { TestimonialsFields } from '@/components/admin/storefront/testimonials-fields';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { useStorefrontBuilder } from '@/layouts/admin/storefront-builder-layout';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type {
    BrandStripSettings,
    CategoryGridSettings,
    FeaturedProductsSettings,
    HeroSliderSettings,
    InfoStripSettings,
    ProductCarouselSettings,
    ProductColumnsSettings,
    ProductTabsSettings,
    PromoBannersSettings,
    StorefrontSection,
    StorefrontSectionType,
    TestimonialsSettings,
} from '@/types';

const placeholders: Record<StorefrontSectionType, string> = {
    hero_slider: __('e.g., Welcome'),
    info_strip: __('e.g., Why shop with us'),
    category_grid: __('e.g., Shop by category'),
    featured_products: __('e.g., Featured products'),
    product_tabs: __('e.g., Best sellers'),
    promo_banners: __('e.g., Promotions'),
    product_carousel: __('e.g., New arrivals'),
    product_columns: __('e.g., Explore'),
    brand_strip: __('e.g., Shop by brand'),
    testimonials: __('e.g., What our customers say'),
};

const checkboxFieldsByType: Partial<Record<StorefrontSectionType, string[]>> = {
    hero_slider: ['autoplay', 'show_dots'],
    brand_strip: ['grayscale'],
};

interface SectionFormProps {
    section?: StorefrontSection;
    sectionType: StorefrontSectionType;
}

export function SectionForm({ section, sectionType }: SectionFormProps) {
    const { reloadIframe } = useStorefrontBuilder();
    const settings = section?.settings;
    const formRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        formRef.current?.scrollIntoView({ behavior: 'instant', block: 'start' });
    }, []);

    const handleTransform = (data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> => {
        data.is_active = data.is_active === 'on';

        if (typeof data.settings === 'object' && data.settings !== null && !Array.isArray(data.settings)) {
            const transformedSettings = { ...(data.settings as Record<string, FormDataConvertible>) };
            const type = (data.type as string) || section?.type;
            const relevantCheckboxFields = type ? (checkboxFieldsByType[type as StorefrontSectionType] ?? []) : [];

            relevantCheckboxFields.forEach((field) => {
                transformedSettings[field] = transformedSettings[field] === 'on';
            });

            data.settings = transformedSettings;
        }

        return data;
    };

    return (
        <div ref={formRef}>
            <Form
                {...(section
                    ? StorefrontSectionController.update.form(section)
                    : StorefrontSectionController.store.form())}
                options={{ preserveScroll: true, only: ['section'] }}
                transform={handleTransform}
                setDefaultsOnSuccess
                onSuccess={() => reloadIframe()}
            >
                {({ processing, errors, recentlySuccessful }) => (
                    <div className="mb-6 space-y-6 p-4 text-sm">
                        <UnsavedChangesAlert />
                        {!section && <input type="hidden" name="type" value={sectionType} />}

                        <Field>
                            <FieldLabel htmlFor="section-title">{__('Section title')}</FieldLabel>
                            <Input
                                id="section-title"
                                name="title"
                                defaultValue={getTranslation(section?.title)}
                                placeholder={placeholders[sectionType]}
                                required
                            />
                            <FieldError>{errors.title}</FieldError>
                        </Field>

                        {sectionType === 'hero_slider' && (
                            <HeroSliderFields settings={settings as HeroSliderSettings} errors={errors} />
                        )}

                        {sectionType === 'info_strip' && (
                            <InfoStripFields settings={settings as InfoStripSettings} errors={errors} />
                        )}

                        {sectionType === 'category_grid' && (
                            <CategoryGridFields settings={settings as CategoryGridSettings} errors={errors} />
                        )}

                        {sectionType === 'featured_products' && (
                            <FeaturedProductsFields settings={settings as FeaturedProductsSettings} errors={errors} />
                        )}

                        {sectionType === 'product_tabs' && (
                            <ProductTabsFields settings={settings as ProductTabsSettings} errors={errors} />
                        )}

                        {sectionType === 'promo_banners' && (
                            <PromoBannersFields settings={settings as PromoBannersSettings} errors={errors} />
                        )}

                        {sectionType === 'product_carousel' && (
                            <ProductCarouselFields settings={settings as ProductCarouselSettings} errors={errors} />
                        )}

                        {sectionType === 'product_columns' && (
                            <ProductColumnsFields settings={settings as ProductColumnsSettings} errors={errors} />
                        )}

                        {sectionType === 'brand_strip' && (
                            <BrandStripFields settings={settings as BrandStripSettings} errors={errors} />
                        )}

                        {sectionType === 'testimonials' && (
                            <TestimonialsFields settings={settings as TestimonialsSettings} errors={errors} />
                        )}

                        <Field orientation="horizontal">
                            <Checkbox
                                id="section-is-active"
                                name="is_active"
                                defaultChecked={section?.is_active ?? true}
                            />
                            <FieldLabel htmlFor="section-is-active">{__('Show on storefront')}</FieldLabel>
                        </Field>

                        <FormSubmit processing={processing} recentlySuccessful={recentlySuccessful}>
                            {section ? __('Update section') : __('Add section')}
                        </FormSubmit>
                    </div>
                )}
            </Form>
        </div>
    );
}
