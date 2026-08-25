import { useEffect, useRef, useState } from 'react';

export function useGatewayHandlers<THandlers>(isEmbedded: boolean, hasElement: boolean) {
    const handlersRef = useRef<THandlers | null>(null);
    const [handlersReady, setHandlersReady] = useState(false);

    const [prevIsEmbedded, setPrevIsEmbedded] = useState(isEmbedded);
    if (isEmbedded !== prevIsEmbedded) {
        setPrevIsEmbedded(isEmbedded);
        setHandlersReady(false);
    }

    useEffect(() => {
        if (!isEmbedded) {
            handlersRef.current = null;
        }
    }, [isEmbedded]);

    const handleReady = (handlers: THandlers) => {
        handlersRef.current = handlers;
        setHandlersReady(true);
    };

    return {
        handlersRef,
        handleReady,
        ready: !isEmbedded || !hasElement || handlersReady,
    };
}
