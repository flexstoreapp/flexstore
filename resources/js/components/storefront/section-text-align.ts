import type { SectionTextAlign } from '@/types';

export function overlayPosition(align: SectionTextAlign | undefined): { justify: string; items: string; text: string } {
    const value = align ?? 'left';
    const [vertical, horizontal] = value.includes('-') ? (value.split('-') as [string, string]) : ['middle', value];

    return {
        justify: vertical === 'top' ? 'justify-start' : vertical === 'bottom' ? 'justify-end' : 'justify-center',
        items: horizontal === 'center' ? 'items-center' : horizontal === 'right' ? 'items-end' : 'items-start',
        text: horizontal === 'center' ? 'text-center' : horizontal === 'right' ? 'text-end' : 'text-start',
    };
}
