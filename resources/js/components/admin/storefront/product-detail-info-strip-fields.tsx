import { router } from '@inertiajs/react';
import { PlusIcon, XIcon } from 'lucide-react';
import { useState } from 'react';

import * as StorefrontProductDetailController from '@/actions/App/Http/Controllers/Admin/StorefrontProductDetailController';
import { ExpandableCard } from '@/components/admin/expandable-card';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
import { IconPicker } from '@/components/ui/icon-picker';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { usePermissions } from '@/hooks/admin/use-permissions';
import { useStorefrontBuilder } from '@/layouts/admin/storefront-builder-layout';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import { getTranslation } from '@/lib/utils';
import type { InfoStripItem } from '@/types';

import { EmptyPlaceholder } from './empty-placeholder';

interface ProductDetailInfoStripFieldsProps {
    items: InfoStripItem[];
    errors: Record<string, string>;
}

type InfoStripItemLocal = {
    icon_name: string;
    title: string;
    subtitle: string;
    _id: string;
};

export function ProductDetailInfoStripFields({ items: initialItems, errors }: ProductDetailInfoStripFieldsProps) {
    const [items, setItems] = useState<InfoStripItemLocal[]>(
        initialItems.map((item) => ({
            icon_name: item.icon_name,
            title: getTranslation(item.title),
            subtitle: item.subtitle ? getTranslation(item.subtitle) : '',
            _id: crypto.randomUUID(),
        })),
    );
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);
    const { reloadIframe } = useStorefrontBuilder();
    const { hasPermission } = usePermissions();
    const canEdit = hasPermission(Permission.StorefrontUpdate);

    const saveItems = (updatedItems: InfoStripItemLocal[]) => {
        const itemsData = updatedItems.map((item) => ({
            icon_name: item.icon_name,
            title: item.title,
            subtitle: item.subtitle,
        }));

        router.patch(
            StorefrontProductDetailController.update(),
            { storefront_product_detail_info_strip: itemsData },
            {
                preserveScroll: true,
                only: ['settings'],
                onSuccess: () => reloadIframe(),
            },
        );
    };

    const addItem = () => {
        if (items.length < 3) {
            const newItems = [...items, { icon_name: '', title: '', subtitle: '', _id: crypto.randomUUID() }];
            setItems(newItems);
            setExpandedIndex(items.length);
        }
    };

    const removeItem = (id: string, index: number) => {
        const updatedItems = items.filter((item) => item._id !== id);
        setItems(updatedItems);
        if (expandedIndex === index) {
            setExpandedIndex(null);
        }
        saveItems(updatedItems);
    };

    const updateItem = (id: string, updates: Partial<Omit<InfoStripItemLocal, '_id'>>) => {
        const updatedItems = items.map((item) => (item._id === id ? { ...item, ...updates } : item));
        setItems(updatedItems);
    };

    const handleSaveItem = () => {
        saveItems(items);
    };

    const addButton = canEdit ? (
        <Button
            type="button"
            variant="outline"
            size="sm"
            className="w-fit"
            onClick={addItem}
            disabled={items.length >= 3}
        >
            <PlusIcon />
            {__('Add item')}
        </Button>
    ) : null;

    return (
        <FieldSet className="gap-2">
            <FieldLegend variant="label">
                {__('Info strip')} {items.length > 0 && `(${items.length})`}
            </FieldLegend>

            {items.length === 0 ? (
                <EmptyPlaceholder
                    title={__('No items')}
                    description={__('Add items to highlight key benefits.')}
                    action={addButton}
                />
            ) : (
                <>
                    {items.map((item, index) => (
                        <ExpandableCard
                            key={item._id}
                            isExpanded={expandedIndex === index}
                            onToggle={() => setExpandedIndex(expandedIndex === index ? null : index)}
                            title={item.title || __('Item :number', { number: index + 1 })}
                            action={
                                canEdit ? (
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
                                ) : undefined
                            }
                        >
                            <Field>
                                <FieldLabel htmlFor={`info-strip-${index}-icon`}>{__('Icon')}</FieldLabel>
                                <IconPicker
                                    id={`info-strip-${index}-icon`}
                                    value={item.icon_name}
                                    onChange={(icon_name) => updateItem(item._id, { icon_name })}
                                    readOnly={!canEdit}
                                />
                                <FieldError>
                                    {errors[`storefront_product_detail_info_strip.${index}.icon_name`]}
                                </FieldError>
                            </Field>

                            <Field>
                                <FieldLabel htmlFor={`info-strip-${index}-title`}>{__('Title')}</FieldLabel>
                                <Input
                                    id={`info-strip-${index}-title`}
                                    value={item.title}
                                    onChange={(e) => updateItem(item._id, { title: e.target.value })}
                                    placeholder={__('Free shipping')}
                                    readOnly={!canEdit}
                                />
                                <FieldError>{errors[`storefront_product_detail_info_strip.${index}.title`]}</FieldError>
                            </Field>

                            <Field>
                                <FieldLabel htmlFor={`info-strip-${index}-subtitle`}>{__('Subtitle')}</FieldLabel>
                                <Textarea
                                    id={`info-strip-${index}-subtitle`}
                                    value={item.subtitle}
                                    onChange={(e) => updateItem(item._id, { subtitle: e.target.value })}
                                    placeholder={__('On all orders over $50')}
                                    rows={2}
                                    className="max-h-24"
                                    readOnly={!canEdit}
                                />
                                <FieldError>
                                    {errors[`storefront_product_detail_info_strip.${index}.subtitle`]}
                                </FieldError>
                            </Field>

                            {canEdit && (
                                <Button type="button" size="sm" className="ms-auto flex w-fit" onClick={handleSaveItem}>
                                    {__('Save')}
                                </Button>
                            )}
                        </ExpandableCard>
                    ))}

                    {addButton}
                </>
            )}
            <FieldError>{errors['storefront_product_detail_info_strip']}</FieldError>
        </FieldSet>
    );
}
