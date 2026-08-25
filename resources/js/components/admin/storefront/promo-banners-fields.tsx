import { PlusIcon, XIcon } from 'lucide-react';
import { useState } from 'react';

import { ExpandableCard } from '@/components/admin/expandable-card';
import { FormDirtySignal } from '@/components/admin/form-dirty-signal';
import { InlineImageUploader } from '@/components/admin/inline-image-uploader';
import { TextAlignmentField } from '@/components/admin/storefront/text-alignment-field';
import { TextColorField } from '@/components/admin/storefront/text-color-field';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { PromoBannersSettings, SectionTextAlign, SectionTextColor } from '@/types';
import type { MediaItem } from '@/types/media';

interface PromoBannersFieldsProps {
    settings?: PromoBannersSettings;
    errors: Record<string, string>;
}

type PromoBannerLocal = {
    image: MediaItem | null;
    title: string;
    subtitle: string;
    url: string;
    text_align: SectionTextAlign;
    text_color: SectionTextColor;
    _id: string;
};

export function PromoBannersFields({ settings, errors }: PromoBannersFieldsProps) {
    const [banners, setBanners] = useState<PromoBannerLocal[]>(
        settings?.banners.map((banner) => ({
            image: banner.image,
            title: getTranslation(banner.title),
            subtitle: getTranslation(banner.subtitle),
            url: banner.url ?? '',
            text_align: banner.text_align ?? 'left',
            text_color: banner.text_color ?? 'dark',
            _id: crypto.randomUUID(),
        })) ?? [],
    );
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    const addBanner = () => {
        if (banners.length < 2) {
            setBanners([
                ...banners,
                {
                    image: null,
                    title: '',
                    subtitle: '',
                    url: '',
                    text_align: 'left',
                    text_color: 'dark',
                    _id: crypto.randomUUID(),
                },
            ]);
            setExpandedIndex(banners.length);
        }
    };

    const removeBanner = (id: string, index: number) => {
        setBanners(banners.filter((banner) => banner._id !== id));
        if (expandedIndex === index) {
            setExpandedIndex(null);
        }
    };

    const updateBanner = (id: string, updates: Partial<Omit<PromoBannerLocal, '_id'>>) => {
        setBanners(banners.map((banner) => (banner._id === id ? { ...banner, ...updates } : banner)));
    };

    return (
        <>
            <FormDirtySignal signal={banners.map((b) => b._id).join(',')} />
            <FieldSet className="gap-2">
                <FieldLegend variant="label">
                    {__('Banners')} {banners.length > 0 && `(${banners.length})`}
                </FieldLegend>

                {banners.map((banner, index) => (
                    <div key={banner._id}>
                        <input
                            type="hidden"
                            name={`settings.banners.${index}.image`}
                            value={banner.image ? String(banner.image.id) : ''}
                        />
                        <input type="hidden" name={`settings.banners.${index}.title`} value={banner.title} />
                        <input type="hidden" name={`settings.banners.${index}.subtitle`} value={banner.subtitle} />
                        <input type="hidden" name={`settings.banners.${index}.url`} value={banner.url} />

                        <ExpandableCard
                            isExpanded={expandedIndex === index}
                            onToggle={() => setExpandedIndex(expandedIndex === index ? null : index)}
                            title={banner.title || __('Banner')}
                            action={
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        removeBanner(banner._id, index);
                                    }}
                                >
                                    <XIcon />
                                </Button>
                            }
                        >
                            <div className="space-y-3">
                                <Field>
                                    <FieldLabel htmlFor={`banner-image-${index}`}>{__('Image')}</FieldLabel>
                                    <InlineImageUploader
                                        id={`banner-image-${index}`}
                                        label={__('Banner image')}
                                        size="lg"
                                        aspectRatio="video"
                                        defaultValue={banner.image}
                                        onChange={(media) => updateBanner(banner._id, { image: media })}
                                        error={errors[`settings.banners.${index}.image`]}
                                    />
                                </Field>

                                <Field>
                                    <FieldLabel htmlFor={`banner-title-${index}`}>{__('Title')}</FieldLabel>
                                    <Input
                                        id={`banner-title-${index}`}
                                        value={banner.title}
                                        onChange={(e) => updateBanner(banner._id, { title: e.target.value })}
                                        placeholder={__('Kitchen appliances')}
                                    />
                                    <FieldError>{errors[`settings.banners.${index}.title`]}</FieldError>
                                </Field>

                                <Field>
                                    <FieldLabel htmlFor={`banner-subtitle-${index}`}>{__('Subtitle')}</FieldLabel>
                                    <Input
                                        id={`banner-subtitle-${index}`}
                                        value={banner.subtitle}
                                        onChange={(e) => updateBanner(banner._id, { subtitle: e.target.value })}
                                        placeholder={__('Up to 30% off this weekend')}
                                    />
                                    <FieldError>{errors[`settings.banners.${index}.subtitle`]}</FieldError>
                                </Field>

                                <Field>
                                    <FieldLabel htmlFor={`banner-url-${index}`}>{__('Link URL')}</FieldLabel>
                                    <Input
                                        id={`banner-url-${index}`}
                                        value={banner.url}
                                        onChange={(e) => updateBanner(banner._id, { url: e.target.value })}
                                        placeholder="/shop"
                                    />
                                    <FieldError>{errors[`settings.banners.${index}.url`]}</FieldError>
                                </Field>

                                <TextAlignmentField
                                    name={`settings.banners.${index}.text_align`}
                                    value={banner.text_align}
                                    onChange={(value) => updateBanner(banner._id, { text_align: value })}
                                    error={errors[`settings.banners.${index}.text_align`]}
                                />

                                <TextColorField
                                    name={`settings.banners.${index}.text_color`}
                                    value={banner.text_color}
                                    onChange={(color) => updateBanner(banner._id, { text_color: color })}
                                />
                            </div>
                        </ExpandableCard>
                    </div>
                ))}

                {banners.length < 2 && (
                    <Button type="button" variant="outline" size="sm" className="w-fit" onClick={addBanner}>
                        <PlusIcon />
                        {__('Add banner')}
                    </Button>
                )}
                <FieldError>{errors['settings.banners']}</FieldError>
            </FieldSet>
        </>
    );
}
