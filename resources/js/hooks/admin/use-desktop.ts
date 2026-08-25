import { useSyncExternalStore } from 'react';

const DESKTOP_BREAKPOINT = 1024;

const mql = typeof window === 'undefined' ? undefined : window.matchMedia(`(min-width: ${DESKTOP_BREAKPOINT}px)`);

function mediaQueryListener(callback: (event: MediaQueryListEvent) => void) {
    if (!mql) {
        return () => {};
    }

    mql.addEventListener('change', callback);

    return () => {
        mql.removeEventListener('change', callback);
    };
}

function isDesktopDisplay(): boolean {
    return mql?.matches ?? false;
}

function getServerSnapshot(): boolean {
    return false;
}

export function useIsDesktop(): boolean {
    return useSyncExternalStore(mediaQueryListener, isDesktopDisplay, getServerSnapshot);
}
