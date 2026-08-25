import { useState } from 'react';

import { Field, FieldDescription, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { useUrlHandleField } from '@/hooks/admin/use-url-handle-field';
import { __ } from '@/lib/i18n';
import { slugify } from '@/lib/utils';

interface DuplicateProductBasicInfoProps {
    productTitle: string;
    errors: Record<string, string>;
}

export function DuplicateProductBasicInfo({ productTitle, errors }: DuplicateProductBasicInfoProps) {
    const [title, setTitle] = useState(productTitle);
    const [prevProductTitle, setPrevProductTitle] = useState(productTitle);
    const {
        urlHandle,
        onSourceChange,
        onUrlHandleChange,
        reset: resetUrlHandle,
        needsServerGeneration,
    } = useUrlHandleField({ initial: slugify(productTitle.trim()) });

    if (productTitle !== prevProductTitle) {
        setPrevProductTitle(productTitle);
        setTitle(productTitle);
        resetUrlHandle(slugify(productTitle.trim()), productTitle.trim());
    }

    const handleTitleChange = (newTitle: string) => {
        setTitle(newTitle);
        if (newTitle.trim()) {
            onSourceChange(newTitle.trim());
        }
    };

    return (
        <>
            <Field>
                <FieldLabel htmlFor="duplicate-title">{__('Title')}</FieldLabel>
                <Input
                    id="duplicate-title"
                    name="title"
                    type="text"
                    value={title}
                    onChange={(e) => handleTitleChange(e.target.value)}
                    required
                />
                <FieldError>{errors.title}</FieldError>
            </Field>

            <Field>
                <FieldLabel htmlFor="duplicate-url-handle">{__('URL handle')}</FieldLabel>
                <Input
                    id="duplicate-url-handle"
                    name="url_handle"
                    type="text"
                    value={urlHandle}
                    onChange={(e) => onUrlHandleChange(e.target.value)}
                />
                {needsServerGeneration && (
                    <FieldDescription>{__('URL handle will be generated automatically on save.')}</FieldDescription>
                )}
                <FieldError>{errors.url_handle}</FieldError>
            </Field>
        </>
    );
}
