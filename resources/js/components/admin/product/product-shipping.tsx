import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { InputGroup } from '@/components/ui/input-group';
import { DIMENSION_UNIT_OPTIONS, WEIGHT_UNIT_OPTIONS } from '@/lib/constants';
import { __ } from '@/lib/i18n';
import { type Product } from '@/types';

interface ProductShippingProps {
    product?: Product;
    errors: Record<string, string>;
    hasVariants?: boolean;
}

export function ProductShipping({ product, errors, hasVariants = false }: ProductShippingProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{__('Shipping')}</CardTitle>
                <CardDescription>{__('Shipping and customs details')}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {!hasVariants && (
                    <>
                        <Field>
                            <FieldLabel htmlFor="weight">{__('Weight')}</FieldLabel>
                            <InputGroup.Root>
                                <InputGroup.Control
                                    id="weight"
                                    name="weight"
                                    type="number"
                                    inputMode="decimal"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                    defaultValue={String(product?.weight ?? '')}
                                />
                                <InputGroup.Select
                                    name="weight_unit"
                                    defaultValue={product?.weight_unit ?? 'kg'}
                                    options={WEIGHT_UNIT_OPTIONS}
                                />
                            </InputGroup.Root>
                            <FieldError>{errors.weight}</FieldError>
                        </Field>

                        <Field>
                            <FieldLabel htmlFor="length">{__('Dimensions')}</FieldLabel>
                            <div className="grid grid-cols-2 gap-3">
                                <InputGroup.Root>
                                    <InputGroup.Prefix>{__('L')}</InputGroup.Prefix>
                                    <InputGroup.Control
                                        id="length"
                                        name="length"
                                        type="number"
                                        inputMode="decimal"
                                        min="0"
                                        step="0.01"
                                        placeholder="0"
                                        defaultValue={String(product?.length ?? '')}
                                    />
                                </InputGroup.Root>
                                <InputGroup.Root>
                                    <InputGroup.Prefix>{__('W')}</InputGroup.Prefix>
                                    <InputGroup.Control
                                        name="width"
                                        type="number"
                                        inputMode="decimal"
                                        min="0"
                                        step="0.01"
                                        placeholder="0"
                                        defaultValue={String(product?.width ?? '')}
                                    />
                                </InputGroup.Root>
                                <InputGroup.Root>
                                    <InputGroup.Prefix>{__('H')}</InputGroup.Prefix>
                                    <InputGroup.Control
                                        name="height"
                                        type="number"
                                        inputMode="decimal"
                                        min="0"
                                        step="0.01"
                                        placeholder="0"
                                        defaultValue={String(product?.height ?? '')}
                                    />
                                </InputGroup.Root>
                                <AdaptiveSelect
                                    name="dimension_unit"
                                    defaultValue={product?.dimension_unit ?? 'cm'}
                                    options={DIMENSION_UNIT_OPTIONS}
                                />
                            </div>
                            <FieldError>
                                {errors.length ?? errors.width ?? errors.height ?? errors.dimension_unit}
                            </FieldError>
                        </Field>
                    </>
                )}
            </CardContent>
        </Card>
    );
}
