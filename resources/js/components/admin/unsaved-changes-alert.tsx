import { useFormContext } from '@inertiajs/react';

import { useConfirm } from '@/components/admin/confirm';
import { useLeaveGuard } from '@/hooks/use-leave-guard';
import { useUnsavedChangesAlert } from '@/hooks/use-unsaved-changes-alert';
import { __ } from '@/lib/i18n';

export function UnsavedChangesAlert() {
    const form = useFormContext();
    const { confirm } = useConfirm();
    const isDirty = form?.isDirty ?? false;

    useUnsavedChangesAlert(isDirty, { confirm });

    useLeaveGuard(isDirty, () =>
        confirm({
            title: __('Leave page?'),
            description: __('You have unsaved changes. Are you sure you want to leave this page?'),
        }),
    );

    return null;
}
