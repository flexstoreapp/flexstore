import { PlusIcon, Trash2Icon } from 'lucide-react';
import { useState } from 'react';

import { BrandPicker, type SelectableBrand } from '@/components/admin/brand-picker';
import { FormDirtySignal } from '@/components/admin/form-dirty-signal';
import { HoverActions } from '@/components/admin/hover-actions';
import { Thumbnail } from '@/components/admin/thumbnail';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { __ } from '@/lib/i18n';
import { mediaAlt } from '@/lib/media';
import { cn, getTranslation } from '@/lib/utils';
import type { BrandStripSettings } from '@/types';

import { EmptyPlaceholder } from './empty-placeholder';

interface BrandStripFieldsProps {
    settings?: BrandStripSettings & {
        brands?: SelectableBrand[];
    };
    errors: Record<string, string>;
}

export function BrandStripFields({ settings, errors }: BrandStripFieldsProps) {
    const [selectedBrands, setSelectedBrands] = useState<SelectableBrand[]>(settings?.brands ?? []);
    const [brandPickerOpen, setBrandPickerOpen] = useState(false);

    const handleRemoveBrand = (brandId: number) => {
        setSelectedBrands((prev) => prev.filter((b) => b.id !== brandId));
    };

    return (
        <>
            <FormDirtySignal signal={selectedBrands.map((b) => b.id).join(',')} />
            <Field>
                <FieldLabel>
                    {__('Brands')} {selectedBrands.length > 0 && `(${selectedBrands.length})`}
                </FieldLabel>

                {selectedBrands.length === 0 ? (
                    <EmptyPlaceholder
                        title={__('No brands')}
                        description={__('Add brands to display in the strip.')}
                        action={
                            <Button type="button" variant="outline" size="sm" onClick={() => setBrandPickerOpen(true)}>
                                <PlusIcon />
                                {__('Add brands')}
                            </Button>
                        }
                    />
                ) : (
                    <div className="space-y-2">
                        {selectedBrands.map((brand, index) => (
                            <div
                                key={brand.id}
                                className={cn(
                                    'group/item relative flex items-center justify-between gap-2 rounded-lg border bg-background p-3',
                                    'transition-all hover:bg-muted/50',
                                )}
                            >
                                <div className="flex items-center gap-3">
                                    <Thumbnail
                                        src={brand.image?.url ?? null}
                                        alt={mediaAlt(brand.image, getTranslation(brand.name))}
                                    />
                                    <p className="text-sm font-medium">{getTranslation(brand.name)}</p>
                                </div>
                                <HoverActions>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label={__('Remove brand')}
                                        onClick={() => handleRemoveBrand(brand.id)}
                                    >
                                        <Trash2Icon />
                                    </Button>
                                </HoverActions>
                                <input type="hidden" name={`settings.brand_ids.${index}`} value={brand.id} />
                            </div>
                        ))}

                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="w-fit"
                            onClick={() => setBrandPickerOpen(true)}
                        >
                            <PlusIcon />
                            {__('Add brands')}
                        </Button>
                    </div>
                )}
                <FieldError>{errors['settings.brand_ids']}</FieldError>

                <BrandPicker
                    open={brandPickerOpen}
                    onOpenChange={setBrandPickerOpen}
                    selectedItems={selectedBrands}
                    onSelectionChange={setSelectedBrands}
                    multiple
                />
            </Field>

            <Field orientation="horizontal">
                <Checkbox
                    id="settings-grayscale"
                    name="settings.grayscale"
                    defaultChecked={settings?.grayscale ?? false}
                />
                <FieldLabel htmlFor="settings-grayscale">{__('Grayscale logos')}</FieldLabel>
            </Field>
        </>
    );
}
