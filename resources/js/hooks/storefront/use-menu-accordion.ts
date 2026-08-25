import { type KeyboardEvent, useState } from 'react';

export type MenuAccordion = ReturnType<typeof useMenuAccordion>;

export function useMenuAccordion() {
    const [expanded, setExpanded] = useState<Record<string, boolean>>({});

    const isOpen = (key: string) => Boolean(expanded[key]);

    const reset = () => setExpanded({});

    const toggle = (key: string) =>
        setExpanded((state) => {
            if (state[key]) {
                return Object.fromEntries(
                    Object.entries(state).filter(([openKey]) => openKey !== key && !openKey.startsWith(`${key}/`)),
                );
            }

            const next: Record<string, boolean> = {};
            for (const openKey of Object.keys(state)) {
                if (key.startsWith(`${openKey}/`)) {
                    next[openKey] = true;
                }
            }
            next[key] = true;

            return next;
        });

    const triggerProps = (key: string) => ({
        role: 'button' as const,
        tabIndex: 0,
        'aria-expanded': isOpen(key),
        onClick: () => toggle(key),
        onKeyDown: (event: KeyboardEvent<HTMLDivElement>) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggle(key);
            }
        },
    });

    return { isOpen, toggle, reset, triggerProps };
}
