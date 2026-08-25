import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldError } from '@/components/ui/field';
import { Textarea } from '@/components/ui/textarea';
import { __ } from '@/lib/i18n';
import type { Order } from '@/types';

interface OrderFormNotesProps {
    order?: Order;
    errors: Record<string, string>;
}

export function OrderFormNotes({ order, errors }: OrderFormNotesProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{__('Order notes')}</CardTitle>
                <CardDescription>{__('Special instructions or requests from the customer')}</CardDescription>
            </CardHeader>
            <CardContent>
                <Field>
                    <Textarea name="notes" defaultValue={order?.notes ?? ''} rows={3} className="max-h-32" />
                    <FieldError>{errors.notes}</FieldError>
                </Field>
            </CardContent>
        </Card>
    );
}
