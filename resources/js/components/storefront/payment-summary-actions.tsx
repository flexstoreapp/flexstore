import { Suspense, type ReactNode } from 'react';

import { Button } from '@/components/storefront/button';
import { TermsNotice } from '@/components/storefront/terms-notice';
import type { PaymentGatewayAdapter } from '@/types';

interface PaymentSummaryActionsProps {
    gateway: PaymentGatewayAdapter;
    generalErrors?: string[];
    error?: string | null;
    submitDisabled?: boolean;
    submitting?: boolean;
    submitLabel?: string;
    notice?: ReactNode;
    children?: ReactNode;
}

export function PaymentSummaryActions({
    gateway,
    generalErrors = [],
    error = null,
    submitDisabled = false,
    submitting = false,
    submitLabel,
    notice,
    children,
}: PaymentSummaryActionsProps) {
    const inlineError = gateway.error || error;

    return (
        <>
            {generalErrors.length > 0 && (
                <div role="alert" className="mt-4 rounded-md border border-error bg-error/10 p-3 text-sm text-error">
                    {generalErrors.map((message, index) => (
                        <p key={index} className="m-0">
                            {message}
                        </p>
                    ))}
                </div>
            )}

            {inlineError && (
                <p role="alert" className="mt-4 mb-0 text-sm text-error">
                    {inlineError}
                </p>
            )}

            <div className="mt-5">
                {gateway.replacesSubmitButton ? (
                    <Suspense fallback={null}>{gateway.renderSubmitAction?.()}</Suspense>
                ) : (
                    <Button
                        type="submit"
                        size="lg"
                        disabled={submitDisabled}
                        processing={submitting || gateway.processing || !gateway.ready}
                        block
                    >
                        {submitLabel ?? gateway.submitButtonText}
                    </Button>
                )}
            </div>

            {children}

            {notice ?? <TermsNotice />}
        </>
    );
}
