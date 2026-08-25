import { usePage } from '@inertiajs/react';
import { type ReactNode } from 'react';

import { CopyableInput } from '@/components/ui/copyable-input';
import { Field, FieldDescription, FieldLabel } from '@/components/ui/field';
import { __ } from '@/lib/i18n';
import type { AdminSharedData } from '@/types';

export function useWebhookUrl(path: string): string {
    const { appUrl } = usePage<AdminSharedData>().props;

    return `${appUrl}${path}`;
}

interface WebhookUrlFieldProps {
    url: string;
    description: ReactNode;
}

export function WebhookUrlField({ url, description }: WebhookUrlFieldProps) {
    return (
        <Field>
            <FieldLabel>{__('Webhook URL')}</FieldLabel>
            <CopyableInput value={url} />
            <FieldDescription className="text-xs">{description}</FieldDescription>
        </Field>
    );
}
