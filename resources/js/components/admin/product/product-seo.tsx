import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldDescription, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { __ } from '@/lib/i18n';

interface ProductSeoProps {
    errors: Record<string, string>;
    seoTitle: string;
    seoDescription: string;
    urlHandle: string;
    urlHandleNeedsServerGeneration: boolean;
    onSeoTitleChange: (value: string) => void;
    onSeoDescriptionChange: (value: string) => void;
    onUrlHandleChange: (value: string) => void;
}

export function ProductSeo({
    errors,
    seoTitle,
    seoDescription,
    urlHandle,
    urlHandleNeedsServerGeneration,
    onSeoTitleChange,
    onSeoDescriptionChange,
    onUrlHandleChange,
}: ProductSeoProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{__('SEO')}</CardTitle>
                <CardDescription>{__('Help customers find your product')}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                <Field className="flex flex-col">
                    <FieldLabel htmlFor="seo-title" className="justify-between">
                        {__('SEO title')}

                        <span className="text-xs whitespace-nowrap text-muted-foreground">
                            {`${seoTitle.trim().length} / 70`}
                        </span>
                    </FieldLabel>
                    <Input
                        id="seo-title"
                        name="seo_title"
                        value={seoTitle}
                        onChange={(e) => onSeoTitleChange(e.target.value)}
                        maxLength={70}
                    />
                    <FieldError>{errors.seo_title}</FieldError>
                </Field>

                <Field className="flex flex-col">
                    <FieldLabel htmlFor="seo-description" className="justify-between">
                        {__('SEO description')}

                        <span className="text-xs whitespace-nowrap text-muted-foreground">
                            {`${seoDescription.trim().length} / 160`}
                        </span>
                    </FieldLabel>
                    <Textarea
                        id="seo-description"
                        name="seo_description"
                        value={seoDescription}
                        onChange={(e) => onSeoDescriptionChange(e.target.value)}
                        rows={3}
                        maxLength={160}
                        className="max-h-24"
                    />
                    <FieldError>{errors.seo_description}</FieldError>
                </Field>

                <Field>
                    <FieldLabel htmlFor="url-handle">{__('URL handle')}</FieldLabel>
                    <Input
                        id="url-handle"
                        name="url_handle"
                        value={urlHandle}
                        onChange={(e) => onUrlHandleChange(e.target.value)}
                    />
                    {urlHandleNeedsServerGeneration && (
                        <FieldDescription>{__('URL handle will be generated automatically on save.')}</FieldDescription>
                    )}
                    <FieldError>{errors.url_handle}</FieldError>
                </Field>
            </CardContent>
        </Card>
    );
}
