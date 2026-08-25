import { Form } from '@inertiajs/react';
import { EyeIcon, EyeOffIcon } from 'lucide-react';
import { useCallback, useState } from 'react';

import * as TwoFactorRecoveryCodesController from '@/actions/App/Http/Controllers/Admin/TwoFactorRecoveryCodesController';
import { SubmitButton } from '@/components/admin/submit-button';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { __, __nodes } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export function TwoFactorRecoveryCodes({ recoveryCodes }: { recoveryCodes: string[] }) {
    const [codesAreVisible, setCodesAreVisible] = useState(false);
    const canRegenerateCodes = recoveryCodes.length > 0 && codesAreVisible;

    const toggleCodesVisibility = useCallback(() => {
        setCodesAreVisible((visible) => !visible);
    }, []);

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex gap-2">{__('2FA recovery codes')}</CardTitle>
                <CardDescription>
                    {__(
                        'Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.',
                    )}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="flex flex-col gap-3 select-none sm:flex-row sm:items-center sm:justify-between">
                    <Button variant="secondary" className="w-fit" onClick={toggleCodesVisibility}>
                        {codesAreVisible ? (
                            <>
                                <EyeOffIcon className="-ms-0.5 size-3.5" />
                                {__('Hide recovery codes')}
                            </>
                        ) : (
                            <>
                                <EyeIcon className="-ms-0.5 size-3.5" />
                                {__('View recovery codes')}
                            </>
                        )}
                    </Button>

                    {canRegenerateCodes && (
                        <Form {...TwoFactorRecoveryCodesController.store.form()} options={{ preserveScroll: true }}>
                            {({ processing }) => (
                                <SubmitButton variant="outline" processing={processing}>
                                    {__('Regenerate codes')}
                                </SubmitButton>
                            )}
                        </Form>
                    )}
                </div>
                <div
                    className={cn(
                        'relative overflow-hidden transition-all duration-300',
                        codesAreVisible ? 'h-auto opacity-100' : 'h-0 opacity-0',
                    )}
                    aria-hidden={!codesAreVisible}
                >
                    <div className="mt-3 space-y-3">
                        <div
                            className="grid gap-1 rounded-lg bg-muted p-4 font-mono text-sm"
                            role="list"
                            aria-label={__('Recovery codes')}
                        >
                            {recoveryCodes.map((code, index) => (
                                <div key={index} role="listitem" className="select-text">
                                    {code}
                                </div>
                            ))}
                        </div>

                        <div className="text-xs text-muted-foreground select-none">
                            <p id="regenerate-warning">
                                {__nodes(
                                    'Each recovery code can be used once to access your account and will be removed after use. If you need more, click :button above.',
                                    { button: <span className="font-bold">{__('Regenerate codes')}</span> },
                                )}
                            </p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
