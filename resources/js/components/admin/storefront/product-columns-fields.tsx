import { PlusIcon, XIcon } from 'lucide-react';
import { useState } from 'react';

import { ExpandableCard } from '@/components/admin/expandable-card';
import { FormDirtySignal } from '@/components/admin/form-dirty-signal';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { ProductColumn, ProductColumnsSettings } from '@/types';

import { ProductSourceFields } from './product-source-fields';

interface ProductColumnsFieldsProps {
    settings?: ProductColumnsSettings;
    errors: Record<string, string>;
}

interface ColumnLocal {
    _id: string;
    heading: string;
    source?: ProductColumn;
}

const MAX_COLUMNS = 3;

export function ProductColumnsFields({ settings, errors }: ProductColumnsFieldsProps) {
    const [columns, setColumns] = useState<ColumnLocal[]>(
        settings?.columns.map((column) => ({
            _id: crypto.randomUUID(),
            heading: getTranslation(column.heading),
            source: column,
        })) ?? [{ _id: crypto.randomUUID(), heading: '' }],
    );
    const [expandedIndex, setExpandedIndex] = useState<number | null>(settings ? null : 0);

    const addColumn = () => {
        if (columns.length < MAX_COLUMNS) {
            setColumns([...columns, { _id: crypto.randomUUID(), heading: '' }]);
            setExpandedIndex(columns.length);
        }
    };

    const removeColumn = (id: string, index: number) => {
        if (columns.length > 1) {
            setColumns(columns.filter((column) => column._id !== id));
            if (expandedIndex === index) {
                setExpandedIndex(null);
            }
        }
    };

    const updateHeading = (id: string, heading: string) => {
        setColumns(columns.map((column) => (column._id === id ? { ...column, heading } : column)));
    };

    return (
        <>
            <FormDirtySignal signal={columns.map((column) => column._id).join(',')} />

            <FieldSet className="gap-2">
                <FieldLegend variant="label">
                    {__('Columns')} ({columns.length})
                </FieldLegend>

                {columns.map((column, index) => (
                    <ExpandableCard
                        key={column._id}
                        isExpanded={expandedIndex === index}
                        onToggle={() => setExpandedIndex(expandedIndex === index ? null : index)}
                        title={column.heading || __('Column :number', { number: index + 1 })}
                        action={
                            columns.length > 1 && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        removeColumn(column._id, index);
                                    }}
                                >
                                    <XIcon />
                                </Button>
                            )
                        }
                    >
                        <input type="hidden" name={`settings.columns.${index}.heading`} value={column.heading} />

                        <Field>
                            <FieldLabel htmlFor={`settings-columns-${index}-heading`}>{__('Heading')}</FieldLabel>
                            <Input
                                id={`settings-columns-${index}-heading`}
                                value={column.heading}
                                onChange={(e) => updateHeading(column._id, e.target.value)}
                                placeholder={__('Top rated')}
                            />
                            <FieldError>{errors[`settings.columns.${index}.heading`]}</FieldError>
                        </Field>

                        <ProductSourceFields
                            prefix={`settings.columns.${index}`}
                            settings={column.source}
                            errors={errors}
                            limitMax={20}
                        />
                    </ExpandableCard>
                ))}

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="w-fit"
                    onClick={addColumn}
                    disabled={columns.length >= MAX_COLUMNS}
                >
                    <PlusIcon />
                    {__('Add column')}
                </Button>
            </FieldSet>
        </>
    );
}
