import { useState } from 'react';

import { BrandPicker, type SelectableBrand } from '@/components/admin/brand-picker';
import { CategoryPicker, type SelectableItem as CategorySelectableItem } from '@/components/admin/category-picker';
import { CurrencyPicker, type SelectableItem as CurrencySelectableItem } from '@/components/admin/currency-picker';
import { FormDirtySignal } from '@/components/admin/form-dirty-signal';
import { ProductPicker, type SelectableItem as ProductSelectableItem } from '@/components/admin/product-picker';
import { RegionPicker, type SelectableItem as RegionSelectableItem } from '@/components/admin/region-picker';
import { ResourcePickerField } from '@/components/admin/resource-picker';
import { Field, FieldDescription, FieldError, FieldLabel } from '@/components/ui/field';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';

interface RestrictionsTabContentProps {
    excludedProducts?: ProductSelectableItem[];
    excludedCategories?: CategorySelectableItem[];
    excludedBrands?: SelectableBrand[];
    allowedRegions?: RegionSelectableItem[];
    supportedCurrencies?: CurrencySelectableItem[];
    errors: Record<string, string>;
    showExcludedProducts?: boolean;
    showExcludedCategories?: boolean;
    showExcludedBrands?: boolean;
    showAllowedRegions?: boolean;
    showSupportedCurrencies?: boolean;
}

