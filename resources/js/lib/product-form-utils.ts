import { type FormDataConvertible } from '@inertiajs/core';

import { divide, multiply, subtract } from '@/lib/decimal';
import { uuidv7 } from '@/lib/uuid';
import type { Product, ProductOption, ProductVariant, ProductVariantOption } from '@/types';

import { getTranslation, stripTrailingZeros } from './utils';

export function stringifyVariantName(variant: ProductVariant): string {
    return variant.options?.map((option) => getTranslation(option.value)).join(' / ') ?? '';
}

export function calculateProfit(price: string, cost: string): string {
    return subtract(price, cost);
}

export function calculateProfitMargin(price: string, cost: string): string {
    const profit = subtract(price, cost);
    return stripTrailingZeros(multiply(divide(profit, price, 4), 100, 2));
}

export function generateVariants(
    options: ProductOption[],
    existingVariants: ProductVariant[],
    product?: Product,
): ProductVariant[] {
    const preparedOptions = options.filter((opt) => getTranslation(opt.name).trim() !== '' && opt.values.length > 0);

    if (preparedOptions.length === 0) return [];

    const combinations = generateCombinations(preparedOptions);
    if (combinations.length === 0) return [];

    const normalize = (id: unknown): string => String(id ?? '').trim();
    const createKey = (opts: { option_id: string; value_id: string }[]): string =>
        opts
            .map((o) => `${normalize(o.option_id)}:${normalize(o.value_id)}`)
            .sort()
            .join('|');

    const currentOptionIds = new Set(preparedOptions.map((o) => normalize(o.id)));
    const existingOptionIds = new Set(existingVariants[0]?.options?.map((o) => normalize(o.option_id)) ?? []);

    const collectIds = (variants: ProductVariant[], getter: (opt: ProductVariantOption) => string): Set<string> => {
        const ids = new Set<string>();
        for (const v of variants) {
            for (const opt of v.options ?? []) {
                ids.add(normalize(getter(opt)));
            }
        }
        return ids;
    };

    const existingValueIds = collectIds(existingVariants, (o) => o.value_id);
    const currentValueIds = new Set(preparedOptions.flatMap((o) => o.values.map((v) => normalize(v.id))));

    const removedOptionIds = new Set([...existingOptionIds].filter((id) => !currentOptionIds.has(id)));
    const removedValueIds = new Set([...existingValueIds].filter((id) => !currentValueIds.has(id)));
    const newOptionIds = new Set([...currentOptionIds].filter((id) => !existingOptionIds.has(id)));
    const firstValueIds = new Set(preparedOptions.map((opt) => normalize(opt.values[0]?.id)).filter(Boolean));

    const variantIndex = new Map<string, ProductVariant>();
    const reducedIndex = new Map<string, ProductVariant>();
    const partialIndex = new Map<string, ProductVariant>();

    const removedOptionFirstValues = new Map<string, string>();
    for (const v of existingVariants) {
        for (const opt of v.options ?? []) {
            const optId = normalize(opt.option_id);
            if (removedOptionIds.has(optId) && !removedOptionFirstValues.has(optId)) {
                removedOptionFirstValues.set(optId, normalize(opt.value_id));
            }
        }
        if (removedOptionFirstValues.size === removedOptionIds.size) break;
    }

    const usesFirstValuesOfRemovedOptions = (variant: ProductVariant): boolean =>
        (variant.options ?? [])
            .filter((o) => removedOptionIds.has(normalize(o.option_id)))
            .every((o) => removedOptionFirstValues.get(normalize(o.option_id)) === normalize(o.value_id));

    for (const variant of existingVariants) {
        const opts = variant.options;
        if (!opts?.length) continue;

        const key = createKey(opts);
        variantIndex.set(key, variant);

        if (removedOptionIds.size > 0) {
            const reduced = opts.filter((o) => !removedOptionIds.has(normalize(o.option_id)));
            if (reduced.length > 0) {
                const reducedKey = createKey(reduced);
                if (!reducedIndex.has(reducedKey) || usesFirstValuesOfRemovedOptions(variant)) {
                    reducedIndex.set(reducedKey, variant);
                }
            }
        }

        if (removedValueIds.size > 0 && opts.length > 1) {
            for (const opt of opts) {
                if (removedValueIds.has(normalize(opt.value_id))) {
                    const partial = opts.filter((o) => o !== opt);
                    const partialKey = createKey(partial);
                    if (!partialIndex.has(partialKey)) {
                        partialIndex.set(partialKey, variant);
                    }
                }
            }
        }
    }

    const locale = typeof document !== 'undefined' ? document.documentElement.lang : 'en';

    const createResult = (
        base: ProductVariant,
        combination: ProductVariantOption[],
        newId?: string,
        isDefault?: boolean,
    ): ProductVariant => ({
        ...base,
        id: newId ?? base.id,
        title: { [locale]: combination.map((opt) => getTranslation(opt.value)).join(' / ') },
        is_default: isDefault ?? base.is_default,
        options: combination,
    });

    const hasVariantData = (v: ProductVariant | undefined): boolean => {
        if (!v) return false;
        return !!(v.price || v.compare_at_price || v.cost_per_item || v.sku || v.barcode);
    };

    return combinations.map((combination) => {
        const key = createKey(combination);
        const exactMatch = variantIndex.get(key);

        if (removedValueIds.size > 0 && combination.length > 1 && !hasVariantData(exactMatch)) {
            for (const opt of combination) {
                const partial = combination.filter((o) => o !== opt);
                const match = partialIndex.get(createKey(partial));
                if (match && hasVariantData(match)) {
                    return createResult(
                        match,
                        combination,
                        exactMatch?.id ?? uuidv7(),
                        exactMatch?.is_default ?? false,
                    );
                }
            }
        }

        if (exactMatch) {
            return createResult(exactMatch, combination);
        }

        if (removedOptionIds.size > 0) {
            const match = reducedIndex.get(key);
            if (match) {
                return createResult(match, combination);
            }
        }

        if (newOptionIds.size > 0) {
            const usesFirstValues = combination
                .filter((o) => newOptionIds.has(normalize(o.option_id)))
                .every((o) => firstValueIds.has(normalize(o.value_id)));

            if (usesFirstValues) {
                const existingKey = createKey(combination.filter((o) => existingOptionIds.has(normalize(o.option_id))));
                const match = variantIndex.get(existingKey);
                if (match) {
                    return createResult(
                        match,
                        combination,
                        uuidv7(),
                        existingKey === createKey(existingVariants.find((v) => v.is_default)?.options ?? []),
                    );
                }
            }
        }

        return createVariantFromCombination(combination, product);
    });
}

