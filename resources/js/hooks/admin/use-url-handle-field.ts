import { useCallback, useState } from 'react';

import { slugify } from '@/lib/utils';

interface UseUrlHandleFieldOptions {
    initial?: string;
    autoGenerate?: boolean;
}

export function useUrlHandleField({ initial = '', autoGenerate = true }: UseUrlHandleFieldOptions = {}) {
    const [urlHandle, setUrlHandle] = useState(initial);
    const [isManuallyChanged, setIsManuallyChanged] = useState(false);
    const [lastSource, setLastSource] = useState('');

    const onSourceChange = (value: string) => {
        setLastSource(value);
        if (autoGenerate && !isManuallyChanged) {
            setUrlHandle(slugify(value));
        }
    };

    const onUrlHandleChange = useCallback((value: string) => {
        setUrlHandle(value);
        setIsManuallyChanged(true);
    }, []);

    const reset = useCallback((newUrlHandle?: string, sourceValue: string = '') => {
        setIsManuallyChanged(false);
        setLastSource(sourceValue);
        if (newUrlHandle !== undefined) {
            setUrlHandle(newUrlHandle);
        }
    }, []);

    const needsServerGeneration = autoGenerate && !isManuallyChanged && lastSource.trim() !== '' && urlHandle === '';

    return { urlHandle, onSourceChange, onUrlHandleChange, reset, needsServerGeneration };
}
