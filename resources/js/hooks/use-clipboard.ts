// Credit: https://usehooks-ts.com/
import { useCallback, useEffect, useRef, useState } from 'react';
import { flushSync } from 'react-dom';

type CopiedValue = string | null;

type CopyFn = (text: string) => Promise<boolean>;

export function useClipboard(): [CopiedValue, CopyFn] {
    const [copiedText, setCopiedText] = useState<CopiedValue>(null);
    const timeoutRef = useRef<NodeJS.Timeout | null>(null);

    useEffect(() => {
        if (!copiedText) return;

        if (timeoutRef.current) clearTimeout(timeoutRef.current);

        timeoutRef.current = setTimeout(() => {
            setCopiedText(null);
            timeoutRef.current = null;
        }, 2000);

        return () => {
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
                timeoutRef.current = null;
            }
        };
    }, [copiedText]);

    const copy: CopyFn = useCallback(async (text) => {
        if (!navigator?.clipboard) {
            console.warn('Clipboard not supported');

            return false;
        }

        try {
            await navigator.clipboard.writeText(text);
            flushSync(() => setCopiedText(null));
            flushSync(() => setCopiedText(text));

            return true;
        } catch (error) {
            console.warn('Copy failed', error);
            setCopiedText(null);

            return false;
        }
    }, []);

    return [copiedText, copy];
}
