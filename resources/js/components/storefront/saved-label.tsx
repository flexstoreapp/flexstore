import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export function SavedLabel({ visible }: { visible: boolean }) {
    return (
        <span
            aria-live="polite"
            className={cn(
                'text-sm font-semibold text-muted transition-opacity duration-300',
                visible ? 'opacity-100' : 'opacity-0',
            )}
        >
            {__('Saved')}
        </span>
    );
}
