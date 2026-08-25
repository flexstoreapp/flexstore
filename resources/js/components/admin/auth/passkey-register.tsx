import { usePasskeyRegister } from '@laravel/passkeys/react';
import { PlusIcon } from 'lucide-react';
import { useState } from 'react';

import * as PasskeyController from '@/actions/App/Http/Controllers/Admin/PasskeyController';
import * as PasskeyRegistrationOptionController from '@/actions/App/Http/Controllers/Admin/PasskeyRegistrationOptionController';
import { SubmitButton } from '@/components/admin/submit-button';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';

function suggestedName(): string {
    if (typeof navigator === 'undefined') {
        return '';
    }

    const userAgent = navigator.userAgent;
    const platforms = ['iPhone', 'iPad', 'Android', 'Mac', 'Windows'];

    return platforms.find((platform) => userAgent.includes(platform)) ?? '';
}

export function PasskeyRegister({ onRegistered }: { onRegistered: () => void }) {
    const [isOpen, setIsOpen] = useState(false);
    const [name, setName] = useState('');

    const { register, isLoading, error, isSupported } = usePasskeyRegister({
        routes: {
            options: PasskeyRegistrationOptionController.show().url,
            submit: PasskeyController.store().url,
        },
        onSuccess: () => {
            setName('');
            setIsOpen(false);
            onRegistered();
        },
    });

    if (!isSupported) {
        return <p className="text-sm text-muted-foreground">{__('Passkeys are not supported in this browser.')}</p>;
    }

    if (!isOpen) {
        return (
            <Button
                variant="outline"
                onClick={() => {
                    setName(suggestedName());
                    setIsOpen(true);
                }}
            >
                <PlusIcon className="size-4" />
                {__('Add passkey')}
            </Button>
        );
    }

    const handleSubmit = (event: React.SyntheticEvent) => {
        event.preventDefault();
        register(name);
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            <Field>
                <FieldLabel htmlFor="passkey-name">{__('Passkey name')}</FieldLabel>
                <Input
                    id="passkey-name"
                    value={name}
                    onChange={(event) => setName(event.target.value)}
                    placeholder={__('e.g., MacBook Pro, iPhone')}
                    autoFocus
                />
                {error && <FieldError>{error}</FieldError>}
            </Field>

            <div className="flex gap-2">
                <SubmitButton processing={isLoading} disabled={isLoading || name.trim() === ''}>
                    {__('Add passkey')}
                </SubmitButton>
                <Button type="button" variant="outline" onClick={() => setIsOpen(false)} disabled={isLoading}>
                    {__('Cancel')}
                </Button>
            </div>
        </form>
    );
}
