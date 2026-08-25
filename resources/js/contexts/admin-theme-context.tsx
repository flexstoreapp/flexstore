import { router } from '@inertiajs/react';
import { createContext, useContext, useEffect, useState, useSyncExternalStore, type ReactNode } from 'react';

import * as AppearanceController from '@/actions/App/Http/Controllers/Admin/AppearanceController';
import {
    applyDarkClass,
    prefersDarkMode,
    subscribeSystemDarkMode,
    updateThemeVariables,
} from '@/lib/admin-theme-utils';
import { type AdminThemeColor, type Appearance } from '@/types';

interface AdminThemeContextType {
    appearance: Appearance;
    themeColor: AdminThemeColor;
    setAppearance: (appearance: Appearance) => void;
    setThemeColor: (themeColor: AdminThemeColor) => void;
    isDark: boolean;
}

const AdminThemeContext = createContext<AdminThemeContextType | undefined>(undefined);

interface AdminThemeProviderProps {
    children: ReactNode;
    serverAppearance: Appearance;
    serverThemeColor: AdminThemeColor;
}

export function AdminThemeProvider({ children, serverAppearance, serverThemeColor }: AdminThemeProviderProps) {
    const [appearance, setAppearanceState] = useState<Appearance>(serverAppearance ?? 'system');
    const [themeColor, setThemeColorState] = useState<AdminThemeColor>(serverThemeColor ?? 'neutral');

    const systemPrefersDark = useSyncExternalStore(subscribeSystemDarkMode, prefersDarkMode, () => false);
    const isDark = appearance === 'system' ? systemPrefersDark : appearance === 'dark';

    useEffect(() => {
        applyDarkClass(appearance);
        updateThemeVariables(themeColor, appearance);
    }, [appearance, themeColor, systemPrefersDark]);

    const setAppearance = (newAppearance: Appearance) => {
        setAppearanceState(newAppearance);

        router.patch(
            AppearanceController.update(),
            { appearance: newAppearance },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['appearance', 'adminThemeColor'],
            },
        );
    };

    const setThemeColor = (newThemeColor: AdminThemeColor) => {
        setThemeColorState(newThemeColor);

        router.patch(
            AppearanceController.update(),
            { admin_theme_color: newThemeColor },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['appearance', 'adminThemeColor'],
            },
        );
    };

    return (
        <AdminThemeContext.Provider
            value={{
                appearance,
                themeColor,
                setAppearance,
                setThemeColor,
                isDark,
            }}
        >
            {children}
        </AdminThemeContext.Provider>
    );
}

export function useAdminTheme() {
    const context = useContext(AdminThemeContext);
    if (context === undefined) {
        throw new Error('useAdminTheme must be used within an AdminThemeProvider');
    }
    return context;
}
