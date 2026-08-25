import { Field, FieldDescription, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { __ } from '@/lib/i18n';

interface SeoTabContentProps {
    idPrefix: string;
    errors: Record<string, string>;
    urlHandle: string;
    urlHandleNeedsServerGeneration: boolean;
    seoTitle: string;
    seoDescription: string;
    onUrlHandleChange: (value: string) => void;
    onSeoTitleChange: (value: string) => void;
    onSeoDescriptionChange: (value: string) => void;
}

export function SeoTabContent({
    idPrefix,
    errors,
    urlHandle,
    urlHandleNeedsServerGeneration,
    seoTitle,
    seoDescription,
    onUrlHandleChange,
    onSeoTitleChange,
    onSeoDescriptionChange,
}: SeoTabContentProps) {
    return (
        <div className="space-y-6">
            <Field>
                <FieldLabel htmlFor={`${idPrefix}-url-handle`}>{__('URL handle')}</FieldLabel>
                <Input
                    id={`${idPrefix}-url-handle`}
                    name="url_handle"
                    value={urlHandle}
                    onChange={(e) => onUrlHandleChange(e.target.value)}
                />
                {urlHandleNeedsServerGeneration && (
                    <FieldDescription>{__('URL handle will be generated automatically on save.')}</FieldDescription>
                )}
                <FieldError>{errors.url_handle}</FieldError>
            </Field>

            <Field>
                <FieldLabel htmlFor={`${idPrefix}-seo-title`} className="justify-between">
                    {__('SEO title')}

                    <span className="text-xs whitespace-nowrap text-muted-foreground">
                        {`${seoTitle.trim().length} / 70`}
                    </span>
                </FieldLabel>
                <Input
                    id={`${idPrefix}-seo-title`}
                    name="seo_title"
                    value={seoTitle}
                    onChange={(e) => onSeoTitleChange(e.target.value)}
                    maxLength={70}
                />
                <FieldError>{errors.seo_title}</FieldError>
            </Field>

            <Field>
                <FieldLabel htmlFor={`${idPrefix}-seo-description`} className="justify-between">
                    {__('SEO description')}

                    <span className="text-xs whitespace-nowrap text-muted-foreground">
                        {`${seoDescription.trim().length} / 160`}
                    </span>
                </FieldLabel>
                <Textarea
                    id={`${idPrefix}-seo-description`}
                    name="seo_description"
                    value={seoDescription}
                    onChange={(e) => onSeoDescriptionChange(e.target.value)}
                    rows={3}
                    maxLength={160}
                    className="max-h-24"
                />
                <FieldError>{errors.seo_description}</FieldError>
            </Field>
        </div>
    );
}
