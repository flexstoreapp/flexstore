import { PlusIcon, XIcon } from 'lucide-react';
import { useState } from 'react';

import { CategoryPicker, type SelectableItem } from '@/components/admin/category-picker';
import { ExpandableCard } from '@/components/admin/expandable-card';
import { InlineImageUploader } from '@/components/admin/inline-image-uploader';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldLabel, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { MediaItem } from '@/types/media';

import { EmptyPlaceholder } from './empty-placeholder';

export interface BrowseCategoryData {
    category: SelectableItem;
    is_mega_menu: boolean;
    featured_image: MediaItem | null;
    featured_title: string | null;
    featured_url: string | null;
}

interface BrowseCategoryConfig extends BrowseCategoryData {
    _id: string;
}

export interface BrowseCategoryPayload {
    category_id: number;
    is_mega_menu: boolean;
    featured_image_id: number | null;
    featured_title: string | null;
    featured_url: string | null;
}

interface BrowseCategoriesFieldsProps {
    browseCategories: BrowseCategoryData[];
    onSave: (payload: BrowseCategoryPayload[]) => void;
}

const toPayload = (configs: BrowseCategoryConfig[]): BrowseCategoryPayload[] =>
    configs.map((config) => ({
        category_id: config.category.id,
        is_mega_menu: config.is_mega_menu,
        featured_image_id: config.featured_image?.id ?? null,
        featured_title: config.featured_title,
        featured_url: config.featured_url,
    }));

export function BrowseCategoriesFields({ browseCategories, onSave }: BrowseCategoriesFieldsProps) {
    const [configs, setConfigs] = useState<BrowseCategoryConfig[]>(
        browseCategories.map((item) => ({ ...item, _id: crypto.randomUUID() })),
    );
    const [pickerOpen, setPickerOpen] = useState(false);
    const [expandedId, setExpandedId] = useState<string | null>(null);

    const updateLocal = (id: string, updates: Partial<BrowseCategoryData>) =>
        setConfigs((current) => current.map((config) => (config._id === id ? { ...config, ...updates } : config)));

    const commit = (next: BrowseCategoryConfig[]) => {
        setConfigs(next);
        onSave(toPayload(next));
    };

    const handleSelection = (selection: SelectableItem[]) => {
        commit(
            selection.map(
                (category) =>
                    configs.find((config) => config.category.id === category.id) ?? {
                        _id: crypto.randomUUID(),
                        category,
                        is_mega_menu: false,
                        featured_image: null,
                        featured_title: null,
                        featured_url: null,
                    },
            ),
        );
    };

    const removeConfig = (id: string) => {
        commit(configs.filter((config) => config._id !== id));
    };

    return (
        <>
            {configs.length === 0 ? (
                <EmptyPlaceholder
                    title={__('No categories')}
                    description={__('Add categories to the Browse categories menu.')}
                    action={
                        <Button type="button" variant="outline" size="sm" onClick={() => setPickerOpen(true)}>
                            <PlusIcon />
                            {__('Add categories')}
                        </Button>
                    }
                />
            ) : (
                <FieldSet className="gap-2">
                    {configs.map((config) => (
                        <ExpandableCard
                            key={config._id}
                            isExpanded={expandedId === config._id}
                            onToggle={() => setExpandedId((current) => (current === config._id ? null : config._id))}
                            title={getTranslation(config.category.name)}
                            action={
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label={__('Remove category')}
                                    onClick={(event) => {
                                        event.stopPropagation();
                                        removeConfig(config._id);
                                    }}
                                >
                                    <XIcon />
                                </Button>
                            }
                        >
                            <Field orientation="horizontal">
                                <Checkbox
                                    id={`mega-${config._id}`}
                                    checked={config.is_mega_menu}
                                    onCheckedChange={(checked) =>
                                        updateLocal(config._id, { is_mega_menu: checked === true })
                                    }
                                />
                                <FieldLabel htmlFor={`mega-${config._id}`}>{__('Show as mega menu')}</FieldLabel>
                            </Field>

                            {config.is_mega_menu && (
                                <>
                                    <Field>
                                        <FieldLabel htmlFor={`featured-${config._id}`}>
                                            {__('Featured image')}
                                        </FieldLabel>
                                        <InlineImageUploader
                                            id={`featured-${config._id}`}
                                            value={config.featured_image}
                                            aspectRatio="square"
                                            onChange={(media) => updateLocal(config._id, { featured_image: media })}
                                        />
                                    </Field>

                                    <Field>
                                        <FieldLabel htmlFor={`ftitle-${config._id}`}>{__('Featured title')}</FieldLabel>
                                        <Input
                                            id={`ftitle-${config._id}`}
                                            value={config.featured_title ?? ''}
                                            placeholder={__('e.g., New arrivals just dropped')}
                                            onChange={(event) =>
                                                updateLocal(config._id, { featured_title: event.target.value })
                                            }
                                        />
                                    </Field>

                                    <Field>
                                        <FieldLabel htmlFor={`furl-${config._id}`}>{__('Featured URL')}</FieldLabel>
                                        <Input
                                            id={`furl-${config._id}`}
                                            value={config.featured_url ?? ''}
                                            placeholder="/shop"
                                            onChange={(event) =>
                                                updateLocal(config._id, { featured_url: event.target.value })
                                            }
                                        />
                                    </Field>
                                </>
                            )}

                            <Button
                                type="button"
                                size="sm"
                                className="ms-auto flex w-fit"
                                onClick={() => onSave(toPayload(configs))}
                            >
                                {__('Save')}
                            </Button>
                        </ExpandableCard>
                    ))}

                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="w-fit"
                        onClick={() => setPickerOpen(true)}
                    >
                        <PlusIcon />
                        {__('Add categories')}
                    </Button>
                </FieldSet>
            )}

            <CategoryPicker
                open={pickerOpen}
                onOpenChange={setPickerOpen}
                selectedItems={configs.map((config) => config.category)}
                onSelectionChange={handleSelection}
                multiple
            />
        </>
    );
}
