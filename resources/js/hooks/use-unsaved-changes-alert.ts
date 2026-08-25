import { router, type Visit } from '@inertiajs/core';
import { useEffect, useRef } from 'react';

import { consumeLeaveConfirmation } from '@/hooks/use-leave-guard';
import { __ } from '@/lib/i18n';

type ConfirmFn = (options: { title: string; description: string }) => Promise<boolean>;

interface UnsavedChangesAlertOptions {
    enabled?: boolean;
    title?: string;
    description?: string;
    confirm?: ConfirmFn;
}

const nativeConfirm: ConfirmFn = ({ title, description }) => Promise.resolve(window.confirm(description || title));

export function useUnsavedChangesAlert(isDirty: boolean, options: boolean | UnsavedChangesAlertOptions = true) {
    const resolved = typeof options === 'boolean' ? { enabled: options } : options;
    const { enabled = true, title, description, confirm = nativeConfirm } = resolved;

    const skipGuardRef = useRef(false);
    const confirmRef = useRef(confirm);
    useEffect(() => {
        confirmRef.current = confirm;
    });

    useEffect(() => {
        if (!isDirty || !enabled) return;

        const onBeforeUnload = (e: BeforeUnloadEvent) => {
            e.preventDefault();
        };

        const removeInertiaListener = router.on('before', (event) => {
            const { visit } = event.detail;

            if (visit.prefetch) return true;

            if (consumeLeaveConfirmation()) return true;

            if (skipGuardRef.current) {
                skipGuardRef.current = false;
                return true;
            }

            if (visit.method !== 'get') return true;

            event.preventDefault();

            window.setTimeout(() => {
                confirmRef
                    .current({
                        title: title ?? __('Leave page?'),
                        description:
                            description ?? __('You have unsaved changes. Are you sure you want to leave this page?'),
                    })
                    .then((confirmed) => {
                        if (confirmed) {
                            skipGuardRef.current = true;
                            router.visit(visit.url, visit as Partial<Visit>);
                        }
                    });
            }, 0);

            return false;
        });

        window.addEventListener('beforeunload', onBeforeUnload);

        return () => {
            window.removeEventListener('beforeunload', onBeforeUnload);
            removeInertiaListener();
        };
    }, [isDirty, enabled, title, description]);
}
