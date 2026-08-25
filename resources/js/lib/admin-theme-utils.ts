import type { AdminThemeColor, Appearance } from '@/types';

import { getTheme } from './admin-theme-config';

function cssVariableName(token: string): string {
    return `--${token
        .replace(/([A-Z])/g, '-$1')
        .toLowerCase()
        .replace(/chart(\d)/, 'chart-$1')}`;
}

export function updateThemeVariables(theme: AdminThemeColor, appearance: Appearance) {
    const themeDefinition = getTheme(theme);
    const isDark = appearance === 'dark' || (appearance === 'system' && prefersDarkMode());
    const colors = isDark ? themeDefinition.dark : themeDefinition.light;

    const root = document.documentElement;

    for (const [token, value] of Object.entries(colors)) {
        root.style.setProperty(cssVariableName(token), value);
    }
}

export function prefersDarkMode(): boolean {
    if (typeof window === 'undefined') {
        return false;
    }
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

export function subscribeSystemDarkMode(callback: () => void): () => void {
    if (typeof window === 'undefined') {
        return () => {};
    }

    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    mediaQuery.addEventListener('change', callback);

    return () => mediaQuery.removeEventListener('change', callback);
}

export function applyDarkClass(appearance: Appearance) {
    const isDark = appearance === 'dark' || (appearance === 'system' && prefersDarkMode());
    document.documentElement.classList.toggle('dark', isDark);
}

export function getThemePreview(theme: AdminThemeColor, isDark: boolean): string {
    const themeDefinition = getTheme(theme);

    return isDark ? themeDefinition.dark.primary : themeDefinition.light.primary;
}
