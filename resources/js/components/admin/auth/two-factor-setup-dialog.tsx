import { Form } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { CheckIcon, CopyIcon, ScanLineIcon } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

import * as TwoFactorConfirmationController from '@/actions/App/Http/Controllers/Admin/TwoFactorConfirmationController';
import {
    AdaptiveDialog,
    AdaptiveDialogContent,
    AdaptiveDialogContentContainer,
    AdaptiveDialogDescription,
    AdaptiveDialogHeader,
    AdaptiveDialogTitle,
} from '@/components/ui/adaptive-dialog';
import { AlertError } from '@/components/ui/alert-error';
import { Button } from '@/components/ui/button';
import { FieldError } from '@/components/ui/field';
import { InputOTP, InputOTPGroup, InputOTPSeparator, InputOTPSlot } from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import { OTP_MAX_LENGTH } from '@/hooks/admin/use-two-factor-auth';
import { useClipboard } from '@/hooks/use-clipboard';
import { __ } from '@/lib/i18n';

interface TwoFactorDialogProps {
    isOpen: boolean;
    onClose: () => void;
    requiresConfirmation: boolean;
    twoFactorEnabled: boolean;
    qrCodeSvg: string | null;
    manualSetupKey: string | null;
    clearSetupData: () => void;
    fetchSetupData: () => Promise<void>;
    errors: string[];
}

export function TwoFactorDialog({
    isOpen,
    onClose,
    requiresConfirmation,
    twoFactorEnabled,
    qrCodeSvg,
    manualSetupKey,
    clearSetupData,
    fetchSetupData,
    errors,
}: TwoFactorDialogProps) {
    const [showVerificationStep, setShowVerificationStep] = useState<boolean>(false);

    const modalConfig = useMemo(() => {
        if (showVerificationStep) {
            return {
                title: __('Verify authentication code'),
                description: __('Enter the 6-digit code from your authenticator app.'),
            };
        }

        return {
            title: __('Enable two-factor authentication'),
            description: __(
                'To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app',
            ),
        };
    }, [showVerificationStep]);

    const handleModalNextStep = useCallback(() => {
        if (requiresConfirmation) {
            setShowVerificationStep(true);
            return;
        }

        clearSetupData();
        onClose();
    }, [requiresConfirmation, clearSetupData, onClose]);

    const resetModalState = useCallback(() => {
        setShowVerificationStep(false);

        if (twoFactorEnabled) {
            clearSetupData();
        }
    }, [twoFactorEnabled, clearSetupData]);

    useEffect(() => {
        if (isOpen && !qrCodeSvg) {
            fetchSetupData();
        }
    }, [isOpen, qrCodeSvg, fetchSetupData]);

    const handleClose = useCallback(() => {
        resetModalState();
        onClose();
    }, [onClose, resetModalState]);

    return (
        <AdaptiveDialog open={isOpen} onOpenChange={(open) => !open && handleClose()}>
            <AdaptiveDialogContent>
                <AdaptiveDialogContentContainer>
                    <AdaptiveDialogHeader className="flex items-center justify-center">
                        <GridScanIcon />
                        <AdaptiveDialogTitle>{modalConfig.title}</AdaptiveDialogTitle>
                        <AdaptiveDialogDescription className="text-center">
                            {modalConfig.description}
                        </AdaptiveDialogDescription>
                    </AdaptiveDialogHeader>

                    <div className="flex flex-col items-center space-y-4">
                        {showVerificationStep ? (
                            <TwoFactorVerificationStep
                                onClose={onClose}
                                onBack={() => setShowVerificationStep(false)}
                            />
                        ) : (
                            <TwoFactorStep
                                qrCodeSvg={qrCodeSvg}
                                manualSetupKey={manualSetupKey}
                                onNextStep={handleModalNextStep}
                                errors={errors}
                            />
                        )}
                    </div>
                </AdaptiveDialogContentContainer>
            </AdaptiveDialogContent>
        </AdaptiveDialog>
    );
}

function GridScanIcon() {
    return (
        <div className="mb-3 rounded-full border border-border bg-card p-0.5 shadow-sm">
            <div className="relative overflow-hidden rounded-full border border-border bg-muted p-2.5">
                <div className="absolute inset-0 grid grid-cols-5 opacity-50">
                    {Array.from({ length: 5 }, (_, i) => (
                        <div key={`col-${i + 1}`} className="border-e border-border last:border-e-0" />
                    ))}
                </div>
                <div className="absolute inset-0 grid grid-rows-5 opacity-50">
                    {Array.from({ length: 5 }, (_, i) => (
                        <div key={`row-${i + 1}`} className="border-b border-border last:border-b-0" />
                    ))}
                </div>
                <ScanLineIcon className="relative z-20 size-6 text-foreground" />
            </div>
        </div>
    );
}

