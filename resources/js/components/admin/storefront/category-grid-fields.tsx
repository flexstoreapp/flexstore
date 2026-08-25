import { PlusIcon, XIcon } from 'lucide-react';
import { useState } from 'react';

import { CategoryPicker, type SelectableItem } from '@/components/admin/category-picker';
import { ExpandableCard } from '@/components/admin/expandable-card';
import { FormDirtySignal } from '@/components/admin/form-dirty-signal';
import { InlineImageUploader } from '@/components/admin/inline-image-uploader';
import { TextColorField } from '@/components/admin/storefront/text-color-field';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { CategoryGridItem, CategoryGridSettings, SectionTextColor } from '@/types';
import type { MediaItem } from '@/types/media';

import { EmptyPlaceholder } from './empty-placeholder';

interface CategoryGridFieldsProps {
    settings?: CategoryGridSettings;
    errors: Record<string, string>;
}

type CategoryGridItemWithId = CategoryGridItem & { _id: string };

export function CategoryGridFields({ settings, errors }: CategoryGridFieldsProps) {
    const [categories, setCategories] = useState<CategoryGridItemWithId[]>(
        settings?.categories.map((cat) => ({
            ...cat,
            text_color: cat.text_color ?? 'dark',
            _id: crypto.randomUUID(),
        })) ?? [],
    );
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);
    const [pickerOpen, setPickerOpen] = useState(false);

    const selectedItems: SelectableItem[] = categories.map((cat) => ({
        id: cat.category_id,
        name: cat.name || {},
    }));

    const handleCategorySelection = (selection: SelectableItem[]) => {
        const existingCategoryIds = categories.map((cat) => cat.category_id);
        const newCategories = selection
            .filter((item) => !existingCategoryIds.includes(item.id))
            .map((item) => ({
                category_id: item.id,
                name: item.name,
                image: null,
                text_color: 'dark' as const,
                _id: crypto.randomUUID(),
            }));

        const existingFiltered = categories.filter((cat) => selection.some((item) => item.id === cat.category_id));
        const updatedCategories = [...existingFiltered, ...newCategories];

        setCategories(updatedCategories);

        if (newCategories.length === 1) {
            setExpandedIndex(existingFiltered.length);
        }
    };

    const removeCategory = (id: string, index: number) => {
        setCategories(categories.filter((cat) => cat._id !== id));
        if (expandedIndex === index) {
            setExpandedIndex(null);
        }
    };

    const updateCategoryImage = (id: string, media: MediaItem | null) => {
        setCategories(categories.map((cat) => (cat._id === id ? { ...cat, image: media } : cat)));
    };

    const updateCategoryTextColor = (id: string, text_color: SectionTextColor) => {
        setCategories(categories.map((cat) => (cat._id === id ? { ...cat, text_color } : cat)));
    };

    const addButton = (
        <Button
            type="button"
            variant="outline"
            size="sm"
            className="w-fit"
            onClick={() => setPickerOpen(true)}
            disabled={categories.length >= 12}
        >
            <PlusIcon />
            {__('Add categories')}
        </Button>
    );

    return (
        <>
            <FormDirtySignal signal={categories.map((c) => c._id).join(',')} />
            <FieldSet className="gap-2">
                <FieldLegend variant="label">
                    {__('Categories')} {categories.length > 0 && `(${categories.length})`}
                </FieldLegend>

                {categories.length === 0 ? (
                    <EmptyPlaceholder
                        title={__('No categories')}
                        description={__('Add categories to display in the grid.')}
                        action={addButton}
                    />
                ) : (
                    <>
                        {categories.map((category, index) => (
                            <div key={category._id}>
                                <input
                                    type="hidden"
                                    name={`settings.categories.${index}.category_id`}
                                    value={category.category_id}
                                />
                                <input
                                    type="hidden"
                                    name={`settings.categories.${index}.image`}
                                    value={category.image ? String(category.image.id) : ''}
                                />

                                <ExpandableCard
                                    isExpanded={expandedIndex === index}
                                    onToggle={() => setExpandedIndex(expandedIndex === index ? null : index)}
                                    title={getTranslation(category.name)}
                                    action={
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon-sm"
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                removeCategory(category._id, index);
                                            }}
                                        >
                                            <XIcon />
                                        </Button>
                                    }
                                >
                                    <Field>
                                        <FieldLabel htmlFor={`category-grid-image-${index}`}>{__('Image')}</FieldLabel>
                                        <InlineImageUploader
                                            id={`category-grid-image-${index}`}
                                            label={getTranslation(category.name)}
                                            size="lg"
                                            aspectRatio="landscape"
                                            defaultValue={category.image}
                                            onChange={(media) => updateCategoryImage(category._id, media)}
                                        />
                                        <FieldError>{errors[`settings.categories.${index}.image`]}</FieldError>
                                    </Field>

                                    <TextColorField
                                        name={`settings.categories.${index}.text_color`}
                                        value={category.text_color}
                                        onChange={(color) => updateCategoryTextColor(category._id, color)}
                                    />
                                </ExpandableCard>
                            </div>
                        ))}

                        {addButton}
                    </>
                )}
                <FieldError>{errors['settings.categories']}</FieldError>
            </FieldSet>

            <CategoryPicker
                open={pickerOpen}
                onOpenChange={setPickerOpen}
                selectedItems={selectedItems}
                onSelectionChange={handleCategorySelection}
                multiple
            />
        </>
    );
}
