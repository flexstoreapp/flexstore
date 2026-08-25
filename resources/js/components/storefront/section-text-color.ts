import type { SectionTextColor } from '@/types';

export function overlayTextClass(color: SectionTextColor | undefined, variant: 'heading' | 'body'): string {
    const light = color === 'light';

    if (variant === 'heading') {
        return light ? 'text-white' : 'text-ink';
    }

    return light ? 'text-white/80' : 'text-muted';
}
