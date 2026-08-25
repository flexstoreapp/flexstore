import { Head } from '@inertiajs/react';
import { CheckCircle2Icon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export default function Done({ storefrontUrl, adminUrl }: { storefrontUrl: string; adminUrl: string }) {
    return (
        <>
            <Head title="Installation complete" />

            <Card>
                <CardHeader className="items-center text-center">
                    <CheckCircle2Icon className="mx-auto size-8 text-emerald-700" />
                    <CardTitle>Installation complete</CardTitle>
                    <CardDescription>Your store is ready. Where would you like to go?</CardDescription>
                </CardHeader>

                <CardContent className="grid gap-3 sm:grid-cols-2">
                    <Button asChild variant="outline" size="md">
                        <a href={storefrontUrl}>Visit storefront</a>
                    </Button>
                    <Button asChild size="md">
                        <a href={adminUrl}>Go to admin panel</a>
                    </Button>
                </CardContent>
            </Card>
        </>
    );
}

Done.layout = { step: 6 };
