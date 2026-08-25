import { useState } from 'react';

import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { __ } from '@/lib/i18n';
import { type Product, type TaxCategoryOption } from '@/types';

interface ProductTaxProps {
    product?: Product;
    errors: Record<string, string>;
    taxCategories: TaxCategoryOption[];
}

export function ProductTax({ product, errors, taxCategories }: ProductTaxProps) {
    const [isTaxExempt, setIsTaxExempt] = useState(product?.is_tax_exempt ?? false);

    return (
        <Card>
            <CardHeader>
                <CardTitle>{__('Tax settings')}</CardTitle>
                <CardDescription>{__('Tax category and exemption settings')}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                <Field orientation="horizontal">
                    <Checkbox
                        id="is-tax-exempt"
                        name="is_tax_exempt"
                        defaultChecked={isTaxExempt}
                        onCheckedChange={(checked: boolean) => setIsTaxExempt(checked)}
                    />
                    <FieldLabel htmlFor="is-tax-exempt">{__('Tax exempt')}</FieldLabel>
                </Field>

                {!isTaxExempt && (
                    <Field>
                        <FieldLabel htmlFor="tax-category">{__('Tax category')}</FieldLabel>
                        <AdaptiveSelect
                            id="tax-category"
                            name="tax_category"
                            defaultValue={product?.tax_category ?? ''}
                            placeholder={__('Select a tax category')}
                            options={taxCategories}
                        />
                        <FieldError>{errors.tax_category}</FieldError>
                    </Field>
                )}
            </CardContent>
        </Card>
    );
}