export function RestrictionsTabContent({
    excludedProducts = [],
    excludedCategories = [],
    excludedBrands = [],
    allowedRegions = [],
    supportedCurrencies = [],
    errors,
    showExcludedProducts = true,
    showExcludedCategories = true,
    showExcludedBrands = true,
    showAllowedRegions = true,
    showSupportedCurrencies = false,
}: RestrictionsTabContentProps) {
    const [selectedProducts, setSelectedProducts] = useState<ProductSelectableItem[]>(excludedProducts);
    const [selectedCategories, setSelectedCategories] = useState<CategorySelectableItem[]>(excludedCategories);
    const [selectedBrands, setSelectedBrands] = useState<SelectableBrand[]>(excludedBrands);
    const [selectedRegions, setSelectedRegions] = useState<RegionSelectableItem[]>(allowedRegions);
    const [selectedCurrencies, setSelectedCurrencies] = useState<CurrencySelectableItem[]>(supportedCurrencies);
    const [productPickerOpen, setProductPickerOpen] = useState(false);
    const [categoryPickerOpen, setCategoryPickerOpen] = useState(false);
    const [brandPickerOpen, setBrandPickerOpen] = useState(false);
    const [regionPickerOpen, setRegionPickerOpen] = useState(false);
    const [currencyPickerOpen, setCurrencyPickerOpen] = useState(false);

    const removeProduct = (product: ProductSelectableItem) => {
        setSelectedProducts((prev) => prev.filter((p) => p.id !== product.id));
    };

    const removeCategory = (category: CategorySelectableItem) => {
        setSelectedCategories((prev) => prev.filter((c) => c.id !== category.id));
    };

    const removeBrand = (brand: SelectableBrand) => {
        setSelectedBrands((prev) => prev.filter((b) => b.id !== brand.id));
    };

    const removeRegion = (region: RegionSelectableItem) => {
        setSelectedRegions((prev) => prev.filter((r) => r.id !== region.id));
    };

    const removeCurrency = (currency: CurrencySelectableItem) => {
        setSelectedCurrencies((prev) => prev.filter((c) => c.code !== currency.code));
    };

    return (
        <div className="mt-4 space-y-6">
            {showExcludedProducts && (
                <Field>
                    <FieldLabel htmlFor="excluded-products">{__('Excluded products')}</FieldLabel>
                    <FormDirtySignal signal={selectedProducts.map((p) => p.id).join(',')} />

                    <ResourcePickerField>
                        <ResourcePickerField.Browse onBrowse={() => setProductPickerOpen(true)}>
                            {__('Browse products')}
                        </ResourcePickerField.Browse>
                        <ResourcePickerField.Tags>
                            {selectedProducts.map((product) => (
                                <ResourcePickerField.Tag
                                    key={product.id}
                                    name="excluded_products[]"
                                    value={product.id}
                                    onRemove={() => removeProduct(product)}
                                >
                                    {getTranslation(product.title)}
                                </ResourcePickerField.Tag>
                            ))}
                        </ResourcePickerField.Tags>
                    </ResourcePickerField>

                    <ProductPicker
                        open={productPickerOpen}
                        onOpenChange={setProductPickerOpen}
                        selectedItems={selectedProducts}
                        onSelectionChange={setSelectedProducts}
                        multiple
                    />
                    <FieldError>{errors.excluded_products}</FieldError>
                    <FieldError>{errors['excluded_products.0']}</FieldError>
                </Field>
            )}

            {showExcludedCategories && (
                <Field>
                    <FieldLabel htmlFor="excluded-categories">{__('Excluded categories')}</FieldLabel>
                    <FormDirtySignal signal={selectedCategories.map((c) => c.id).join(',')} />

                    <ResourcePickerField>
                        <ResourcePickerField.Browse onBrowse={() => setCategoryPickerOpen(true)}>
                            {__('Browse categories')}
                        </ResourcePickerField.Browse>
                        <ResourcePickerField.Tags>
                            {selectedCategories.map((category) => (
                                <ResourcePickerField.Tag
                                    key={category.id}
                                    name="excluded_categories[]"
                                    value={category.id}
                                    onRemove={() => removeCategory(category)}
                                >
                                    {getTranslation(category.name)}
                                </ResourcePickerField.Tag>
                            ))}
                        </ResourcePickerField.Tags>
                    </ResourcePickerField>

                    <CategoryPicker
                        open={categoryPickerOpen}
                        onOpenChange={setCategoryPickerOpen}
                        selectedItems={selectedCategories}
                        onSelectionChange={setSelectedCategories}
                        multiple
                    />
                    <FieldError>{errors.excluded_categories}</FieldError>
                    <FieldError>{errors['excluded_categories.0']}</FieldError>
                </Field>
            )}

            {showExcludedBrands && (
                <Field>
                    <FieldLabel htmlFor="excluded-brands">{__('Excluded brands')}</FieldLabel>
                    <FormDirtySignal signal={selectedBrands.map((b) => b.id).join(',')} />

                    <ResourcePickerField>
                        <ResourcePickerField.Browse onBrowse={() => setBrandPickerOpen(true)}>
                            {__('Browse brands')}
                        </ResourcePickerField.Browse>
                        <ResourcePickerField.Tags>
                            {selectedBrands.map((brand) => (
                                <ResourcePickerField.Tag
                                    key={brand.id}
                                    name="excluded_brands[]"
                                    value={brand.id}
                                    onRemove={() => removeBrand(brand)}
                                >
                                    {getTranslation(brand.name)}
                                </ResourcePickerField.Tag>
                            ))}
                        </ResourcePickerField.Tags>
                    </ResourcePickerField>

                    <BrandPicker
                        open={brandPickerOpen}
                        onOpenChange={setBrandPickerOpen}
                        selectedItems={selectedBrands}
                        onSelectionChange={setSelectedBrands}
                        multiple
                    />
                    <FieldError>{errors.excluded_brands}</FieldError>
                    <FieldError>{errors['excluded_brands.0']}</FieldError>
                </Field>
            )}

            {showAllowedRegions && (
                <Field>
                    <FieldLabel htmlFor="allowed-regions">{__('Allowed regions')}</FieldLabel>
                    <FormDirtySignal signal={selectedRegions.map((r) => r.id).join(',')} />

                    <ResourcePickerField>
                        <ResourcePickerField.Browse onBrowse={() => setRegionPickerOpen(true)}>
                            {__('Browse regions')}
                        </ResourcePickerField.Browse>
                        <ResourcePickerField.Tags>
                            {selectedRegions.map((region) => (
                                <ResourcePickerField.Tag
                                    key={region.id}
                                    name="allowed_regions[]"
                                    value={region.id}
                                    onRemove={() => removeRegion(region)}
                                >
                                    {getTranslation(region.name)}
                                </ResourcePickerField.Tag>
                            ))}
                        </ResourcePickerField.Tags>
                    </ResourcePickerField>

                    <RegionPicker
                        open={regionPickerOpen}
                        onOpenChange={setRegionPickerOpen}
                        selectedItems={selectedRegions}
                        onSelectionChange={setSelectedRegions}
                        multiple
                    />
                    <FieldError>{errors.allowed_regions}</FieldError>
                    <FieldError>{errors['allowed_regions.0']}</FieldError>
                </Field>
            )}

            {showSupportedCurrencies && (
                <Field>
                    <FieldLabel htmlFor="supported-currencies">{__('Supported currencies')}</FieldLabel>
                    <FormDirtySignal signal={selectedCurrencies.map((c) => c.code).join(',')} />

                    <ResourcePickerField>
                        <ResourcePickerField.Browse onBrowse={() => setCurrencyPickerOpen(true)}>
                            {__('Browse currencies')}
                        </ResourcePickerField.Browse>
                        <ResourcePickerField.Tags>
                            {selectedCurrencies.map((currency) => (
                                <ResourcePickerField.Tag
                                    key={currency.code}
                                    name="supported_currencies[]"
                                    value={currency.code}
                                    onRemove={() => removeCurrency(currency)}
                                >
                                    {currency.code}
                                </ResourcePickerField.Tag>
                            ))}
                        </ResourcePickerField.Tags>
                    </ResourcePickerField>

                    <FieldDescription>{__('Leave empty to accept all currencies.')}</FieldDescription>

                    <CurrencyPicker
                        open={currencyPickerOpen}
                        onOpenChange={setCurrencyPickerOpen}
                        selectedItems={selectedCurrencies}
                        onSelectionChange={setSelectedCurrencies}
                        multiple
                    />
                    <FieldError>{errors.supported_currencies}</FieldError>
                    <FieldError>{errors['supported_currencies.0']}</FieldError>
                </Field>
            )}
        </div>
    );
}
