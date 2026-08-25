import { Form } from '@inertiajs/react';
import { lazy, Suspense, useState } from 'react';

import * as StorefrontController from '@/actions/App/Http/Controllers/Admin/StorefrontController';
import * as StorefrontCustomJsController from '@/actions/App/Http/Controllers/Admin/StorefrontCustomJsController';
import { FormSubmit } from '@/components/admin/form-submit';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import { Can } from '@/components/ui/can';
import { Field, FieldError } from '@/components/ui/field';
import { Skeleton } from '@/components/ui/skeleton';
import { useStorefrontBuilder } from '@/layouts/admin/storefront-builder-layout';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';

const CodeEditor = lazy(() => import('@/components/ui/code-editor').then((m) => ({ default: m.CodeEditor })));

export default function CustomJs({ customJs }: { customJs: string }) {
    const [js, setJs] = useState(customJs);
    const { reloadIframe } = useStorefrontBuilder();

    return (
        <Form
            {...StorefrontCustomJsController.update.form()}
            options={{ preserveScroll: true }}
            setDefaultsOnSuccess
            onSuccess={() => reloadIframe()}
        >
            {({ processing, recentlySuccessful, errors }) => (
                <div className="mb-6 space-y-4 p-4 text-sm">
                    <UnsavedChangesAlert />
                    <p className="text-muted-foreground">
                        {__('Add custom JavaScript code to your storefront. This code will run on every page.')}
                    </p>

                    <Suspense fallback={<Skeleton className="h-100 w-full rounded-md" />}>
                        <Can
                            permission={Permission.StorefrontUpdate}
                            fallback={
                                <CodeEditor
                                    value={customJs}
                                    placeholder={`console.log('Hello World!');`}
                                    language="js"
                                    readOnly
                                />
                            }
                        >
                            <Field>
                                <CodeEditor
                                    name="storefront_custom_js"
                                    value={js}
                                    onChange={setJs}
                                    placeholder={`console.log('Hello World!');`}
                                    language="js"
                                />
                                <FieldError>{errors.storefront_custom_js}</FieldError>
                            </Field>

                            <FormSubmit
                                className="mt-6"
                                processing={processing}
                                recentlySuccessful={recentlySuccessful}
                            >
                                {__('Save changes')}
                            </FormSubmit>
                        </Can>
                    </Suspense>
                </div>
            )}
        </Form>
    );
}

CustomJs.layout = {
    title: __('Custom JavaScript'),
    backHref: StorefrontController.index(),
};
