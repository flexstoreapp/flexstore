import { useEffect } from 'react';

export function useHotkey(key: string, callback: () => void) {
    useEffect(() => {
        const parts = key.split('+');
        const actualKey = parts[parts.length - 1].toLowerCase();
        const modifiers = parts.slice(0, -1).map((m) => m.toLowerCase());

        const down = (e: KeyboardEvent) => {
            const target = e.target as HTMLElement;

            if (
                target &&
                (target instanceof HTMLInputElement ||
                    target instanceof HTMLTextAreaElement ||
                    target.isContentEditable)
            ) {
                return;
            }

            const modifierMap: Record<string, boolean> = {
                ctrl: e.ctrlKey,
                control: e.ctrlKey,
                alt: e.altKey,
                option: e.altKey,
                shift: e.shiftKey,
                meta: e.metaKey,
                cmd: e.metaKey,
                command: e.metaKey,
            };

            const canonicalModifier = (mod: string) => {
                if (['cmd', 'meta', 'command'].includes(mod)) return 'meta';
                if (['ctrl', 'control'].includes(mod)) return 'ctrl';
                if (['alt', 'option'].includes(mod)) return 'alt';
                if (['shift'].includes(mod)) return 'shift';

                return mod;
            };

            const requiredModifiers = modifiers.filter((m) => m).map(canonicalModifier);
            const allRequiredPressed = requiredModifiers.every((mod) => modifierMap[mod]);

            if (requiredModifiers.length > 0) {
                if (e.key.toLowerCase() === actualKey && allRequiredPressed) {
                    e.preventDefault();
                    callback();
                }
            } else {
                if (e.key.toLowerCase() === actualKey && !e.ctrlKey && !e.metaKey && !e.altKey && !e.shiftKey) {
                    e.preventDefault();
                    callback();
                }
            }
        };

        document.addEventListener('keydown', down);

        return () => document.removeEventListener('keydown', down);
    }, [key, callback]);
}
