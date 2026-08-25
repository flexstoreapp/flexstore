import { EyeIcon } from 'lucide-react';

import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

const quickActionClass =
    'w-10 h-10 flex items-center justify-center text-ink can-hover:hover:bg-surface-2 transition-colors focus-visible:-outline-offset-2';

interface ProductCardQuickActionsProps {
    reveal: string;
    onQuickView: () => void;
}

export function ProductCardQuickActions({ reveal, onQuickView }: ProductCardQuickActionsProps) {
    return (
        <div
            className={cn(
                'absolute end-0 top-0 flex -translate-y-full flex-col overflow-hidden rounded-es-sm border-s border-b border-line bg-surface opacity-0 transition-[opacity,translate] duration-(--duration-fast) ease-out-quart no-hover:hidden [&>*:last-child]:rounded-es-[7px]',
                reveal,
            )}
        >
            <button
                type="button"
                onClick={onQuickView}
                aria-label={__('Quick view')}
                title={__('Quick view')}
                className={quickActionClass}
            >
                <EyeIcon size={17} strokeWidth={1.8} aria-hidden="true" />
            </button>
        </div>
    );
}
