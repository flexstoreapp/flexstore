import { BoxesIcon, PlusIcon } from 'lucide-react';
import React from 'react';

import { FormDirtySignal } from '@/components/admin/form-dirty-signal';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { useProductVariants } from '@/hooks/admin/use-product-variants';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { Product, ProductVariant } from '@/types';

import { DefaultVariantSelector } from './default-variant-selector';
import { VariantOptions } from './variant-options';
import { VariantTabs } from './variant-tabs';

interface ProductVariantsProps {
    product?: Product;
    errors: Record<string, string>;
    isDigital?: boolean;
    onVariantsChange: (variants: ProductVariant[]) => void;
}

export function ProductVariants({ product, errors, isDigital = false, onVariantsChange }: ProductVariantsProps) {
    const {
        options,
        variants,
        addOption,
        removeOption,
        updateOptionName,
        addOptionValue,
        removeOptionValue,
        setDefaultVariant,
        updateVariant,
        updateAllVariants,
    } = useProductVariants({ product, onVariantsChange });

    return (
        <Card>
            {options.length > 0 && (
                <CardHeader>
                    <CardTitle>{__('Variants')}</CardTitle>
                    <CardDescription>{__('Add different versions of your product')}</CardDescription>
                </CardHeader>
            )}

            <CardContent className="space-y-6">
                <FormDirtySignal
                    signal={options.map((o) => `${o.id}#${(o.values ?? []).map((v) => v.id).join('-')}`).join('|')}
                />
                {variants.map((variant, index) => (
                    <React.Fragment key={variant.id}>
                        <input type="hidden" name={`variants.${index}.id`} value={variant.id} />
                        <input type="hidden" name={`variants.${index}.title`} value={getTranslation(variant.title)} />
                        <input
                            type="hidden"
                            name={`variants.${index}.is_default`}
                            value={variant.is_default ? '1' : '0'}
                        />
                        {variant.options?.map((option, optionIndex) => (
                            <React.Fragment key={`${variant.id}-${option.option_id}`}>
                                <input
                                    type="hidden"
                                    name={`variants.${index}.options.${optionIndex}.option_id`}
                                    value={option.option_id}
                                />
                                <input
                                    type="hidden"
                                    name={`variants.${index}.options.${optionIndex}.value_id`}
                                    value={option.value_id}
                                />
                            </React.Fragment>
                        ))}
                    </React.Fragment>
                ))}

                {options.length > 0 ? (
                    <>
                        <VariantOptions
                            options={options}
                            errors={errors}
                            onAddOption={addOption}
                            onRemoveOption={removeOption}
                            onUpdateOptionName={updateOptionName}
                            onAddOptionValue={addOptionValue}
                            onRemoveOptionValue={removeOptionValue}
                        />

                        {variants.length > 0 && (
                            <DefaultVariantSelector
                                variants={variants}
                                errors={errors}
                                onDefaultVariantChange={setDefaultVariant}
                            />
                        )}

                        {variants.length > 0 && (
                            <VariantTabs
                                variants={variants}
                                errors={errors}
                                isDigital={isDigital}
                                onVariantUpdate={updateVariant}
                                onAllVariantsUpdate={updateAllVariants}
                            />
                        )}
                    </>
                ) : (
                    <Empty>
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <BoxesIcon />
                            </EmptyMedia>
                            <EmptyTitle>{__('No variants')}</EmptyTitle>
                            <EmptyDescription>
                                {__('Add options like size or color to create variants.')}
                            </EmptyDescription>
                        </EmptyHeader>
                        <EmptyContent>
                            <Button type="button" variant="outline" onClick={addOption}>
                                <PlusIcon />
                                {__('Add option')}
                            </Button>
                        </EmptyContent>
                    </Empty>
                )}
            </CardContent>
        </Card>
    );
}
