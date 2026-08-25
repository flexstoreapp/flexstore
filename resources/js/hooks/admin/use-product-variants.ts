import { usePage } from '@inertiajs/react';
import { useState } from 'react';

import { generateVariants } from '@/lib/product-form-utils';
import { uuidv7 } from '@/lib/uuid';
import type { Product, ProductOption, ProductVariant } from '@/types';

interface UseProductVariantsOptions {
    product?: Product;
    onVariantsChange: (variants: ProductVariant[]) => void;
}

export function useProductVariants({ product, onVariantsChange }: UseProductVariantsOptions) {
    const { activeLocale } = usePage().props;
    const [options, setOptions] = useState<ProductOption[]>(product?.options || []);
    const [variants, setVariants] = useState<ProductVariant[]>(product?.variants || []);

    const commitVariants = (nextVariants: ProductVariant[]) => {
        setVariants(nextVariants);
        onVariantsChange(nextVariants);
    };

    const addOption = () => {
        const newOption: ProductOption = {
            id: uuidv7(),
            product_id: product?.id || 0,
            name: {},
            values: [],
        };
        setOptions((prevOptions) => [...prevOptions, newOption]);
        return newOption.id;
    };

    const removeOption = (optionId: string) => {
        const updatedOptions = options.filter((option) => option.id !== optionId);
        setOptions(updatedOptions);
        commitVariants(generateVariants(updatedOptions, variants, product));
    };

    const updateOptionName = (optionId: string, name: string) => {
        setOptions((prevOptions) =>
            prevOptions.map((option) =>
                option.id === optionId ? { ...option, name: { ...option.name, [activeLocale]: name } } : option,
            ),
        );
    };

    const addOptionValue = (value: string, option: ProductOption) => {
        const newValue = {
            id: uuidv7(),
            product_option_id: option.id,
            value: { [activeLocale]: value },
        };

        const updatedOptions = options.map((opt) =>
            opt.id === option.id ? { ...opt, values: [...opt.values, newValue] } : opt,
        );
        setOptions(updatedOptions);
        commitVariants(generateVariants(updatedOptions, variants, product));
    };

    const removeOptionValue = (index: number, option: ProductOption) => {
        const updatedOptions = options.map((opt) =>
            opt.id === option.id ? { ...opt, values: opt.values.filter((_, i) => i !== index) } : opt,
        );
        setOptions(updatedOptions);
        commitVariants(generateVariants(updatedOptions, variants, product));
    };

    const setDefaultVariant = (variantId: string | null) => {
        commitVariants(
            variants.map((variant) => ({
                ...variant,
                is_default: variant.id === variantId,
            })),
        );
    };

    const updateVariant = (variantId: string, updates: Partial<ProductVariant>) => {
        commitVariants(variants.map((variant) => (variant.id === variantId ? { ...variant, ...updates } : variant)));
    };

    const updateAllVariants = (updates: Partial<ProductVariant>) => {
        commitVariants(variants.map((variant) => ({ ...variant, ...updates })));
    };

    return {
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
    };
}
