import { type KeyboardEvent, useState } from 'react';

export function useSearchDropdown<T>(items: T[], onSelect: (item: T) => void) {
    const [open, setOpen] = useState(false);
    const [activeIndex, setActiveIndex] = useState(-1);

    const handleKeyDown = (event: KeyboardEvent) => {
        if (event.key === 'Escape') {
            setOpen(false);
            return;
        }

        if (items.length === 0) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActiveIndex((current) => (current + 1) % items.length);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveIndex((current) => (current <= 0 ? items.length - 1 : current - 1));
        } else if (event.key === 'Enter' && activeIndex >= 0) {
            event.preventDefault();
            setOpen(false);
            onSelect(items[activeIndex]);
        }
    };

    return { open, setOpen, activeIndex, setActiveIndex, handleKeyDown };
}
