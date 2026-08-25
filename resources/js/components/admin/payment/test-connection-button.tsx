import { type FormDataConvertible } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import { useState } from 'react';

import TestPaymentGatewayConnectionController from '@/actions/App/Http/Controllers/Admin/TestPaymentGatewayConnectionController';
import { SubmitButton } from '@/components/admin/submit-button';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { PaymentGatewayDriver } from '@/types';

interface TestConnectionButtonProps {
    driver: PaymentGatewayDriver;
    credentials: Record<string, FormDataConvertible>;
    disabled?: boolean;
}

export function TestConnectionButton({ driver, credentials, disabled = false }: TestConnectionButtonProps) {
    const [testing, setTesting] = useState(false);
    const [result, setResult] = useState<{ ok: boolean; message: string } | null>(null);

    const testConnection = () => {
        setTesting(true);
        setResult(null);
        router.post(
            TestPaymentGatewayConnectionController(),
            { driver, credentials },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => setResult({ ok: true, message: __('Connection successful.') }),
                onError: (formErrors) =>
                    setResult({ ok: false, message: formErrors.connection ?? __('Connection failed.') }),
                onFinish: () => setTesting(false),
            },
        );
    };

    return (
        <div className="flex items-center gap-3">
            <SubmitButton
                type="button"
                variant="outline"
                processing={testing}
                disabled={testing || disabled}
                onClick={testConnection}
            >
                {__('Test connection')}
            </SubmitButton>
            {result && (
                <span
                    className={cn('text-xs', result.ok ? 'text-emerald-700 dark:text-emerald-400' : 'text-destructive')}
                >
                    {result.message}
                </span>
            )}
        </div>
    );
}
