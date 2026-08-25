import { router } from '@inertiajs/react';
import { KeyRoundIcon } from 'lucide-react';

import * as PasskeyController from '@/actions/App/Http/Controllers/Admin/PasskeyController';
import { PasskeyItem, type Passkey } from '@/components/admin/auth/passkey-item';
import { PasskeyRegister } from '@/components/admin/auth/passkey-register';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { __ } from '@/lib/i18n';

export function ManagePasskeys({ passkeys }: { passkeys: Passkey[] }) {
    const handleDelete = (id: number, onFinish: () => void) => {
        router.delete(PasskeyController.destroy(id).url, {
            preserveScroll: true,
            onFinish,
        });
    };

    return (
        <div className="space-y-4">
            {passkeys.length > 0 ? (
                <div className="divide-y rounded-lg border">
                    {passkeys.map((passkey) => (
                        <PasskeyItem key={passkey.id} passkey={passkey} onDelete={handleDelete} />
                    ))}
                </div>
            ) : (
                <Empty className="rounded-lg border">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <KeyRoundIcon />
                        </EmptyMedia>
                        <EmptyTitle>{__('No passkeys')}</EmptyTitle>
                        <EmptyDescription>{__('Add a passkey to log in without a password.')}</EmptyDescription>
                    </EmptyHeader>
                </Empty>
            )}

            <PasskeyRegister onRegistered={() => router.reload()} />
        </div>
    );
}
