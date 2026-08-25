import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

import { __ } from '@/lib/i18n';
import type { TranslatableField } from '@/types';

export function cn(...inputs: ClassValue[]): string {
    return twMerge(clsx(inputs));
}

let locale: string | undefined;
let defaultLocale: string | undefined;

export function setTranslationLocale(active: string, fallback: string): void {
    locale = active;
    defaultLocale = fallback;
}

export function getTranslation(field: TranslatableField | undefined | null, fallback: string = ''): string {
    if (!field || !locale || !defaultLocale) return fallback;

    return field[locale] ?? field[defaultLocale] ?? fallback;
}

export function stripTrailingZeros(number: number | string): string {
    let numberStr = String(number).trim();

    if (numberStr.includes('.')) {
        numberStr = numberStr
            .replace(/(\.\d*?[1-9])0+(\D*$)/, '$1$2')
            .replace(/\.0+(\D*$)/, '$1')
            .replace(/\.(\D*$)/, '$1');
    }

    if (numberStr === '' || numberStr === '-0') return '0';
    return numberStr;
}

export function tabHasErrors<T extends Record<string, unknown[]>>(
    tabFields: T,
    tabName: string,
    errors: Partial<Record<string, string>>,
) {
    return tabFields[tabName].some((field) => typeof field === 'string' && field in errors) ?? false;
}

export function slugify(text: string): string {
    return text
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/_+/g, '-')
        .replace(/@/g, '-at-')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/[\s-]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

export function getTextFromHTML(htmlString: string): string {
    const tempElement = document.createElement('div');
    tempElement.innerHTML = htmlString;

    return tempElement.textContent ?? '';
}

export function humanFileSize(bytes: number, decimals = 2): string {
    if (!bytes || bytes <= 0) return __('0 Bytes');

    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = [__('Bytes'), __('KB'), __('MB'), __('GB'), __('TB'), __('PB')];

    const i = Math.min(Math.floor(Math.log(bytes) / Math.log(k)), sizes.length - 1);

    return `${Number.parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
}

export function cssToJs(css: string): Record<string, string> {
    if (!css || typeof css !== 'string') {
        return {};
    }

    const cssObject: Record<string, string> = {};
    const declarations = css.split(';').filter(Boolean);

    for (const declaration of declarations) {
        const parts = declaration.split(':');

        if (parts.length === 2) {
            const property = parts[0].trim();
            const value = parts[1].trim();

            if (property && value) {
                const camelCaseProp = property.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());

                cssObject[camelCaseProp] = value;
            }
        }
    }

    return cssObject;
}

const BLOCK_TAGS = new Set([
    'address',
    'article',
    'aside',
    'blockquote',
    'dd',
    'div',
    'dl',
    'dt',
    'figcaption',
    'figure',
    'footer',
    'h1',
    'h2',
    'h3',
    'h4',
    'h5',
    'h6',
    'header',
    'hr',
    'img',
    'li',
    'main',
    'nav',
    'ol',
    'p',
    'pre',
    'section',
    'table',
    'tbody',
    'td',
    'tfoot',
    'th',
    'thead',
    'tr',
    'ul',
]);

const VOID_TAGS = new Set(['br', 'hr', 'img']);

export function formatHtml(html: string): string {
    const tokens = html.match(/<[^>]+>|[^<]+/g);

    if (!tokens) {
        return '';
    }

    const lines: string[] = [];
    const openIndexes: number[] = [];
    let line = '';

    const indent = (depth: number) => '    '.repeat(depth);

    const flush = () => {
        if (line.trim()) {
            lines.push(indent(openIndexes.length) + line.trim());
        }

        line = '';
    };

    for (const token of tokens) {
        const tag = token.startsWith('<') ? token.match(/^<\/?\s*([a-z0-9-]+)/i)?.[1].toLowerCase() : undefined;

        if (!tag || !BLOCK_TAGS.has(tag)) {
            line += token;
            continue;
        }

        if (VOID_TAGS.has(tag) || token.endsWith('/>')) {
            flush();
            lines.push(indent(openIndexes.length) + token);
            continue;
        }

        if (token.startsWith('</')) {
            const openIndex = openIndexes.pop() ?? -1;

            if (openIndex === lines.length - 1) {
                lines[openIndex] += line.trim() + token;
                line = '';
                continue;
            }

            flush();
            lines.push(indent(openIndexes.length) + token);
            continue;
        }

        flush();
        openIndexes.push(lines.length);
        lines.push(indent(openIndexes.length - 1) + token);
    }

    flush();

    return lines.join('\n');
}
