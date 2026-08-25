import { Form, Head } from '@inertiajs/react';

import { SubmitButton } from '@/components/admin/submit-button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldDescription, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';

export default function License({ domain }: { domain: string }) {
    return (
        <>
            <Head title="License" />

            <Form action="/install/license" method="post">
                {({ errors, processing }) => (
                    <Card>
                        <CardHeader>
                            <CardTitle>License</CardTitle>
                            <CardDescription>Activate your license for {domain}</CardDescription>
                        </CardHeader>

                        <CardContent>
                            <Field>
                                <FieldLabel htmlFor="license_key">License key</FieldLabel>
                                <Input id="license_key" name="license_key" autoFocus autoComplete="off" />
                                <FieldDescription>
                                    You received this key by email after your purchase. Activating uses one of your
                                    plan&apos;s domains.
                                </FieldDescription>
                                <FieldError>{errors.license_key}</FieldError>
                            </Field>
                        </CardContent>

                        <CardFooter className="justify-end border-t">
                            <SubmitButton processing={processing} size="md">
                                Activate license
                            </SubmitButton>
                        </CardFooter>
                    </Card>
                )}
            </Form>
        </>
    );
}

License.layout = { step: 4 };
