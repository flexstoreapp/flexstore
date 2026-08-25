import { cloneElement, isValidElement, type ReactNode } from 'react';

import { readDomTranslations } from './load-translations';
import type { TranslationKey, TranslationParams } from '../types';

declare global {
    interface Window {
        translations: Record<TranslationKey, string>;
    }
}

type ParameterizedTranslationKey = {
    [K in TranslationKey]: TranslationParams<K> extends Record<string, never> ? never : K;
}[TranslationKey];

let translations: Record<TranslationKey, string>;

if (typeof window !== 'undefined') {
    const bundle = window.translations ?? readDomTranslations();

    if (bundle) {
        window.translations = bundle as Record<TranslationKey, string>;
        translations = bundle as Record<TranslationKey, string>;
    }
}

export function setTranslations(bundle: Record<string, string>): void {
    translations = bundle as Record<TranslationKey, string>;
}

export function __<K extends TranslationKey>(
    key: K,
    params: TranslationParams<K> = {} as TranslationParams<K>,
): string {
    const translation = getTranslation(key);

    return applyParameters(translation, params as Record<string, unknown>);
}

export function __nodes<K extends ParameterizedTranslationKey>(
    key: K,
    values: { [P in keyof TranslationParams<K>]: ReactNode },
): ReactNode[] {
    const translation = getTranslation(key);
    const names = Object.keys(values as Record<string, ReactNode>).sort((a, b) => b.length - a.length);

    if (names.length === 0) {
        return [translation];
    }

    const nodes: ReactNode[] = [];
    let index = 0;

    for (const match of translation.matchAll(new RegExp(`:(${names.join('|')})`, 'g'))) {
        if (match.index > index) {
            nodes.push(translation.slice(index, match.index));
        }

        const value = (values as Record<string, ReactNode>)[match[1]];

        nodes.push(isValidElement(value) ? cloneElement(value, { key: nodes.length }) : value);
        index = match.index + match[0].length;
    }

    if (index < translation.length) {
        nodes.push(translation.slice(index));
    }

    return nodes;
}

export function transChoice<K extends TranslationKey>(
    key: K,
    count: number,
    params: TranslationParams<K> = {} as TranslationParams<K>,
): string {
    const translation = getTranslation(key);
    const paramsWithCount = { count, ...(params as Record<string, unknown>) };
    const segments = translation.split('|').map((segment) => segment.trim());
    let result = '';

    // Handle simple singular/plural format (first part singular, second part plural)
    // Format: ":count product selected|:count products selected"
    if (isSimplePluralFormat(segments)) {
        result = count === 1 ? segments[0] : segments[1];

        return applyParameters(result, paramsWithCount);
    }

    // Handle complex format with explicit rules
    // Format: {0} No items|{1} One item|[2,*] :count items

    // First, try to find an exact match
    const exactMatchResult = findExactMatch(segments, count);
    if (exactMatchResult) {
        return applyParameters(exactMatchResult, paramsWithCount);
    }

    // Then, try to find a range match
    const rangeMatchResult = findRangeMatch(segments, count);
    if (rangeMatchResult) {
        return applyParameters(rangeMatchResult, paramsWithCount);
    }

    // If no condition matches, look for a segment without a condition
    result = findFallbackSegment(segments);

    return applyParameters(result, paramsWithCount);
}

function getTranslation<K extends TranslationKey>(key: K): string {
    if (typeof window !== 'undefined' && window.translations) {
        translations = window.translations;
    } else if (!translations) {
        const bundle = readDomTranslations();

        if (bundle) {
            translations = bundle as Record<TranslationKey, string>;

            if (typeof window !== 'undefined') {
                window.translations = translations;
            }
        }
    }

    return translations?.[key] || (key as string);
}

function applyParameters(text: string, params: Record<string, unknown>): string {
    let result = text;

    if (result && Object.keys(params).length > 0) {
        Object.entries(params)
            .sort(([a], [b]) => b.length - a.length)
            .forEach(([paramKey, paramValue]) => {
                result = result.replace(new RegExp(`:${paramKey}`, 'g'), String(paramValue));
            });
    }

    return result;
}

function isSimplePluralFormat(segments: string[]): boolean {
    return (
        segments.length === 2 &&
        !segments[0].match(/^\s*\{/) &&
        !segments[0].match(/^\s*\[/) &&
        !segments[1].match(/^\s*\{/) &&
        !segments[1].match(/^\s*\[/)
    );
}

function findExactMatch(segments: string[], count: number): string | null {
    for (const segment of segments) {
        const exactMatch = segment.match(/^\s*\{\s*([0-9]+)\s*\}\s*(.*)/);

        if (exactMatch && parseInt(exactMatch[1], 10) === count) {
            return exactMatch[2].trim();
        }
    }

    return null;
}

function findRangeMatch(segments: string[], count: number): string | null {
    for (const segment of segments) {
        const rangeMatch = segment.match(/^\s*\[\s*([0-9]+)\s*,\s*([0-9]+|\*)\s*\]\s*(.*)/);

        if (rangeMatch) {
            const min = parseInt(rangeMatch[1], 10);
            const max = rangeMatch[2] === '*' ? Infinity : parseInt(rangeMatch[2], 10);

            if (count >= min && count <= max) {
                return rangeMatch[3].trim();
            }
        }
    }

    return null;
}

function findFallbackSegment(segments: string[]): string {
    // Look for a segment without a condition
    const plainSegment = segments.find((segment) => {
        return !segment.match(/^\s*\{/) && !segment.match(/^\s*\[/);
    });

    if (plainSegment) {
        return plainSegment;
    }

    // Default to the first segment if no plain segment found
    const firstSegment = segments[0];

    // Try to extract the message part if it has a condition
    const exactMatch = firstSegment.match(/^\s*\{\s*[0-9]+\s*\}\s*(.*)/);
    if (exactMatch) {
        return exactMatch[1].trim();
    }

    const rangeMatch = firstSegment.match(/^\s*\[\s*[0-9]+\s*,\s*(?:[0-9]+|\*)\s*\]\s*(.*)/);
    if (rangeMatch) {
        return rangeMatch[1].trim();
    }

    return firstSegment;
}
