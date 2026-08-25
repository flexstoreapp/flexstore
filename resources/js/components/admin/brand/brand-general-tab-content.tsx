import { InlineImageUploader } from '@/components/admin/inline-image-uploader';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { Brand } from '@/types';

interface BrandGeneralTabContentProps {
    brand?: Brand;
    errors: Record<string, string>;
    onNameChange: (value: string) => void;
    onDescriptionChange: (value: string) => void;
}

export function BrandGeneralTabContent({
    brand,
    errors,
    onNameChange,
    onDescriptionChange,
}: BrandGeneralTabContentProps) {
    return (
        <div className="space-y-6">
            <Field>
                <FieldLabel htmlFor="brand-name">{__('Brand name')}</FieldLabel>
                <Input
                    id="brand-name"
                    name="name"
                    defaultValue={getTranslation(brand?.name)}
                    onChange={(e) => onNameChange(e.target.value)}
                />
                <FieldError>{errors.name}</FieldError>
            </Field>

            <Field>
                <FieldLabel htmlFor="brand-description">{__('Brand description')}</FieldLabel>
                <Textarea
                    id="brand-description"
                    name="description"
                    defaultValue={getTranslation(brand?.description)}
                    onChange={(e) => onDescriptionChange(e.target.value)}
                    rows={3}
                    className="max-h-40"
                />
                <FieldError>{errors.description}</FieldError>
            </Field>

            <Field>
                <FieldLabel htmlFor="brand-image">{__('Brand logo')}</FieldLabel>
                <InlineImageUploader
                    id="brand-image"
                    name="image_id"
                    defaultValue={brand?.image ?? null}
                    size="md"
                    aspectRatio="square"
                    label={__('Brand logo')}
                    error={errors.image_id}
                />
            </Field>

            <Field orientation="horizontal">
                <Checkbox id="brand-is-active" name="is_active" defaultChecked={brand?.is_active ?? true} />
                <FieldLabel htmlFor="brand-is-active">{__('Active')}</FieldLabel>
            </Field>
        </div>
    );
}
