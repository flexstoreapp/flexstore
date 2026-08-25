import { type UrlMethodPair } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import { useEffect } from 'react';

interface UseBackNavigationOptions {
    fallbackHref?: UrlMethodPair;
}

let navigationDepth = 0;
let isGoingBack = false;
let isUsingFallback = false;
let isInitialized = false;

function initializeGlobalListeners() {
    if (isInitialized) return;
    isInitialized = true;

    window.addEventListener('popstate', () => {
        isGoingBack = true;
    });

    router.on('navigate', () => {
        if (isGoingBack) {
            navigationDepth = Math.max(0, navigationDepth - 1);
            isGoingBack = false;
        } else if (isUsingFallback) {
            navigationDepth = 1;
            isUsingFallback = false;
        } else {
            navigationDepth++;
        }
    });
}

export function useBackNavigation({ fallbackHref }: UseBackNavigationOptions = {}) {
    useEffect(() => {
        initializeGlobalListeners();
    }, []);

    const goBack = () => {
        if (navigationDepth > 1) {
            isGoingBack = true;
            window.history.back();
        } else if (fallbackHref) {
            isUsingFallback = true;
            router.visit(fallbackHref.url);
        } else {
            window.history.back();
        }
    };

    return { goBack };
}
