import { type Dispatch, type SetStateAction, createContext, useCallback, useContext, useState } from 'react';

import { useIsomorphicLayoutEffect } from '@/hooks/admin/use-isomorphic-layout-effect';
import { AdminShell } from '@/layouts/admin/admin-shell';
import { AuthCardLayout } from '@/layouts/admin/auth-card-layout';

interface AuthLayoutProps {
    title?: string;
    description?: string;
}

interface AuthHeaderOverride {
    title?: string;
    description?: string;
}

const AuthHeaderContext = createContext<Dispatch<SetStateAction<AuthHeaderOverride | null>> | null>(null);

export function useAuthHeader(header: AuthHeaderOverride): void {
    const setHeader = useContext(AuthHeaderContext);

    useIsomorphicLayoutEffect(() => {
        if (!setHeader) return;
        setHeader(header);
        return () => setHeader(null);
    }, [setHeader, header]);
}

export function AuthLayout({
    children,
    title: titleProp,
    description: descriptionProp,
}: React.PropsWithChildren<AuthLayoutProps>) {
    const [override, setOverride] = useState<AuthHeaderOverride | null>(null);
    const setOverrideStable = useCallback<Dispatch<SetStateAction<AuthHeaderOverride | null>>>(
        (next) => setOverride(next),
        [],
    );

    const title = override?.title ?? titleProp ?? '';
    const description = override?.description ?? descriptionProp ?? '';

    return (
        <AdminShell>
            <AuthHeaderContext.Provider value={setOverrideStable}>
                <AuthCardLayout title={title} description={description}>
                    {children}
                </AuthCardLayout>
            </AuthHeaderContext.Provider>
        </AdminShell>
    );
}
