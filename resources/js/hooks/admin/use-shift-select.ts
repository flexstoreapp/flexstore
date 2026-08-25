import { useRef } from 'react';

export function useShiftSelect() {
    const lastIndex = useRef<number | null>(null);

    function getRange(currentIndex: number, shiftKey: boolean): [number, number] | null {
        const range =
            shiftKey && lastIndex.current !== null && lastIndex.current !== currentIndex
                ? ([Math.min(lastIndex.current, currentIndex), Math.max(lastIndex.current, currentIndex)] as [
                      number,
                      number,
                  ])
                : null;

        lastIndex.current = currentIndex;
        return range;
    }

    function reset() {
        lastIndex.current = null;
    }

    return { getRange, reset };
}
