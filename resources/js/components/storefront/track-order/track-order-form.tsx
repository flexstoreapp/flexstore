import { Form } from '@inertiajs/react';

import * as TrackOrderController from '@/actions/App/Http/Controllers/Storefront/TrackOrderController';
import { Button } from '@/components/storefront/button';
import { TextField } from '@/components/storefront/text-field';
import { __ } from '@/lib/i18n';

interface TrackOrderFormProps {
    defaults?: { orderNumber: string; email: string };
}

export function TrackOrderForm({ defaults }: TrackOrderFormProps) {
    return (
        <Form
            {...TrackOrderController.store.form()}
            options={{ preserveScroll: true }}
            className="rounded-md border border-line bg-surface p-6 lg:p-7"
        >
            {({ processing, errors }) => (
                <>
                    <p className="mt-0 mb-0 text-muted">
                        {__('Enter your order number and the email used at checkout to see the latest status.')}
                    </p>

                    <div className="mt-5 grid grid-cols-1 items-start gap-4 sm:grid-cols-2 md:grid-cols-[1fr_1fr_auto]">
                        <TextField
                            id="order_number"
                            name="order_number"
                            label={__('Order number')}
                            inputMode="numeric"
                            placeholder="1024"
                            defaultValue={defaults?.orderNumber}
                            error={errors.order_number}
                        />
                        <TextField
                            id="email"
                            name="email"
                            label={__('Email address')}
                            type="email"
                            inputMode="email"
                            autoComplete="email"
                            placeholder="you@example.com"
                            defaultValue={defaults?.email}
                            error={errors.email}
                        />
                        <div className="sm:col-span-2 md:col-span-1">
                            <span aria-hidden="true" className="mb-2 hidden text-sm font-semibold md:block">
                                &nbsp;
                            </span>
                            <Button type="submit" size="md" processing={processing} className="w-full">
                                {__('Track order')}
                            </Button>
                        </div>
                    </div>
                </>
            )}
        </Form>
    );
}
