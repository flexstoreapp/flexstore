import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldLabel } from '@/components/ui/field';
import { __ } from '@/lib/i18n';

export function DuplicateProductStatus() {
    return (
        <Field orientation="horizontal">
            <Checkbox id="duplicate-is-active" name="is_active" defaultChecked={false} />
            <FieldLabel htmlFor="duplicate-is-active">{__('Set duplicate as active')}</FieldLabel>
        </Field>
    );
}