function generateCombinations(options: ProductOption[]): ProductVariantOption[][] {
    const result: ProductVariantOption[][] = [];

    const generate = (current: ProductVariantOption[], index: number) => {
        if (index === options.length) {
            result.push([...current]);
            return;
        }

        for (const value of options[index].values) {
            current.push({
                option_id: options[index].id,
                value_id: value.id,
                name: options[index].name,
                value: value.value,
            });
            generate(current, index + 1);
            current.pop();
        }
    };

    generate([], 0);
    return result;
}

function createVariantFromCombination(combination: ProductVariantOption[], product?: Product): ProductVariant {
    const locale = typeof document !== 'undefined' ? document.documentElement.lang : 'en';

    return {
        id: uuidv7(),
        product_id: 0,
        title: { [locale]: combination.map((opt) => getTranslation(opt.value)).join(' / ') },
        sku: null,
        barcode: null,
        price: product?.price ?? '',
        compare_at_price: product?.compare_at_price ?? null,
        cost_per_item: product?.cost_per_item ?? null,
        track_stock: product?.track_stock ?? false,
        stock: null,
        in_stock: true,
        weight: String(product?.weight ?? ''),
        weight_unit: product?.weight_unit ?? 'kg',
        length: product?.length ?? null,
        width: product?.width ?? null,
        height: product?.height ?? null,
        dimension_unit: product?.dimension_unit ?? 'cm',
        is_default: false,
        options: combination,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
    };
}

export function handleTransform(data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> {
    const booleanFields = [
        'is_active',
        'is_tax_exempt',
        'track_stock',
        'in_stock',
        'add_more',
        'include_in_catalog',
        'is_adult',
    ];
    for (const field of booleanFields) {
        data[field] = data[field] === 'on';
    }

    const nullableNumberFields = ['download_limit', 'download_expiry_days'];
    for (const field of nullableNumberFields) {
        if (data[field] === '' || data[field] === undefined) {
            data[field] = null;
        }
    }

    if (!Array.isArray(data.media)) {
        data.media = [];
    }

    if (data.type === 'digital' && !Array.isArray(data.downloads)) {
        data.downloads = [];
    }

    if (Array.isArray(data.options)) {
        data.options = data.options.map((option) => ({
            ...(option as Record<string, FormDataConvertible>),
            values: (option as Record<string, FormDataConvertible>).values ?? [],
        }));
    }

    return data;
}
