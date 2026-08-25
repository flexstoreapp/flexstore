import { KeyRoundIcon, Trash2Icon } from 'lucide-react';
import { Fragment } from 'react';

import { useConfirm } from '@/components/admin/confirm';
import { HoverActions } from '@/components/admin/hover-actions';
import { Button } from '@/components/ui/button';
import { __ } from '@/lib/i18n';

export interface Passkey {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string | null;
    last_used_at_diff: string | null;
}

interface PasskeyItemProps {
    passkey: Passkey;
    onDelete: (id: number, onFinish: () => void) => void;
}

export function PasskeyItem({ passkey, onDelete }: PasskeyItemProps) {
    const { confirm } = useConfirm();

    const metaParts = [
        passkey.authenticator,
        passkey.created_at_diff && __('Added :time', { time: passkey.created_at_diff }),
        passkey.last_used_at_diff && __('Last used :time', { time: passkey.last_used_at_diff }),
    ].filter(Boolean);

    const handleDelete = () => {
        confirm({
            variant: 'delete',
            title: __('Remove passkey'),
            description: __(
                'Are you sure you want to remove the :name passkey? You will no longer be able to use it to log in.',
                { name: passkey.name },
            ),
            confirmLabel: __('Remove'),
            action: () =>
                new Promise<void>((resolve) => {
                    onDelete(passkey.id, () => resolve());
                }),
        });
    };

    return (
        <div className="group/item flex items-center justify-between gap-4 p-4">
            <div className="flex items-center gap-3">
                <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-muted">
                    <KeyRoundIcon className="size-5 text-muted-foreground" />
                </div>
                <div className="space-y-0.5">
                    <p className="text-sm font-medium">{passkey.name}</p>
                    <p className="flex flex-wrap items-center gap-x-1 text-xs text-muted-foreground">
                        {metaParts.map((part, index) => (
                            <Fragment key={index}>
                                {index > 0 && <span aria-hidden>·</span>}
                                <span>{part}</span>
                            </Fragment>
                        ))}
                    </p>
                </div>
            </div>

            <HoverActions>
                <Button variant="ghost" size="icon-md" onClick={handleDelete}>
                    <Trash2Icon />
                    <span className="sr-only">{__('Remove')}</span>
                </Button>
            </HoverActions>
        </div>
    );
}
