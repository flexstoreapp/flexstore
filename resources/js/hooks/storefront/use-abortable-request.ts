import type { CancelToken } from '@inertiajs/core';
import { useEffect, useRef } from 'react';

export function useAbortableRequest() {
    const cancelToken = useRef<CancelToken | null>(null);

    const abort = () => {
        cancelToken.current?.cancel();
        cancelToken.current = null;
    };

    useEffect(
        () => () => {
            cancelToken.current?.cancel();
        },
        [],
    );

    const run = (perform: (capture: (token: CancelToken) => void) => void) => {
        abort();
        perform((token) => {
            cancelToken.current = token;
        });
    };

    return { run, abort };
}
