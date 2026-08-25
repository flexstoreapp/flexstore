import { useSyncExternalStore } from 'react';

export type OS = 'Windows' | 'macOS' | 'iOS' | 'Android' | 'Linux' | 'ChromeOS' | 'unknown';

function detect(): OS {
    if (typeof navigator === 'undefined') return 'unknown';
    const userAgent = navigator.userAgent;

    if (/Windows/i.test(userAgent)) return 'Windows';
    if (/Macintosh|Mac OS X|Mac_PowerPC/i.test(userAgent)) return 'macOS';
    if (/(iPhone|iPad|iPod)/i.test(userAgent)) return 'iOS';
    if (/Android/i.test(userAgent)) return 'Android';
    if (/Linux/i.test(userAgent)) return 'Linux';
    if (/CrOS/i.test(userAgent)) return 'ChromeOS';

    return 'unknown';
}

const subscribe = () => () => {};

export function useOsDetector(): OS {
    return useSyncExternalStore(subscribe, detect, () => 'unknown');
}
