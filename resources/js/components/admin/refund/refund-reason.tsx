import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { __ } from '@/lib/i18n';

interface RefundReasonProps {
    reason: string;
    onReasonChange: (value: string) => void;
}

export function RefundReason({ reason, onReasonChange }: RefundReasonProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{__('Refund reason')}</CardTitle>
                <CardDescription>{__('Provide a reason for this refund')}</CardDescription>
            </CardHeader>
            <CardContent>
                <Textarea
                    id="refund-reason"
                    value={reason}
                    onChange={(e) => onReasonChange(e.target.value)}
                    rows={3}
                    className="max-h-32"
                />
            </CardContent>
        </Card>
    );
}
