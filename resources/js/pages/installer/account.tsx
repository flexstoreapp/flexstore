import { Form, Head } from '@inertiajs/react';

import { SubmitButton } from '@/components/admin/submit-button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';

export default function Account() {
    return (
        <>
            <Head title="Admin account" />

            <Form action="/install/account" method="post">
                {({ errors, processing }) => (
                    <Card>
                        <CardHeader>
                            <CardTitle>Admin account</CardTitle>
                            <CardDescription>Create the administrator account for your store</CardDescription>
                        </CardHeader>

                        <CardContent className="space-y-4">
                            <Field>
                                <FieldLabel htmlFor="name">Name</FieldLabel>
                                <Input id="name" name="name" autoFocus />
                                <FieldError>{errors.name}</FieldError>
                            </Field>

                            <Field>
                                <FieldLabel htmlFor="email">Email address</FieldLabel>
                                <Input id="email" name="email" type="text" />
                                <FieldError>{errors.email}</FieldError>
                            </Field>

                            <Field>
                                <div className="grid grid-cols-2 gap-4">
                                    <Field>
                                        <FieldLabel htmlFor="password">Password</FieldLabel>
                                        <Input id="password" name="password" type="password" />
                                    </Field>
                                    <Field>
                                        <FieldLabel htmlFor="password_confirmation">Confirm password</FieldLabel>
                                        <Input
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            type="password"
                                        />
                                    </Field>
                                </div>
                                <FieldError>{errors.password}</FieldError>
                            </Field>
                        </CardContent>

                        <CardFooter className="justify-end border-t">
                            <SubmitButton processing={processing} size="md">
                                Create account
                            </SubmitButton>
                        </CardFooter>
                    </Card>
                )}
            </Form>
        </>
    );
}

Account.layout = { step: 3 };
