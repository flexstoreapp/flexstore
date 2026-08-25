import { PlusIcon } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { ProductOption } from '@/types';

import { VariantOptionItem } from './variant-option-item';

interface VariantOptionsProps {
    options: ProductOption[];
    errors: Record<string, string>;
    onAddOption: () => string;
    onRemoveOption: (optionId: string) => void;
    onUpdateOptionName: (optionId: string, name: string) => void;
    onAddOptionValue: (value: string, option: ProductOption) => void;
    onRemoveOptionValue: (index: number, option: ProductOption) => void;
}

export function VariantOptions({
    options,
    errors,
    onAddOption,
    onRemoveOption,
    onUpdateOptionName,
    onAddOptionValue,
    onRemoveOptionValue,
}: VariantOptionsProps) {
    const [expandedOptionId, setExpandedOptionId] = useState<string | null>(() => {
        if (options.length === 1 && !getTranslation(options[0].name)) {
            return options[0].id;
        }
        return null;
    });

    const handleToggleExpanded = (optionId: string) => {
        setExpandedOptionId(expandedOptionId === optionId ? null : optionId);
    };

    const handleAddOption = () => {
        const newOptionId = onAddOption();
        setExpandedOptionId(newOptionId);
    };

    return (
        <div className="space-y-3">
            {options.map((option, index) => (
                <VariantOptionItem
                    key={option.id}
                    option={option}
                    index={index}
                    errors={errors}
                    isExpanded={expandedOptionId === option.id}
                    onToggleExpanded={() => handleToggleExpanded(option.id)}
                    onRemoveOption={onRemoveOption}
                    onUpdateOptionName={onUpdateOptionName}
                    onAddOptionValue={onAddOptionValue}
                    onRemoveOptionValue={onRemoveOptionValue}
                />
            ))}

            <Button type="button" variant="outline" onClick={handleAddOption}>
                <PlusIcon className="-ms-0.5 size-3.5" />
                {__('Add another option')}
            </Button>
        </div>
    );
}
