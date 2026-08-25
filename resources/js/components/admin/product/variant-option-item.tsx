import { AlertCircleIcon, XIcon } from 'lucide-react';
import React, { memo, useState } from 'react';

import { ExpandableCard } from '@/components/admin/expandable-card';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { TagsInput } from '@/components/ui/tags-input';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { ProductOption } from '@/types';

function getOptionValueTags(option: ProductOption): string[] {
    return option.values?.map((v) => getTranslation(v.value)) || [];
}

interface VariantOptionItemProps {
    option: ProductOption;
    index: number;
    errors: Record<string, string>;
    isExpanded: boolean;
    onToggleExpanded: () => void;
    onRemoveOption: (optionId: string) => void;
    onUpdateOptionName: (optionId: string, name: string) => void;
    onAddOptionValue: (value: string, option: ProductOption) => void;
    onRemoveOptionValue: (index: number, option: ProductOption) => void;
}

const VariantOptionItem = memo(
    ({
        option,
        index,
        errors,
        isExpanded,
        onToggleExpanded,
        onRemoveOption,
        onUpdateOptionName,
        onAddOptionValue,
        onRemoveOptionValue,
    }: VariantOptionItemProps) => {
        const [localName, setLocalName] = useState(() => getTranslation(option.name));

        const handleNameBlur = () => {
            const currentName = getTranslation(option.name);
            if (localName !== currentName) {
                onUpdateOptionName(option.id, localName);
            }
        };

        const handleAddOptionValue = (value: string) => {
            onAddOptionValue(value, option);
        };

        const handleRemoveOptionValue = (valueIndex: number) => {
            onRemoveOptionValue(valueIndex, option);
        };

        const hasErrors = !!errors[`options.${index}.name`] || !!errors[`options.${index}.values`];

        return (
            <ExpandableCard
                isExpanded={isExpanded}
                onToggle={onToggleExpanded}
                title={
                    <>
                        {localName || __('New option')}
                        {hasErrors && <AlertCircleIcon className="ms-1.5 inline text-destructive" size={16} />}
                    </>
                }
                action={
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        onClick={(e) => {
                            e.stopPropagation();
                            onRemoveOption(option.id);
                        }}
                    >
                        <XIcon />
                    </Button>
                }
            >
                <input type="hidden" name={`options.${index}.id`} value={option.id} />

                {option.values?.map((value, valueIndex) => (
                    <React.Fragment key={`${option.id}-${valueIndex}`}>
                        <input type="hidden" name={`options.${index}.values.${valueIndex}.id`} value={value.id} />
                        <input
                            type="hidden"
                            name={`options.${index}.values.${valueIndex}.value`}
                            value={getTranslation(value.value)}
                        />
                    </React.Fragment>
                ))}

                <Field className="max-w-xs">
                    <FieldLabel htmlFor={`option-${option.id}`}>{__('Option name')}</FieldLabel>
                    <Input
                        id={`option-${option.id}`}
                        name={`options.${index}.name`}
                        value={localName}
                        onChange={(e) => setLocalName(e.target.value)}
                        onBlur={handleNameBlur}
                    />
                    <FieldError>{errors[`options.${index}.name`]}</FieldError>
                </Field>

                <Field>
                    <FieldLabel htmlFor={`option-${option.id}-values`}>{__('Option values')}</FieldLabel>
                    <TagsInput
                        id={`option-${option.id}-values`}
                        tags={getOptionValueTags(option)}
                        onAdd={handleAddOptionValue}
                        onRemove={handleRemoveOptionValue}
                    />
                    <FieldError>{errors[`options.${index}.values`]}</FieldError>
                </Field>
            </ExpandableCard>
        );
    },
);

VariantOptionItem.displayName = 'VariantOptionItem';

export { VariantOptionItem };
