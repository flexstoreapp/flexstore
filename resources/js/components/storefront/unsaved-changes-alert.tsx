import { useFormContext } from '@inertiajs/react';

import { useConfirm } from '@/components/storefront/confirm';
import { useUnsavedChangesAlert } from '@/hooks/use-unsaved-changes-alert';

export function UnsavedChangesAlert() {
    const form = useFormContext();
    const { confirm } = useConfirm();

    useUnsavedChangesAlert(form?.isDirty ?? false, { confirm });

    return null;
}
