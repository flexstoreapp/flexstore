import { Form } from '@inertiajs/react';
import { lazy, Suspense, useState } from 'react';

import * as StorefrontController from '@/actions/App/Http/Controllers/Admin/StorefrontController';
import * as StorefrontCustomCssController from '@/actions/App/Http/Controllers/Admin/StorefrontCustomCssController';
import { FormSubmit } from '@/components/admin/form-submit';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import { Can } from '@/components/ui/can';
import { Field, FieldError } from '@/components/ui/field';
import { Skeleton } from '@/components/ui/skeleton';
import { useStorefrontBuilder } from '@/layouts/admin/storefront-builder-layout';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';

const CodeEditor = lazy(() => import('@/components/ui/code-editor').then((m) => ({ default: m.CodeEditor })));

export default function CustomCss({ customCss }: { customCss: string }) {
    const [css, setCss] = useState(customCss);
    const { reloadIframe } = useStorefrontBuilder();

    return (
        <Form
            {...StorefrontCustomCssController.update.form()}
            options={{ preserveScroll: true }}
            setDefaultsOnSuccess
            onSuccess={() => reloadIframe()}
        >
            {({ processing, recentlySuccessful, errors }) => (
                <div className="mb-6 space-y-4 p-4 text-sm">
                    <UnsavedChangesAlert />
                    <p className="text-muted-foreground">
                        {__('Add custom CSS styles to your storefront. These styles will be applied globally.')}
                    </p>

                    <Suspense fallback={<Skeleton className="h-100 w-full rounded-md" />}>
                        <Can
                            permission={Permission.StorefrontUpdate}
                            fallback={
                                <CodeEditor
                                    value={customCss}
                                    placeholder={`.my-class {\n  color: #333;\n  font-size: 16px;\n}`}
                                    language="css"
                                    readOnly
                                />
                            }
                        >
                            <Field>
                                <CodeEditor
                                    name="storefront_custom_css"
                                    value={css}
                                    onChange={setCss}
                                    placeholder={`.my-class {\n  color: #333;\n  font-size: 16px;\n}`}
                                    language="css"
                                    disabled={processing}
                                />
                                <FieldError>{errors.storefront_custom_css}</FieldError>
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

CustomCss.layout = {
    title: __('Custom CSS'),
    backHref: StorefrontController.index(),
};
