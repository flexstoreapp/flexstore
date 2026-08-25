import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';

import { SubmitButton } from '@/components/admin/submit-button';
import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldDescription, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';

interface Driver {
    value: string;
    label: string;
    defaultPort: string;
}

export default function Database({ availableDrivers }: { availableDrivers: Driver[] }) {
    const defaultDriver = availableDrivers[0];
    const [connection, setConnection] = useState(defaultDriver?.value ?? 'mysql');
    const [port, setPort] = useState(defaultDriver?.defaultPort ?? '3306');

    const isSqlite = connection === 'sqlite';

    function handleDriverChange(value: string) {
        setConnection(value);
        const driver = availableDrivers.find((d) => d.value === value);
        if (driver) {
            setPort(driver.defaultPort);
        }
    }

    return (
        <>
            <Head title="Database" />

            <Form
                action="/install/database"
                method="post"
                transform={(data) => ({ ...data, demo_data: data.demo_data === 'on' })}
            >
                {({ errors, processing }) => (
                    <Card>
                        <CardHeader>
                            <CardTitle>Database configuration</CardTitle>
                            <CardDescription>Configure the database connection for your store</CardDescription>
                        </CardHeader>

                        <CardContent className="space-y-4">
                            {errors.db_connection &&
                                !['db_host', 'db_port', 'db_database', 'db_username'].some((k) => k in errors) && (
                                    <div className="rounded-md border border-destructive/20 bg-destructive/5 px-3 py-2 text-sm whitespace-pre-wrap text-destructive">
                                        {errors.db_connection}
                                    </div>
                                )}

                            <Field>
                                <FieldLabel htmlFor="db_connection">Database driver</FieldLabel>
                                <AdaptiveSelect
                                    id="db_connection"
                                    name="db_connection"
                                    options={availableDrivers.map((d) => ({ value: d.value, label: d.label }))}
                                    value={connection}
                                    onValueChange={handleDriverChange}
                                    search={false}
                                />
                            </Field>

                            {!isSqlite && (
                                <>
                                    <div className="grid grid-cols-3 gap-4">
                                        <Field className="col-span-2">
                                            <FieldLabel htmlFor="db_host">Host</FieldLabel>
                                            <Input
                                                id="db_host"
                                                name="db_host"
                                                defaultValue="127.0.0.1"
                                                placeholder="127.0.0.1"
                                            />
                                            <FieldError>{errors.db_host}</FieldError>
                                        </Field>
                                        <Field>
                                            <FieldLabel htmlFor="db_port">Port</FieldLabel>
                                            <Input
                                                id="db_port"
                                                name="db_port"
                                                value={port}
                                                onChange={(e) => setPort(e.target.value)}
                                                placeholder={connection === 'pgsql' ? '5432' : '3306'}
                                            />
                                            <FieldError>{errors.db_port}</FieldError>
                                        </Field>
                                    </div>

                                    <Field>
                                        <FieldLabel htmlFor="db_database">Database name</FieldLabel>
                                        <Input id="db_database" name="db_database" placeholder="flexstore" />
                                        <FieldError>{errors.db_database}</FieldError>
                                    </Field>

                                    <div className="grid grid-cols-2 gap-4">
                                        <Field>
                                            <FieldLabel htmlFor="db_username">Username</FieldLabel>
                                            <Input
                                                id="db_username"
                                                name="db_username"
                                                placeholder={connection === 'pgsql' ? 'postgres' : 'root'}
                                            />
                                            <FieldError>{errors.db_username}</FieldError>
                                        </Field>
                                        <Field>
                                            <FieldLabel htmlFor="db_password">Password</FieldLabel>
                                            <Input id="db_password" name="db_password" type="password" />
                                            <FieldError>{errors.db_password}</FieldError>
                                        </Field>
                                    </div>
                                </>
                            )}

                            {isSqlite && (
                                <Field>
                                    <FieldLabel htmlFor="db_database">Database filename</FieldLabel>
                                    <Input
                                        id="db_database"
                                        name="db_database"
                                        defaultValue="database.sqlite"
                                        placeholder="database.sqlite"
                                    />
                                    <FieldError>{errors.db_database}</FieldError>
                                    <FieldDescription>File will be created in the storage directory.</FieldDescription>
                                </Field>
                            )}

                            <Field className="border-t pt-4">
                                <div className="flex items-start gap-3">
                                    <Checkbox id="demo_data" name="demo_data" className="mt-0.5" />
                                    <div className="grid gap-1">
                                        <FieldLabel htmlFor="demo_data">Install demo data</FieldLabel>
                                        <FieldDescription>
                                            Fills the store with sample products, categories, orders, blog posts and
                                            storefront content so you can explore the admin panel right away.
                                        </FieldDescription>
                                    </div>
                                </div>
                                <FieldError>{errors.demo_data}</FieldError>
                            </Field>
                        </CardContent>

                        <CardFooter className="justify-end border-t">
                            <SubmitButton processing={processing} size="md">
                                Connect & migrate
                            </SubmitButton>
                        </CardFooter>
                    </Card>
                )}
            </Form>
        </>
    );
}

Database.layout = { step: 2 };
