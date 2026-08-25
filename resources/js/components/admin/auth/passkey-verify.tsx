import { usePasskeyVerify } from '@laravel/passkeys/react';
import { FingerprintIcon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { __ } from '@/lib/i18n';

interface PasskeyVerifyProps {
    options: string;
    submit: string;
    label: string;
}

export function PasskeyVerify({ options, submit, label }: PasskeyVerifyProps) {
    const { verify, isLoading, error, isSupported } = usePasskeyVerify({
        routes: { options, submit },
        onSuccess: (response) => {
            if (response?.redirect) {
                window.location.href = response.redirect;
            }
        },
    });

    if (!isSupported) {
        return null;
    }

    return (
        <>
            <div className="my-6 flex items-center gap-3">
                <Separator className="flex-1" />
                <span className="text-xs text-muted-foreground">{__('Or')}</span>
                <Separator className="flex-1" />
            </div>

            <div className="space-y-2">
                <Button
                    type="button"
                    variant="outline"
                    className="w-full"
                    onClick={() => verify()}
                    disabled={isLoading}
                >
                    <FingerprintIcon className="size-4" />
                    {isLoading ? __('Verifying...') : label}
                </Button>
                {error && <p className="text-center text-sm text-destructive">{error}</p>}
            </div>
        </>
    );
}
