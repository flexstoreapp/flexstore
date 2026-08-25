import { Form, Head } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useMemo, useState } from 'react';

import * as TwoFactorChallengeController from '@/actions/App/Http/Controllers/Admin/TwoFactorChallengeController';
import { SubmitButton } from '@/components/admin/submit-button';
import { FieldError } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { InputOTP, InputOTPGroup, InputOTPSeparator, InputOTPSlot } from '@/components/ui/input-otp';
import { OTP_MAX_LENGTH } from '@/hooks/admin/use-two-factor-auth';
import { useAuthHeader } from '@/layouts/admin/auth-layout';
import { __ } from '@/lib/i18n';

export default function TwoFactorChallenge() {
    const [showRecoveryInput, setShowRecoveryInput] = useState<boolean>(false);
    const [code, setCode] = useState<string>('');

    const authConfigContent = useMemo<{
        title: string;
        description: string;
        toggleText: string;
    }>(() => {
        if (showRecoveryInput) {
            return {
                title: __('Recovery code'),
                description: __(
                    'Please confirm access to your account by entering one of your emergency recovery codes.',
                ),
                toggleText: __('log in using an authentication code'),
            };
        }

        return {
            title: __('Authentication code'),
            description: __('Enter the authentication code provided by your authenticator application.'),
            toggleText: __('log in using a recovery code'),
        };
    }, [showRecoveryInput]);

    const toggleRecoveryMode = (clearErrors: () => void): void => {
        setShowRecoveryInput(!showRecoveryInput);
        clearErrors();
        setCode('');
    };

    useAuthHeader({ title: authConfigContent.title, description: authConfigContent.description });

    return (
        <>
            <Head title={__('Two-factor auth')} />

            <div className="space-y-6">
                <Form
                    {...TwoFactorChallengeController.store.form()}
                    resetOnError
                    resetOnSuccess={!showRecoveryInput}
                    className="space-y-4"
                >
                    {({ errors, processing, clearErrors }) => (
                        <>
                            {showRecoveryInput ? (
                                <>
                                    <Input
                                        name="recovery_code"
                                        type="text"
                                        placeholder={__('Enter recovery code')}
                                        autoFocus={showRecoveryInput}
                                        required
                                    />
                                    <FieldError>{errors.recovery_code}</FieldError>
                                </>
                            ) : (
                                <div className="flex flex-col items-center justify-center space-y-3 text-center">
                                    <div className="flex w-full items-center justify-center">
                                        <InputOTP
                                            name="code"
                                            maxLength={OTP_MAX_LENGTH}
                                            value={code}
                                            onChange={(value) => setCode(value)}
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
                                    </div>
                                    <FieldError>{errors.code}</FieldError>
                                </div>
                            )}

                            <SubmitButton className="w-full" processing={processing}>
                                {__('Continue')}
                            </SubmitButton>

                            <div className="text-center text-sm text-muted-foreground">
                                <span>{__('Or you can')} </span>
                                <button
                                    type="button"
                                    className="cursor-pointer text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                    onClick={() => toggleRecoveryMode(clearErrors)}
                                >
                                    {authConfigContent.toggleText}
                                </button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
