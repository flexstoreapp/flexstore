import { useEffect } from 'react';

export function usePointerModality() {
    useEffect(() => {
        const handlePointerDown = () => {
            document.documentElement.setAttribute('data-pointer', '');
        };

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Tab') {
                document.documentElement.removeAttribute('data-pointer');
            }
        };

        window.addEventListener('pointerdown', handlePointerDown, true);
        window.addEventListener('keydown', handleKeyDown, true);

        return () => {
            window.removeEventListener('pointerdown', handlePointerDown, true);
            window.removeEventListener('keydown', handleKeyDown, true);
            document.documentElement.removeAttribute('data-pointer');
        };
    }, []);
}
