import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldLabel } from '@/components/ui/field';
import { __ } from '@/lib/i18n';
import type { Product } from '@/types';

interface ProductStatusProps {
    product?: Product;
    errors: Record<string, string>;
}

export function ProductStatus({ product }: ProductStatusProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{__('Status')}</CardTitle>
                <CardDescription>{__('Control product visibility')}</CardDescription>
            </CardHeader>
            <CardContent>
                <Field orientation="horizontal">
                    <Checkbox id="is-active" name="is_active" defaultChecked={product?.is_active ?? true} />
                    <FieldLabel htmlFor="is-active">{__('Active')}</FieldLabel>
                </Field>
            </CardContent>
        </Card>
    );
}
