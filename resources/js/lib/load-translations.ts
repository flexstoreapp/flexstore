export function readDomTranslations(): Record<string, string> | null {
    if (typeof document === 'undefined') {
        return null;
    }

    const payload = document.getElementById('app-translations')?.textContent;

    if (!payload) {
        return null;
    }

    try {
        const decoded: unknown = JSON.parse(payload);

        if (decoded === null || typeof decoded !== 'object' || Array.isArray(decoded)) {
            return null;
        }

        return decoded as Record<string, string>;
    } catch {
        return null;
    }
}
