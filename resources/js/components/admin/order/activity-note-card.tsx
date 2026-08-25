import { Form, router } from '@inertiajs/react';
import { PencilIcon, Trash2Icon } from 'lucide-react';
import { useState } from 'react';

import * as OrderActivityController from '@/actions/App/Http/Controllers/Admin/OrderActivityController';
import { ActivityTimestamp } from '@/components/admin/activity-timestamp';
import { useConfirm } from '@/components/admin/confirm';
import { HoverActions } from '@/components/admin/hover-actions';
import { SubmitButton } from '@/components/admin/submit-button';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import { Button } from '@/components/ui/button';
import { Can } from '@/components/ui/can';
import { FieldError } from '@/components/ui/field';
import { Textarea } from '@/components/ui/textarea';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import type { OrderActivity } from '@/types';

export function ActivityNoteCard({ activity }: { activity: OrderActivity }) {
    const [editing, setEditing] = useState(false);
    const { confirm } = useConfirm();

    const handleDelete = () => {
        confirm({
            variant: 'delete',
            title: __('Are you absolutely sure?'),
            description: __('This will permanently delete this note.'),
            action: () =>
                new Promise<void>((resolve) => {
                    router.delete(
                        OrderActivityController.destroy({ order: activity.order_id, activity: activity.id }),
                        {
                            preserveScroll: true,
                            onFinish: () => resolve(),
                        },
                    );
                }),
        });
    };

    if (editing) {
        return (
            <Form
                {...OrderActivityController.update.form({ order: activity.order_id, activity: activity.id })}
                options={{ preserveScroll: true }}
                setDefaultsOnSuccess
                onSuccess={() => setEditing(false)}
            >
                {({ processing, errors }) => (
                    <>
                        <UnsavedChangesAlert />
                        <Textarea
                            name="comment"
                            defaultValue={activity.comment ?? ''}
                            aria-label={__('Edit note')}
                            className="max-h-32 min-h-16"
                            required
                            autoFocus
                            onKeyDown={(e) => {
                                if (e.key === 'Enter' && e.metaKey) {
                                    e.currentTarget.form?.requestSubmit();
                                }
                                if (e.key === 'Escape') {
                                    setEditing(false);
                                }
                            }}
                        />
                        <FieldError>{errors.comment}</FieldError>

                        <div className="mt-2 flex items-center justify-end gap-2">
                            <Button variant="ghost" size="sm" type="button" onClick={() => setEditing(false)}>
                                {__('Cancel')}
                            </Button>
                            <SubmitButton processing={processing} size="sm" variant="outline">
                                {__('Save')}
                            </SubmitButton>
                        </div>
                    </>
                )}
            </Form>
        );
    }

    return (
        <div className="group/item flex items-start justify-between gap-3">
            <div className="space-y-1.5">
                <p className="pt-0.5 text-sm whitespace-pre-line text-foreground">{activity.comment}</p>
                {activity.user && (
                    <p className="text-xs text-muted-foreground">{__('by :name', { name: activity.user.name })}</p>
                )}
            </div>
            <div className="flex shrink-0 items-start gap-1">
                <HoverActions>
                    <Can permission={Permission.OrdersManage}>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            type="button"
                            onClick={() => setEditing(true)}
                            aria-label={__('Edit note')}
                        >
                            <PencilIcon />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            type="button"
                            onClick={handleDelete}
                            aria-label={__('Delete note')}
                        >
                            <Trash2Icon />
                        </Button>
                    </Can>
                </HoverActions>
                <ActivityTimestamp date={activity.created_at} />
            </div>
        </div>
    );
}
