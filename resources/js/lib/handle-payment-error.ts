import { isHttpError } from '@/lib/http';
import { __ } from '@/lib/i18n';

export function handlePaymentError(
    err: unknown,
    setFormError: (key: string, message: string) => void,
    setError: (message: string) => void,
    scrollToFirstError?: () => void,
): void {
    if (isHttpError(err) && err.status === 422 && err.data?.errors) {
        const errors = err.data.errors as Record<string, string[]>;
        for (const [key, messages] of Object.entries(errors)) {
            if (Array.isArray(messages) && messages.length > 0) {
                setFormError(key, messages[0]);
            }
        }

        scrollToFirstError?.();

        return;
    }

    const message = isHttpError(err) ? (err.data?.message as string) : (err as { message?: string }).message;

    setError(message ?? __('An error occurred during payment.'));
}
