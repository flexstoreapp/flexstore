import { useState } from 'react';

import { ResourcePickerTrigger } from '@/components/admin/resource-picker';
import { UserPicker, type SelectableUser } from '@/components/admin/user-picker';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import type { Order, User } from '@/types';

interface OrderFormCustomerProps {
    order?: Order;
    customerEmail: string;
    onCustomerEmailChange: (customerEmail: string) => void;
    errors: Record<string, string>;
}

function getSelectableCustomer(customer: User | null): SelectableUser | null {
    if (!customer) return null;
    return { id: customer.id, name: customer.name, email: customer.email };
}

export function OrderFormCustomer({ order, customerEmail, onCustomerEmailChange, errors }: OrderFormCustomerProps) {
    const [customerPickerOpen, setCustomerPickerOpen] = useState(false);
    const [customer, setCustomer] = useState<SelectableUser | null>(() =>
        getSelectableCustomer(order?.customer ?? null),
    );

    const handleCustomerChange = (customer: SelectableUser | null) => {
        setCustomer(customer);
        onCustomerEmailChange(customer?.email ?? '');
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>{__('Customer')}</CardTitle>
                <CardDescription>{__('Associate a customer with the order')}</CardDescription>
            </CardHeader>
            <CardContent className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Field>
                    <FieldLabel htmlFor="customer_id">{__('Link to existing customer')}</FieldLabel>
                    <ResourcePickerTrigger
                        id="customer_id"
                        name="customer_id"
                        value={customer?.id}
                        label={customer?.name}
                        placeholder={__('Select a customer')}
                        onOpen={() => setCustomerPickerOpen(true)}
                        onRemove={() => setCustomer(null)}
                    />
                    <UserPicker
                        open={customerPickerOpen}
                        onOpenChange={setCustomerPickerOpen}
                        selectedItems={customer ? [customer] : []}
                        onSelectionChange={handleCustomerChange}
                        customersOnly={true}
                    />
                    <FieldError>{errors.customer_id}</FieldError>
                </Field>

                <Field>
                    <FieldLabel htmlFor="customer_email">{__('Email address')}</FieldLabel>
                    <Input
                        id="customer_email"
                        name="customer_email"
                        type="email"
                        value={customerEmail}
                        onChange={(e) => onCustomerEmailChange(e.target.value)}
                    />
                    <FieldError>{errors.customer_email}</FieldError>
                </Field>
            </CardContent>
        </Card>
    );
}
