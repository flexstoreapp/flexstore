import { AlertCircleIcon } from 'lucide-react';

import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { __ } from '@/lib/i18n';

interface AlertErrorProps {
    errors: string[];
    title?: string;
}

export function AlertError({ errors, title }: AlertErrorProps) {
    return (
        <Alert variant="destructive">
            <AlertCircleIcon />
            <AlertTitle>{title || __('Something went wrong.')}</AlertTitle>
            <AlertDescription>
                <ul className="list-inside list-disc text-sm">
                    {Array.from(new Set(errors)).map((error, index) => (
                        <li key={index}>{error}</li>
                    ))}
                </ul>
            </AlertDescription>
        </Alert>
    );
}
