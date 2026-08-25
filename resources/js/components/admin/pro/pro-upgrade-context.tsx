import { createContext, useCallback, useContext, useMemo, useState, type ReactNode } from 'react';

import { ProUpgradeDialog } from '@/components/admin/pro/pro-upgrade-dialog';

interface ProUpgradeContextValue {
    open: (feature?: string) => void;
}

const ProUpgradeContext = createContext<ProUpgradeContextValue | null>(null);

export function ProUpgradeProvider({ children }: { children: ReactNode }) {
    const [feature, setFeature] = useState<string | null>(null);
    const [isOpen, setIsOpen] = useState(false);

    const open = useCallback((name?: string) => {
        setFeature(name ?? null);
        setIsOpen(true);
    }, []);

    const value = useMemo(() => ({ open }), [open]);

    return (
        <ProUpgradeContext value={value}>
            {children}
            <ProUpgradeDialog open={isOpen} onOpenChange={setIsOpen} feature={feature} />
        </ProUpgradeContext>
    );
}

export function useProUpgrade(): ProUpgradeContextValue {
    const context = useContext(ProUpgradeContext);

    if (context === null) {
        throw new Error('useProUpgrade must be used within a ProUpgradeProvider.');
    }

    return context;
}
