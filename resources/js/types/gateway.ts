import type { ReactNode } from 'react';

import type { PaymentOption } from './payment';

export interface PaymentGatewayAdapter {
    processing: boolean;
    ready: boolean;
    error: string | null;
    replacesSubmitButton: boolean;
    submitButtonText: string;
    handleSubmit: () => Promise<void>;
    renderInlineContent: ((option: PaymentOption) => ReactNode) | null;
    renderHiddenComponent: (() => ReactNode) | null;
    renderSubmitAction: (() => ReactNode) | null;
}

export interface GatewayContext {
    option: PaymentOption | null;
    amount: string;
    submitUrl: string;
    pageComponent: string;
    currencyCode: string;
    currencyDecimalPlaces: number;
    storeName: string;
    formData: Record<string, unknown>;
    setFormError: (key: string, message: string) => void;
    scrollToFirstError: () => void;
    setSubmitting: (value: boolean) => void;
}
