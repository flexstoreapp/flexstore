import { ProBadge } from '@/components/admin/pro/pro-badge';
import { useProUpgrade } from '@/components/admin/pro/pro-upgrade-context';
import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldLabel } from '@/components/ui/field';
import { __ } from '@/lib/i18n';

export function ProductShopping() {
    const { open: openProUpgrade } = useProUpgrade();

    return (
        <Card className="cursor-pointer" onClick={() => openProUpgrade(__('Catalog feeds'))} role="presentation">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    {__('Shopping')}
                    <ProBadge />
                </CardTitle>
                <CardDescription>{__('Shopping attributes for Google and Meta catalogs')}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6 opacity-60">
                <Field orientation="horizontal">
                    <Checkbox id="include-in-catalog" checked disabled />
                    <FieldLabel htmlFor="include-in-catalog">{__('Include in catalogs')}</FieldLabel>
                </Field>

                <Field>
                    <FieldLabel htmlFor="condition">{__('Condition')}</FieldLabel>
                    <AdaptiveSelect
                        id="condition"
                        disabled
                        value="new"
                        options={[{ value: 'new', label: __('New') }]}
                    />
                </Field>

                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <Field>
                        <FieldLabel htmlFor="age-group">{__('Age group')}</FieldLabel>
                        <AdaptiveSelect
                            id="age-group"
                            disabled
                            value=""
                            placeholder={__('Select an option')}
                            options={[{ value: '', label: __('None') }]}
                        />
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="gender">{__('Gender')}</FieldLabel>
                        <AdaptiveSelect
                            id="gender"
                            disabled
                            value=""
                            placeholder={__('Select an option')}
                            options={[{ value: '', label: __('None') }]}
                        />
                    </Field>
                </div>

                <Field orientation="horizontal">
                    <Checkbox id="is-adult" checked={false} disabled />
                    <FieldLabel htmlFor="is-adult">{__('Adult')}</FieldLabel>
                </Field>
            </CardContent>
        </Card>
    );
}
