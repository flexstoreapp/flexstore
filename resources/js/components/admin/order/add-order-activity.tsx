import { Form } from '@inertiajs/react';

import * as OrderActivityController from '@/actions/App/Http/Controllers/Admin/OrderActivityController';
import { SubmitButton } from '@/components/admin/submit-button';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import { FieldError } from '@/components/ui/field';
import { Textarea } from '@/components/ui/textarea';
import { __ } from '@/lib/i18n';
import type { Order } from '@/types';

export function AddOrderActivity({ order }: { order: Order }) {
    return (
        <Form
            {...OrderActivityController.store.form(order)}
            options={{ preserveScroll: true }}
            resetOnSuccess={['comment']}
            setDefaultsOnSuccess
        >
            {({ processing, errors }) => (
                <div className="space-y-3">
                    <UnsavedChangesAlert />
                    <div className="space-y-2">
                        <Textarea
                            name="comment"
                            placeholder={__('Add a note...')}
                            aria-label={__('Order note')}
                            className="max-h-32 min-h-16"
                            required
                            onKeyDown={(e) => {
                                if (e.key === 'Enter' && e.metaKey) {
                                    e.currentTarget.form?.requestSubmit();
                                }
                            }}
                        />
                        <FieldError>{errors.comment}</FieldError>
                    </div>

                    <div className="flex items-start justify-between">
                        <p className="text-xs text-muted-foreground">{__('Customers won’t see this.')}</p>
                        <SubmitButton processing={processing} variant="outline">
                            {__('Add note')}
                        </SubmitButton>
                    </div>
                </div>
            )}
        </Form>
    );
}
