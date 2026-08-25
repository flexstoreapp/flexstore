import { Form, Head } from '@inertiajs/react';
import { CheckCircle2Icon } from 'lucide-react';

import { SubmitButton } from '@/components/admin/submit-button';
import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';

export default function Finalize({ appUrl, timezones }: { appUrl: string; timezones: string[] }) {
    const timezoneOptions = timezones.map((tz) => ({ value: tz, label: tz }));

    return (
        <>
            <Head title="Finish installation" />

            <Form action="/install/finalize" method="post">
                {({ errors, processing }) => (
                    <Card>
                        <CardHeader>
                            <CardTitle>Finish installation</CardTitle>
                            <CardDescription>Review and finalize your installation</CardDescription>
                        </CardHeader>

                        <CardContent className="space-y-4">
                            <Field>
                                <FieldLabel htmlFor="store_name">Store name</FieldLabel>
                                <Input id="store_name" name="store_name" autoFocus />
                                <FieldError>{errors.store_name}</FieldError>
                            </Field>

                            <Field>
                                <FieldLabel htmlFor="timezone">Timezone</FieldLabel>
                                <AdaptiveSelect
                                    id="timezone"
                                    name="timezone"
                                    options={timezoneOptions}
                                    defaultValue="UTC"
                                    placeholder="Select a timezone"
                                    searchPlaceholder="Search timezones..."
                                />
                                <FieldError>{errors.timezone}</FieldError>
                            </Field>

                            <div className="rounded-lg border bg-muted/50 p-4">
                                <h3 className="mb-3 text-sm font-medium">The following actions will be performed:</h3>
                                <ul className="space-y-2.5">
                                    {[
                                        'Set store name',
                                        'Set application to production mode',
                                        'Disable debug mode',
                                        `Set application URL to ${appUrl}`,
                                        'Create public storage symlink',
                                        'Cache configuration, routes, and views',
                                    ].map((item) => (
                                        <li key={item} className="flex items-start gap-2 text-sm text-muted-foreground">
                                            <CheckCircle2Icon className="mt-0.5 size-3.5 shrink-0 text-emerald-700" />
                                            {item}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </CardContent>

                        <CardFooter className="justify-end border-t">
                            <SubmitButton processing={processing} size="md">
                                Complete installation
                            </SubmitButton>
                        </CardFooter>
                    </Card>
                )}
            </Form>
        </>
    );
}

Finalize.layout = { step: 5 };
