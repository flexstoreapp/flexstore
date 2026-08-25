import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { InputGroup } from '@/components/ui/input-group';
import { useFormatMoney } from '@/hooks/use-format-money';
import { useMoneyInputProps } from '@/hooks/use-money-input';
import { WEIGHT_UNIT_OPTIONS } from '@/lib/constants';
import { __ } from '@/lib/i18n';

interface ConditionsTabContentProps {
    entity?: {
        min_order_value?: string | number | null;
        max_order_value?: string | number | null;
        min_weight?: string | number | null;
        max_weight?: string | number | null;
        min_weight_unit?: string | null;
        max_weight_unit?: string | null;
    };
    errors: Record<string, string>;
    showOrderConditions?: boolean;
    showWeightConditions?: boolean;
}

export function ConditionsTabContent({
    entity,
    errors,
    showOrderConditions = true,
    showWeightConditions = true,
}: ConditionsTabContentProps) {
    const { currencyFormat } = useFormatMoney();
    const moneyInputProps = useMoneyInputProps();

    return (
        <div className="mt-4 grid grid-cols-2 space-y-6 gap-x-4">
            {showOrderConditions && (
                <>
                    <Field>
                        <FieldLabel htmlFor="min-order-value">{__('Min order value')}</FieldLabel>
                        <InputGroup.Root>
                            <InputGroup.Prefix>{currencyFormat.symbol}</InputGroup.Prefix>
                            <InputGroup.Control
                                {...moneyInputProps}
                                id="min-order-value"
                                name="min_order_value"
                                defaultValue={entity?.min_order_value ?? ''}
                            />
                        </InputGroup.Root>
                        <FieldError>{errors.min_order_value}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel htmlFor="max-order-value">{__('Max order value')}</FieldLabel>
                        <InputGroup.Root>
                            <InputGroup.Prefix>{currencyFormat.symbol}</InputGroup.Prefix>
                            <InputGroup.Control
                                {...moneyInputProps}
                                id="max-order-value"
                                name="max_order_value"
                                defaultValue={entity?.max_order_value ?? ''}
                            />
                        </InputGroup.Root>
                        <FieldError>{errors.max_order_value}</FieldError>
                    </Field>
                </>
            )}

            {showWeightConditions && (
                <>
                    <Field>
                        <FieldLabel htmlFor="min-weight">{__('Min weight')}</FieldLabel>
                        <InputGroup.Root>
                            <InputGroup.Control
                                id="min-weight"
                                name="min_weight"
                                type="number"
                                inputMode="decimal"
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                                defaultValue={entity?.min_weight ?? ''}
                            />
                            <InputGroup.Select
                                name="min_weight_unit"
                                defaultValue={entity?.min_weight_unit ?? 'kg'}
                                options={WEIGHT_UNIT_OPTIONS}
                            />
                        </InputGroup.Root>
                        <FieldError>{errors.min_weight}</FieldError>
                        <FieldError>{errors.min_weight_unit}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel htmlFor="max-weight">{__('Max weight')}</FieldLabel>
                        <InputGroup.Root>
                            <InputGroup.Control
                                id="max-weight"
                                name="max_weight"
                                type="number"
                                inputMode="decimal"
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                                defaultValue={entity?.max_weight ?? ''}
                            />
                            <InputGroup.Select
                                name="max_weight_unit"
                                defaultValue={entity?.max_weight_unit ?? 'kg'}
                                options={WEIGHT_UNIT_OPTIONS}
                            />
                        </InputGroup.Root>
                        <FieldError>{errors.max_weight}</FieldError>
                        <FieldError>{errors.max_weight_unit}</FieldError>
                    </Field>
                </>
            )}
        </div>
    );
}
