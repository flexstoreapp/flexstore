import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { __ } from '@/lib/i18n';

export function OrderNotes({ notes }: { notes: string }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{__('Order notes')}</CardTitle>
                <CardDescription>{__('Special instructions or requests from the customer')}</CardDescription>
            </CardHeader>
            <CardContent>
                <div className="text-sm tracking-wide">{notes}</div>
            </CardContent>
        </Card>
    );
}
