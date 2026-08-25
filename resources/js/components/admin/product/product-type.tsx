import { ReactiveHiddenInput } from '@/components/admin/reactive-hidden-input';
import type { AdaptiveSelectOption } from '@/components/ui/adaptive-select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { RadioCard, RadioCardGroup } from '@/components/ui/radio-card';
import { __ } from '@/lib/i18n';
import type { ProductType } from '@/types';

const TYPE_DESCRIPTIONS: Record<ProductType, string> = {
    physical: __('A tangible item that is shipped to the customer'),
    digital: __('A downloadable file delivered to the customer'),
};

interface ProductTypeSelectProps {
    value: ProductType;
    onChange: (value: ProductType) => void;
    productTypes: AdaptiveSelectOption[];
}

export function ProductTypeSelect({ value, onChange, productTypes }: ProductTypeSelectProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{__('Product type')}</CardTitle>
                <CardDescription>{__('Choose how this product is fulfilled')}</CardDescription>
            </CardHeader>
            <CardContent>
                <ReactiveHiddenInput name="type" value={value} />

                <RadioCardGroup
                    value={value}
                    onValueChange={(next) => onChange(next as ProductType)}
                    aria-label={__('Product type')}
                >
                    {productTypes.map((option) => (
                        <RadioCard
                            key={option.value}
                            value={option.value}
                            label={option.label}
                            description={TYPE_DESCRIPTIONS[option.value as ProductType] ?? ''}
                        />
                    ))}
                </RadioCardGroup>
            </CardContent>
        </Card>
    );
}