interface TwoFactorStepProps {
    qrCodeSvg: string | null;
    manualSetupKey: string | null;
    onNextStep: () => void;
    errors: string[];
}

function TwoFactorStep({ qrCodeSvg, manualSetupKey, onNextStep, errors }: TwoFactorStepProps) {
    const [copiedText, copy] = useClipboard();
    const IconComponent = copiedText === manualSetupKey ? CheckIcon : CopyIcon;

    return (
        <>
            {errors?.length ? (
                <AlertError errors={errors} />
            ) : (
                <>
                    <div className="mx-auto flex max-w-md overflow-hidden">
                        <div className="mx-auto aspect-square w-64 rounded-lg border border-border">
                            <div className="z-10 flex h-full w-full items-center justify-center p-5">
                                {qrCodeSvg ? (
                                    <div
                                        className="aspect-square w-full rounded-lg bg-white p-2 [&_svg]:size-full"
                                        dangerouslySetInnerHTML={{
                                            __html: qrCodeSvg,
                                        }}
                                    />
                                ) : (
                                    <Spinner />
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="flex w-full gap-x-5">
                        <Button className="w-full" onClick={onNextStep}>
                            {__('Continue')}
                        </Button>
                    </div>

                    <div className="relative flex w-full items-center justify-center">
                        <div className="absolute inset-0 top-1/2 h-px w-full bg-border" />
                        <span className="relative bg-background px-2 py-1">{__('Or, enter the code manually')}</span>
                    </div>

                    <div className="flex w-full gap-x-2">
                        <div className="flex w-full items-stretch overflow-hidden rounded-xl border border-border">
                            {!manualSetupKey ? (
                                <div className="flex h-full w-full items-center justify-center bg-muted p-3">
                                    <Spinner />
                                </div>
                            ) : (
                                <>
                                    <input
                                        type="text"
                                        value={manualSetupKey}
                                        className="h-full w-full bg-background p-3 text-foreground outline-none"
                                        readOnly
                                    />
                                    <button
                                        onClick={() => copy(manualSetupKey)}
                                        className="border-s border-border px-3 hover:bg-muted/50"
                                    >
                                        <IconComponent className="size-4" />
                                    </button>
                                </>
                            )}
                        </div>
                    </div>
                </>
            )}
        </>
    );
}

function TwoFactorVerificationStep({ onClose, onBack }: { onClose: () => void; onBack: () => void }) {
    const [code, setCode] = useState<string>('');
    const pinInputContainerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        setTimeout(() => {
            pinInputContainerRef.current?.querySelector('input')?.focus();
        }, 0);
    }, []);

    return (
        <Form {...TwoFactorConfirmationController.store.form()} onSuccess={() => onClose()} resetOnError resetOnSuccess>
            {({ processing, errors }) => (
                <>
                    <div ref={pinInputContainerRef} className="relative w-full space-y-3">
                        <div className="flex w-full flex-col items-center space-y-3 py-2">
                            <InputOTP
                                id="otp"
                                name="code"
                                maxLength={OTP_MAX_LENGTH}
                                onChange={setCode}
                                disabled={processing}
                                pattern={REGEXP_ONLY_DIGITS}
                            >
                                <InputOTPGroup>
                                    <InputOTPSlot index={0} />
                                    <InputOTPSlot index={1} />
                                    <InputOTPSlot index={2} />
                                </InputOTPGroup>
                                <InputOTPSeparator />
                                <InputOTPGroup>
                                    <InputOTPSlot index={3} />
                                    <InputOTPSlot index={4} />
                                    <InputOTPSlot index={5} />
                                </InputOTPGroup>
                            </InputOTP>
                            <FieldError>{errors.code}</FieldError>
                        </div>

                        <div className="flex w-full gap-x-5">
                            <Button
                                type="button"
                                variant="outline"
                                className="flex-1"
                                onClick={onBack}
                                disabled={processing}
                            >
                                {__('Back')}
                            </Button>
                            <Button
                                type="submit"
                                className="flex-1"
                                disabled={processing || code.length < OTP_MAX_LENGTH}
                            >
                                {__('Confirm')}
                            </Button>
                        </div>
                    </div>
                </>
            )}
        </Form>
    );
}
