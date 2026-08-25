import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { Category } from '@/types';

interface CategoryGeneralTabContentProps {
    category?: Category;
    errors: Record<string, string>;
    onNameChange: (value: string) => void;
    onDescriptionChange: (value: string) => void;
}

export function CategoryGeneralTabContent({
    category,
    errors,
    onNameChange,
    onDescriptionChange,
}: CategoryGeneralTabContentProps) {
    return (
        <div className="min-w-0 space-y-6">
            <Field>
                <FieldLabel htmlFor="category-name">{__('Category name')}</FieldLabel>
                <Input
                    id="category-name"
                    name="name"
                    defaultValue={getTranslation(category?.name)}
                    onChange={(e) => onNameChange(e.target.value)}
                />
                <FieldError>{errors.name}</FieldError>
            </Field>

            <Field>
                <FieldLabel htmlFor="category-description">{__('Description')}</FieldLabel>
                <Textarea
                    id="category-description"
                    name="description"
                    defaultValue={getTranslation(category?.description)}
                    onChange={(e) => onDescriptionChange(e.target.value)}
                    rows={3}
                    className="max-h-40"
                />
                <FieldError>{errors.description}</FieldError>
            </Field>

            <Field orientation="horizontal">
                <Checkbox id="category-is-active" name="is_active" defaultChecked={category?.is_active ?? true} />
                <FieldLabel htmlFor="category-is-active">{__('Active')}</FieldLabel>
            </Field>
        </div>
    );
}
