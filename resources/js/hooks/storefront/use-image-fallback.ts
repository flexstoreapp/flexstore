import { useState } from 'react';

export function useImageFallback(url: string | null | undefined) {
    const [failedUrl, setFailedUrl] = useState<string | null>(null);

    return {
        failed: !url || url === failedUrl,
        imgProps: {
            ref: (element: HTMLImageElement | null) => {
                if (element && element.complete && element.naturalWidth === 0 && url) {
                    setFailedUrl(url);
                }
            },
            onError: () => setFailedUrl(url ?? null),
        },
    };
}
