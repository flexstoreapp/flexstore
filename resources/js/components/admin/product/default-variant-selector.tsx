import { useCallback, useMemo } from 'react';

import { InfoPopover } from '@/components/admin/info-popover';
import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { __ } from '@/lib/i18n';
import { stringifyVariantName } from '@/lib/product-form-utils';
import { cn } from '@/lib/utils';
import type { ProductVariant } from '@/types';

interface DefaultVariantSelectorProps {
    variants: ProductVariant[];
    errors: Record<string, string>;
    onDefaultVariantChange: (variantId: string | null) => void;
}

export function DefaultVariantSelector({ variants, errors, onDefaultVariantChange }: DefaultVariantSelectorProps) {
    const defaultVariantIndex = useMemo(() => variants.findIndex((variant) => variant.is_default), [variants]);

    const defaultVariantId = defaultVariantIndex === -1 ? '' : variants[defaultVariantIndex].id;

    const handleRemove = useCallback(() => onDefaultVariantChange(null), [onDefaultVariantChange]);

    const variantOptions = variants.map((variant) => ({
        value: variant.id,
        label: stringifyVariantName(variant),
    }));

    return (
        <Field className="max-w-xs">
            <div className="flex items-center gap-2">
                <FieldLabel htmlFor="default-variant">{__('Default variant')}</FieldLabel>
                <InfoPopover
                    className="w-72"
                    text={__('Pre-selected on the storefront. Without one, the product shows a price range.')}
                />
            </div>
            <div className="flex">
                <AdaptiveSelect
                    id="default-variant"
                    options={variantOptions}
                    value={defaultVariantId}
                    onValueChange={onDefaultVariantChange}
                    placeholder={__('No default variant')}
                    className={cn('flex-1', defaultVariantId && 'rounded-e-none')}
                />
                {defaultVariantId && (
                    <Button
                        type="button"
                        variant="outline"
                        size="md"
                        className="rounded-s-none border-s-0"
                        onClick={handleRemove}
                    >
                        {__('Remove')}
                    </Button>
                )}
            </div>
            <FieldError>{errors[`variants.${defaultVariantIndex}.is_default`]}</FieldError>
        </Field>
    );
}
