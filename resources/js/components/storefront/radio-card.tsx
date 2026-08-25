import type { KeyboardEvent, ReactNode } from 'react';

import { useDirection } from '@/hooks/storefront/use-direction';
import { cn } from '@/lib/utils';

interface RadioCardProps {
    checked: boolean;
    onSelect: () => void;
    children: ReactNode;
    align?: 'start' | 'center';
    showDot?: boolean;
    className?: string;
}

function handleKey(event: KeyboardEvent<HTMLDivElement>, onSelect: () => void, rtl: boolean) {
    if (event.key === ' ' || event.key === 'Enter') {
        event.preventDefault();
        onSelect();
        return;
    }

    const forward = event.key === 'ArrowDown' || event.key === (rtl ? 'ArrowLeft' : 'ArrowRight');
    const backward = event.key === 'ArrowUp' || event.key === (rtl ? 'ArrowRight' : 'ArrowLeft');
    if (!forward && !backward) {
        return;
    }

    const group = event.currentTarget.closest('[role="radiogroup"]');
    if (!group) {
        return;
    }

    event.preventDefault();
    const radios = Array.from(group.querySelectorAll<HTMLElement>('[role="radio"]'));
    const current = radios.indexOf(event.currentTarget);
    const nextIndex = (current + (forward ? 1 : -1) + radios.length) % radios.length;
    radios[nextIndex]?.click();
    requestAnimationFrame(() => {
        group.querySelectorAll<HTMLElement>('[role="radio"]')[nextIndex]?.focus();
    });
}

export function RadioCard({
    checked,
    onSelect,
    children,
    align = 'center',
    showDot = true,
    className,
}: RadioCardProps) {
    const direction = useDirection();

    const select = () => {
        if (!checked) {
            onSelect();
        }
    };

    return (
        <div
            role="radio"
            tabIndex={checked ? 0 : -1}
            aria-checked={checked}
            onClick={select}
            onKeyDown={(event) => handleKey(event, select, direction === 'rtl')}
            className={cn(
                'group flex cursor-default gap-4 rounded-md border border-line-strong bg-surface p-4 transition focus-visible:outline-offset-0 aria-checked:relative aria-checked:z-10 aria-checked:border-primary aria-checked:bg-primary-tint/50',
                align === 'start' ? 'items-start' : 'items-center',
                className,
            )}
        >
            {showDot && (
                <span
                    aria-hidden="true"
                    className={cn(
                        'flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 border-line-strong transition group-aria-checked:border-primary group-aria-checked:bg-primary',
                        align === 'start' && 'mt-0.5',
                    )}
                >
                    <span className="h-2 w-2 scale-0 rounded-full bg-surface transition group-aria-checked:scale-100" />
                </span>
            )}
            {children}
        </div>
    );
}
