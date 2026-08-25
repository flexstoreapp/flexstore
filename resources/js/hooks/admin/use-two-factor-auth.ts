import { useCallback, useMemo, useState } from 'react';

import * as TwoFactorQrCodeController from '@/actions/App/Http/Controllers/Admin/TwoFactorQrCodeController';
import * as TwoFactorSecretKeyController from '@/actions/App/Http/Controllers/Admin/TwoFactorSecretKeyController';
import { httpGet } from '@/lib/http';
import { __ } from '@/lib/i18n';

interface TwoFactorData {
    svg: string;
}

interface TwoFactorSecretKey {
    secretKey: string;
}

export const OTP_MAX_LENGTH = 6;

export const useTwoFactorAuth = () => {
    const [qrCodeSvg, setQrCodeSvg] = useState<string | null>(null);
    const [manualSetupKey, setManualSetupKey] = useState<string | null>(null);
    const [errors, setErrors] = useState<string[]>([]);

    const hasSetupData = useMemo<boolean>(
        () => qrCodeSvg !== null && manualSetupKey !== null,
        [qrCodeSvg, manualSetupKey],
    );

    const fetchQrCode = useCallback(async (): Promise<void> => {
        try {
            const data = await httpGet<TwoFactorData>(TwoFactorQrCodeController.show());
            setQrCodeSvg(data.svg);
        } catch {
            setErrors((prev) => [...prev, __('Failed to fetch QR code.')]);
            setQrCodeSvg(null);
        }
    }, []);

    const fetchSetupKey = useCallback(async (): Promise<void> => {
        try {
            const data = await httpGet<TwoFactorSecretKey>(TwoFactorSecretKeyController.show());
            setManualSetupKey(data.secretKey);
        } catch {
            setErrors((prev) => [...prev, __('Failed to fetch a setup key.')]);
            setManualSetupKey(null);
        }
    }, []);

    const clearErrors = useCallback((): void => {
        setErrors([]);
    }, []);

    const clearSetupData = useCallback((): void => {
        setManualSetupKey(null);
        setQrCodeSvg(null);
        clearErrors();
    }, [clearErrors]);

    const fetchSetupData = useCallback(async (): Promise<void> => {
        try {
            clearErrors();
            await Promise.all([fetchQrCode(), fetchSetupKey()]);
        } catch {
            setQrCodeSvg(null);
            setManualSetupKey(null);
        }
    }, [clearErrors, fetchQrCode, fetchSetupKey]);

    return {
        qrCodeSvg,
        manualSetupKey,
        hasSetupData,
        errors,
        clearErrors,
        clearSetupData,
        fetchQrCode,
        fetchSetupKey,
        fetchSetupData,
    };
};
