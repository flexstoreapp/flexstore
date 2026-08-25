import { PlusIcon, XIcon } from 'lucide-react';
import { useState } from 'react';

import { ExpandableCard } from '@/components/admin/expandable-card';
import { FormDirtySignal } from '@/components/admin/form-dirty-signal';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
import { IconPicker } from '@/components/ui/icon-picker';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { InfoStripItem, InfoStripSettings } from '@/types';

interface InfoStripFieldsProps {
    settings?: InfoStripSettings;
    errors: Record<string, string>;
}

type InfoStripItemLocal = Omit<InfoStripItem, 'title' | 'subtitle'> & {
    title: string;
    subtitle: string;
    _id: string;
};

export function InfoStripFields({ settings, errors }: InfoStripFieldsProps) {
    const [items, setItems] = useState<InfoStripItemLocal[]>(
        settings?.items.map((item) => ({
            icon_name: item.icon_name,
            title: getTranslation(item.title),
            subtitle: getTranslation(item.subtitle),
            _id: crypto.randomUUID(),
        })) ?? [{ icon_name: '', title: '', subtitle: '', _id: crypto.randomUUID() }],
    );
    const [expandedIndex, setExpandedIndex] = useState<number | null>(settings ? null : 0);

    const addItem = () => {
        if (items.length < 4) {
            setItems([...items, { icon_name: '', title: '', subtitle: '', _id: crypto.randomUUID() }]);
            setExpandedIndex(items.length);
        }
    };

    const removeItem = (id: string, index: number) => {
        if (items.length > 1) {
            setItems(items.filter((item) => item._id !== id));
            if (expandedIndex === index) {
                setExpandedIndex(null);
            }
        }
    };

    const updateItem = (id: string, updates: Partial<Omit<InfoStripItemLocal, '_id'>>) => {
        setItems(items.map((item) => (item._id === id ? { ...item, ...updates } : item)));
    };

    return (
        <>
            <FormDirtySignal signal={items.map((item) => item._id).join(',')} />

            <FieldSet className="gap-2">
                <FieldLegend variant="label">
                    {__('Items')} ({items.length})
                </FieldLegend>

                {items.map((item, index) => (
                    <div key={item._id}>
                        <input type="hidden" name={`settings.items.${index}.icon_name`} value={item.icon_name} />
                        <input type="hidden" name={`settings.items.${index}.title`} value={item.title} />
                        <input type="hidden" name={`settings.items.${index}.subtitle`} value={item.subtitle} />

                        <ExpandableCard
                            isExpanded={expandedIndex === index}
                            onToggle={() => setExpandedIndex(expandedIndex === index ? null : index)}
                            title={item.title || __('Item :number', { number: index + 1 })}
                            action={
                                items.length > 1 && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            removeItem(item._id, index);
                                        }}
                                    >
                                        <XIcon />
                                    </Button>
                                )
                            }
                        >
                            <Field>
                                <FieldLabel htmlFor={`settings-items-${index}-icon-name`}>{__('Icon')}</FieldLabel>
                                <IconPicker
                                    id={`settings-items-${index}-icon-name`}
                                    value={item.icon_name}
                                    onChange={(icon_name) => updateItem(item._id, { icon_name })}
                                />
                                <FieldError>{errors[`settings.items.${index}.icon_name`]}</FieldError>
                            </Field>

                            <Field>
                                <FieldLabel htmlFor={`settings-items-${index}-title`}>{__('Title')}</FieldLabel>
                                <Input
                                    id={`settings-items-${index}-title`}
                                    value={item.title}
                                    onChange={(e) => updateItem(item._id, { title: e.target.value })}
                                    placeholder={__('Free shipping')}
                                />
                                <FieldError>{errors[`settings.items.${index}.title`]}</FieldError>
                            </Field>

                            <Field>
                                <FieldLabel htmlFor={`settings-items-${index}-subtitle`}>{__('Subtitle')}</FieldLabel>
                                <Input
                                    id={`settings-items-${index}-subtitle`}
                                    value={item.subtitle}
                                    onChange={(e) => updateItem(item._id, { subtitle: e.target.value })}
                                    placeholder={__('On orders over $75')}
                                />
                                <FieldError>{errors[`settings.items.${index}.subtitle`]}</FieldError>
                            </Field>
                        </ExpandableCard>
                    </div>
                ))}

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="w-fit"
                    onClick={addItem}
                    disabled={items.length >= 4}
                >
                    <PlusIcon />
                    {__('Add item')}
                </Button>
            </FieldSet>
        </>
    );
}
