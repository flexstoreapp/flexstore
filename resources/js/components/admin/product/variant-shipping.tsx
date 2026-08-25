import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { InputGroup } from '@/components/ui/input-group';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { DIMENSION_UNIT_OPTIONS, WEIGHT_UNIT_OPTIONS } from '@/lib/constants';
import { __ } from '@/lib/i18n';
import { stringifyVariantName } from '@/lib/product-form-utils';
import { type ProductVariant } from '@/types';

interface VariantShippingProps {
    variants: ProductVariant[];
    errors: Record<string, string>;
    onVariantUpdate: (variantId: string, updates: Partial<ProductVariant>) => void;
    onAllVariantsUpdate: (updates: Partial<ProductVariant>) => void;
}

export function VariantShipping({ variants, onVariantUpdate, onAllVariantsUpdate, errors }: VariantShippingProps) {
    return (
        <div className="space-y-6">
            <div className="flex flex-wrap justify-end gap-4">
                <Field className="w-35 gap-2">
                    <FieldLabel htmlFor="all-weight" className="text-xs">
                        {__('Weight')}
                    </FieldLabel>
                    <InputGroup.Root className="h-8">
                        <InputGroup.Control
                            id="all-weight"
                            className="text-xs"
                            type="number"
                            inputMode="decimal"
                            min="0"
                            step="0.01"
                            placeholder="0.00"
                            onChange={(e) => onAllVariantsUpdate({ weight: e.target.value })}
                        />
                        <InputGroup.Select
                            defaultValue="kg"
                            options={WEIGHT_UNIT_OPTIONS}
                            onValueChange={(value) => onAllVariantsUpdate({ weight_unit: value })}
                        />
                    </InputGroup.Root>
                </Field>

                <Field className="w-auto gap-2">
                    <FieldLabel className="text-xs">{__('Dimensions')}</FieldLabel>
                    <div className="flex gap-2">
                        <InputGroup.Root className="h-8 w-24">
                            <InputGroup.Prefix>{__('L')}</InputGroup.Prefix>
                            <InputGroup.Control
                                className="text-xs"
                                type="number"
                                inputMode="decimal"
                                min="0"
                                step="0.01"
                                placeholder="0"
                                onChange={(e) => onAllVariantsUpdate({ length: e.target.value })}
                            />
                        </InputGroup.Root>
                        <InputGroup.Root className="h-8 w-24">
                            <InputGroup.Prefix>{__('W')}</InputGroup.Prefix>
                            <InputGroup.Control
                                className="text-xs"
                                type="number"
                                inputMode="decimal"
                                min="0"
                                step="0.01"
                                placeholder="0"
                                onChange={(e) => onAllVariantsUpdate({ width: e.target.value })}
                            />
                        </InputGroup.Root>
                        <InputGroup.Root className="h-8 w-24">
                            <InputGroup.Prefix>{__('H')}</InputGroup.Prefix>
                            <InputGroup.Control
                                className="text-xs"
                                type="number"
                                inputMode="decimal"
                                min="0"
                                step="0.01"
                                placeholder="0"
                                onChange={(e) => onAllVariantsUpdate({ height: e.target.value })}
                            />
                        </InputGroup.Root>
                        <AdaptiveSelect
                            className="h-8 w-24 shrink-0"
                            defaultValue="cm"
                            options={DIMENSION_UNIT_OPTIONS}
                            onValueChange={(value) => onAllVariantsUpdate({ dimension_unit: value })}
                        />
                    </div>
                </Field>
            </div>

            <ScrollArea className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="whitespace-nowrap">{__('Variant')}</TableHead>
                            <TableHead className="whitespace-nowrap">{__('Weight')}</TableHead>
                            <TableHead className="whitespace-nowrap">{__('Dimensions')}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {variants.map((variant, index) => (
                            <TableRow key={variant.id}>
                                <TableCell className="font-medium">{stringifyVariantName(variant)}</TableCell>
                                <TableCell>
                                    <div className="space-y-1.5">
                                        <InputGroup.Root className="h-8 w-35">
                                            <InputGroup.Control
                                                name={`variants.${index}.weight`}
                                                type="number"
                                                inputMode="decimal"
                                                min="0"
                                                step="0.01"
                                                placeholder="0.00"
                                                value={variant.weight ?? ''}
                                                onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                                                    onVariantUpdate(variant.id, { weight: e.target.value })
                                                }
                                            />
                                            <InputGroup.Select
                                                name={`variants.${index}.weight_unit`}
                                                value={variant.weight_unit ?? 'kg'}
                                                options={WEIGHT_UNIT_OPTIONS}
                                                onValueChange={(value: string) =>
                                                    onVariantUpdate(variant.id, { weight_unit: value })
                                                }
                                            />
                                        </InputGroup.Root>
                                        <FieldError className="text-xs">
                                            {errors[`variants.${index}.weight`]}
                                        </FieldError>
                                        <FieldError className="text-xs">
                                            {errors[`variants.${index}.weight_unit`]}
                                        </FieldError>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div className="space-y-1.5">
                                        <div className="flex w-fit gap-2">
                                            <InputGroup.Root className="h-8 w-24">
                                                <InputGroup.Prefix>{__('L')}</InputGroup.Prefix>
                                                <InputGroup.Control
                                                    name={`variants.${index}.length`}
                                                    type="number"
                                                    inputMode="decimal"
                                                    min="0"
                                                    step="0.01"
                                                    placeholder="0"
                                                    value={variant.length ?? ''}
                                                    onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                                                        onVariantUpdate(variant.id, { length: e.target.value })
                                                    }
                                                />
                                            </InputGroup.Root>
                                            <InputGroup.Root className="h-8 w-24">
                                                <InputGroup.Prefix>{__('W')}</InputGroup.Prefix>
                                                <InputGroup.Control
                                                    name={`variants.${index}.width`}
                                                    type="number"
                                                    inputMode="decimal"
                                                    min="0"
                                                    step="0.01"
                                                    placeholder="0"
                                                    value={variant.width ?? ''}
                                                    onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                                                        onVariantUpdate(variant.id, { width: e.target.value })
                                                    }
                                                />
                                            </InputGroup.Root>
                                            <InputGroup.Root className="h-8 w-24">
                                                <InputGroup.Prefix>{__('H')}</InputGroup.Prefix>
                                                <InputGroup.Control
                                                    name={`variants.${index}.height`}
                                                    type="number"
                                                    inputMode="decimal"
                                                    min="0"
                                                    step="0.01"
                                                    placeholder="0"
                                                    value={variant.height ?? ''}
                                                    onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                                                        onVariantUpdate(variant.id, { height: e.target.value })
                                                    }
                                                />
                                            </InputGroup.Root>
                                            <AdaptiveSelect
                                                className="h-8 w-24 shrink-0"
                                                name={`variants.${index}.dimension_unit`}
                                                value={variant.dimension_unit ?? 'cm'}
                                                options={DIMENSION_UNIT_OPTIONS}
                                                onValueChange={(value: string) =>
                                                    onVariantUpdate(variant.id, { dimension_unit: value })
                                                }
                                            />
                                        </div>
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>

                <ScrollBar orientation="horizontal" />
            </ScrollArea>
        </div>
    );
}
