import { PlusIcon, XIcon } from 'lucide-react';
import { useState } from 'react';

import { ExpandableCard } from '@/components/admin/expandable-card';
import { FormDirtySignal } from '@/components/admin/form-dirty-signal';
import { ProductSourceFields } from '@/components/admin/storefront/product-source-fields';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { ProductTab, ProductTabsSettings } from '@/types';

interface ProductTabsFieldsProps {
    settings?: ProductTabsSettings;
    errors: Record<string, string>;
}

type ProductTabLocal = ProductTab & {
    _id: string;
    labelText: string;
};

export function ProductTabsFields({ settings, errors }: ProductTabsFieldsProps) {
    const [tabs, setTabs] = useState<ProductTabLocal[]>(
        settings?.tabs.map((tab) => ({
            ...tab,
            labelText: getTranslation(tab.label),
            _id: crypto.randomUUID(),
        })) ?? [],
    );
    const [expandedIndex, setExpandedIndex] = useState<number | null>(settings ? null : 0);

    const addTab = () => {
        if (tabs.length < 5) {
            setTabs([
                ...tabs,
                {
                    label: {},
                    labelText: '',
                    product_source: 'latest',
                    product_limit: 8,
                    category_id: null,
                    brand_id: null,
                    product_ids: null,
                    _id: crypto.randomUUID(),
                },
            ]);
            setExpandedIndex(tabs.length);
        }
    };

    const removeTab = (id: string, index: number) => {
        setTabs(tabs.filter((tab) => tab._id !== id));
        if (expandedIndex === index) {
            setExpandedIndex(null);
        }
    };

    const updateLabel = (id: string, labelText: string) => {
        setTabs(tabs.map((tab) => (tab._id === id ? { ...tab, labelText } : tab)));
    };

    return (
        <>
            <FormDirtySignal signal={tabs.map((t) => t._id).join(',')} />
            <FieldSet className="gap-2">
                <FieldLegend variant="label">
                    {__('Tabs')} ({tabs.length})
                </FieldLegend>

                {tabs.map((tab, index) => (
                    <div key={tab._id}>
                        <input type="hidden" name={`settings.tabs.${index}.label`} value={tab.labelText} />

                        <ExpandableCard
                            isExpanded={expandedIndex === index}
                            onToggle={() => setExpandedIndex(expandedIndex === index ? null : index)}
                            title={tab.labelText || __('Tab :number', { number: index + 1 })}
                            action={
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        removeTab(tab._id, index);
                                    }}
                                >
                                    <XIcon />
                                </Button>
                            }
                        >
                            <Field>
                                <FieldLabel htmlFor={`settings-tabs-${index}-label`}>{__('Label')}</FieldLabel>
                                <Input
                                    id={`settings-tabs-${index}-label`}
                                    value={tab.labelText}
                                    onChange={(e) => updateLabel(tab._id, e.target.value)}
                                    placeholder={__('Electronics')}
                                />
                                <FieldError>{errors[`settings.tabs.${index}.label`]}</FieldError>
                            </Field>

                            <ProductSourceFields prefix={`settings.tabs.${index}`} settings={tab} errors={errors} />
                        </ExpandableCard>
                    </div>
                ))}
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="w-fit"
                    onClick={addTab}
                    disabled={tabs.length >= 5}
                >
                    <PlusIcon />
                    {__('Add tab')}
                </Button>
            </FieldSet>
        </>
    );
}
