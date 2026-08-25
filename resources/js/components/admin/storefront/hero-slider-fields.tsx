import { PlusIcon, XIcon } from 'lucide-react';
import { useState } from 'react';

import { ExpandableCard } from '@/components/admin/expandable-card';
import { FormDirtySignal } from '@/components/admin/form-dirty-signal';
import { InlineImageUploader } from '@/components/admin/inline-image-uploader';
import { TextAlignmentField } from '@/components/admin/storefront/text-alignment-field';
import { TextColorField } from '@/components/admin/storefront/text-color-field';
import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { HeroSideTile, HeroSlide, HeroSliderSettings, SectionTextAlign, SectionTextColor } from '@/types';
import type { MediaItem } from '@/types/media';

interface HeroSliderFieldsProps {
    settings?: HeroSliderSettings;
    errors: Record<string, string>;
}

type SlideLocal = {
    _id: string;
    image: MediaItem | null;
    headline: string;
    subtext: string;
    button_text: string;
    button_url: string;
    text_color: SectionTextColor;
    text_align: SectionTextAlign;
};

type SideTileLocal = {
    _id: string;
    image: MediaItem | null;
    title: string;
    subtitle: string;
    url: string;
    text_color: SectionTextColor;
    text_align: SectionTextAlign;
};

function toSlideLocal(slide: HeroSlide): SlideLocal {
    return {
        _id: crypto.randomUUID(),
        image: slide.image,
        headline: getTranslation(slide.headline),
        subtext: getTranslation(slide.subtext),
        button_text: getTranslation(slide.button_text),
        button_url: slide.button_url ?? '',
        text_color: slide.text_color ?? 'dark',
        text_align: slide.text_align ?? 'left',
    };
}

function toSideTileLocal(tile: HeroSideTile): SideTileLocal {
    return {
        _id: crypto.randomUUID(),
        image: tile.image,
        title: getTranslation(tile.title),
        subtitle: getTranslation(tile.subtitle),
        url: tile.url ?? '',
        text_color: tile.text_color ?? 'dark',
        text_align: tile.text_align ?? 'top-left',
    };
}

function newSlide(): SlideLocal {
    return {
        _id: crypto.randomUUID(),
        image: null,
        headline: '',
        subtext: '',
        button_text: '',
        button_url: '',
        text_color: 'dark',
        text_align: 'left',
    };
}

function newSideTile(): SideTileLocal {
    return {
        _id: crypto.randomUUID(),
        image: null,
        title: '',
        subtitle: '',
        url: '',
        text_color: 'dark',
        text_align: 'top-left',
    };
}

export function HeroSliderFields({ settings, errors }: HeroSliderFieldsProps) {
    const [slides, setSlides] = useState<SlideLocal[]>(settings?.slides.map(toSlideLocal) ?? []);
    const [sideTiles, setSideTiles] = useState<SideTileLocal[]>(settings?.side_tiles.map(toSideTileLocal) ?? []);
    const [expandedSlide, setExpandedSlide] = useState<number | null>(null);
    const [expandedTile, setExpandedTile] = useState<number | null>(null);
    const [autoplay, setAutoplay] = useState(settings?.autoplay ?? true);

    const addSlide = () => {
        if (slides.length >= 5) return;
        setSlides([...slides, newSlide()]);
        setExpandedSlide(slides.length);
    };

    const removeSlide = (id: string, index: number) => {
        setSlides(slides.filter((slide) => slide._id !== id));
        if (expandedSlide === index) {
            setExpandedSlide(null);
        }
    };

    const updateSlide = (id: string, field: keyof Omit<SlideLocal, '_id' | 'image'>, value: string) => {
        setSlides(slides.map((slide) => (slide._id === id ? { ...slide, [field]: value } : slide)));
    };

    const updateSlideImage = (id: string, media: MediaItem | null) => {
        setSlides(slides.map((slide) => (slide._id === id ? { ...slide, image: media } : slide)));
    };

    const addSideTile = () => {
        if (sideTiles.length >= 2) return;
        setSideTiles([...sideTiles, newSideTile()]);
        setExpandedTile(sideTiles.length);
    };

    const removeSideTile = (id: string, index: number) => {
        setSideTiles(sideTiles.filter((tile) => tile._id !== id));
        if (expandedTile === index) {
            setExpandedTile(null);
        }
    };

    const updateSideTile = (id: string, field: keyof Omit<SideTileLocal, '_id' | 'image'>, value: string) => {
        setSideTiles(sideTiles.map((tile) => (tile._id === id ? { ...tile, [field]: value } : tile)));
    };

    const updateSideTileImage = (id: string, media: MediaItem | null) => {
        setSideTiles(sideTiles.map((tile) => (tile._id === id ? { ...tile, image: media } : tile)));
    };

    return (
        <>
            <FormDirtySignal signal={slides.map((slide) => slide._id).join(',')} />
            <FormDirtySignal signal={sideTiles.map((tile) => tile._id).join(',')} />

            <FieldSet className="gap-2">
                <FieldLegend variant="label">
                    {__('Slides')} {slides.length > 0 && `(${slides.length})`}
                </FieldLegend>

                {slides.map((slide, index) => (
                    <div key={slide._id}>
                        <input type="hidden" name={`settings.slides.${index}.headline`} value={slide.headline} />
                        <input type="hidden" name={`settings.slides.${index}.subtext`} value={slide.subtext} />
                        <input type="hidden" name={`settings.slides.${index}.button_text`} value={slide.button_text} />
                        <input type="hidden" name={`settings.slides.${index}.button_url`} value={slide.button_url} />
                        <input
                            type="hidden"
                            name={`settings.slides.${index}.image`}
                            value={slide.image ? String(slide.image.id) : ''}
                        />

                        <ExpandableCard
                            isExpanded={expandedSlide === index}
                            onToggle={() => setExpandedSlide(expandedSlide === index ? null : index)}
                            title={slide.headline || __('Slide :number', { number: index + 1 })}
                            action={
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        removeSlide(slide._id, index);
                                    }}
                                >
                                    <XIcon />
                                </Button>
                            }
                        >
                            <Field>
                                <FieldLabel htmlFor={`hero-slide-image-${slide._id}`}>{__('Image')}</FieldLabel>
                                <InlineImageUploader
                                    id={`hero-slide-image-${slide._id}`}
                                    defaultValue={slide.image}
                                    size="lg"
                                    aspectRatio="video"
                                    label={__('Slide image')}
                                    onChange={(media) => updateSlideImage(slide._id, media)}
                                />
                                <FieldError>{errors[`settings.slides.${index}.image`]}</FieldError>
                            </Field>

                            <Field>
                                <FieldLabel htmlFor={`hero-slide-headline-${slide._id}`}>{__('Headline')}</FieldLabel>
                                <Input
                                    id={`hero-slide-headline-${slide._id}`}
                                    value={slide.headline}
                                    onChange={(e) => updateSlide(slide._id, 'headline', e.target.value)}
                                    placeholder={__('Summer sale up to 50% off')}
                                />
                                <FieldError>{errors[`settings.slides.${index}.headline`]}</FieldError>
                            </Field>

                            <Field>
                                <FieldLabel htmlFor={`hero-slide-subtext-${slide._id}`}>{__('Subtext')}</FieldLabel>
                                <Textarea
                                    id={`hero-slide-subtext-${slide._id}`}
                                    value={slide.subtext}
                                    onChange={(e) => updateSlide(slide._id, 'subtext', e.target.value)}
                                    placeholder={__('Thousands of deals across every department.')}
                                    className="max-h-24"
                                />
                                <FieldError>{errors[`settings.slides.${index}.subtext`]}</FieldError>
                            </Field>

                            <div className="grid gap-3 sm:grid-cols-2">
                                <Field>
                                    <FieldLabel htmlFor={`hero-slide-button-text-${slide._id}`}>
                                        {__('Button text')}
                                    </FieldLabel>
                                    <Input
                                        id={`hero-slide-button-text-${slide._id}`}
                                        value={slide.button_text}
                                        onChange={(e) => updateSlide(slide._id, 'button_text', e.target.value)}
                                        placeholder={__('Shop now')}
                                    />
                                    <FieldError>{errors[`settings.slides.${index}.button_text`]}</FieldError>
                                </Field>

                                <Field>
                                    <FieldLabel htmlFor={`hero-slide-button-url-${slide._id}`}>
                                        {__('Button URL')}
                                    </FieldLabel>
                                    <Input
                                        id={`hero-slide-button-url-${slide._id}`}
                                        value={slide.button_url}
                                        onChange={(e) => updateSlide(slide._id, 'button_url', e.target.value)}
                                        placeholder="/shop"
                                    />
                                    <FieldError>{errors[`settings.slides.${index}.button_url`]}</FieldError>
                                </Field>
                            </div>

                            <TextColorField
                                name={`settings.slides.${index}.text_color`}
                                value={slide.text_color}
                                onChange={(color) =>
                                    setSlides(
                                        slides.map((s) => (s._id === slide._id ? { ...s, text_color: color } : s)),
                                    )
                                }
                            />

                            <TextAlignmentField
                                name={`settings.slides.${index}.text_align`}
                                value={slide.text_align}
                                onChange={(align) =>
                                    setSlides(
                                        slides.map((s) => (s._id === slide._id ? { ...s, text_align: align } : s)),
                                    )
                                }
                                error={errors[`settings.slides.${index}.text_align`]}
                            />
                        </ExpandableCard>
                    </div>
                ))}
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="w-fit"
                    onClick={addSlide}
                    disabled={slides.length >= 5}
                >
                    <PlusIcon />
                    {__('Add slide')}
                </Button>
            </FieldSet>

            <FieldSet className="gap-2">
                <FieldLegend variant="label">
                    {__('Side tiles')} {sideTiles.length > 0 && `(${sideTiles.length})`}
                </FieldLegend>

                {sideTiles.map((tile, index) => (
                    <div key={tile._id}>
                        <input type="hidden" name={`settings.side_tiles.${index}.title`} value={tile.title} />
                        <input type="hidden" name={`settings.side_tiles.${index}.subtitle`} value={tile.subtitle} />
                        <input type="hidden" name={`settings.side_tiles.${index}.url`} value={tile.url} />
                        <input
                            type="hidden"
                            name={`settings.side_tiles.${index}.image`}
                            value={tile.image ? String(tile.image.id) : ''}
                        />

                        <ExpandableCard
                            isExpanded={expandedTile === index}
                            onToggle={() => setExpandedTile(expandedTile === index ? null : index)}
                            title={tile.title || __('Side tile :number', { number: index + 1 })}
                            action={
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        removeSideTile(tile._id, index);
                                    }}
                                >
                                    <XIcon />
                                </Button>
                            }
                        >
                            <Field>
                                <FieldLabel htmlFor={`hero-side-tile-image-${tile._id}`}>{__('Image')}</FieldLabel>
                                <InlineImageUploader
                                    id={`hero-side-tile-image-${tile._id}`}
                                    defaultValue={tile.image}
                                    size="lg"
                                    aspectRatio="video"
                                    label={__('Side tile image')}
                                    onChange={(media) => updateSideTileImage(tile._id, media)}
                                />
                                <FieldError>{errors[`settings.side_tiles.${index}.image`]}</FieldError>
                            </Field>

                            <Field>
                                <FieldLabel htmlFor={`hero-side-tile-title-${tile._id}`}>{__('Title')}</FieldLabel>
                                <Input
                                    id={`hero-side-tile-title-${tile._id}`}
                                    value={tile.title}
                                    onChange={(e) => updateSideTile(tile._id, 'title', e.target.value)}
                                    placeholder={__('Headphones')}
                                />
                                <FieldError>{errors[`settings.side_tiles.${index}.title`]}</FieldError>
                            </Field>

                            <Field>
                                <FieldLabel htmlFor={`hero-side-tile-subtitle-${tile._id}`}>
                                    {__('Subtitle')}
                                </FieldLabel>
                                <Input
                                    id={`hero-side-tile-subtitle-${tile._id}`}
                                    value={tile.subtitle}
                                    onChange={(e) => updateSideTile(tile._id, 'subtitle', e.target.value)}
                                    placeholder={__('From $49')}
                                />
                                <FieldError>{errors[`settings.side_tiles.${index}.subtitle`]}</FieldError>
                            </Field>

                            <Field>
                                <FieldLabel htmlFor={`hero-side-tile-url-${tile._id}`}>{__('URL')}</FieldLabel>
                                <Input
                                    id={`hero-side-tile-url-${tile._id}`}
                                    value={tile.url}
                                    onChange={(e) => updateSideTile(tile._id, 'url', e.target.value)}
                                    placeholder="/shop"
                                />
                                <FieldError>{errors[`settings.side_tiles.${index}.url`]}</FieldError>
                            </Field>

                            <TextColorField
                                name={`settings.side_tiles.${index}.text_color`}
                                value={tile.text_color}
                                onChange={(color) =>
                                    setSideTiles(
                                        sideTiles.map((t) => (t._id === tile._id ? { ...t, text_color: color } : t)),
                                    )
                                }
                            />

                            <TextAlignmentField
                                name={`settings.side_tiles.${index}.text_align`}
                                value={tile.text_align}
                                onChange={(align) =>
                                    setSideTiles(
                                        sideTiles.map((t) => (t._id === tile._id ? { ...t, text_align: align } : t)),
                                    )
                                }
                                error={errors[`settings.side_tiles.${index}.text_align`]}
                            />
                        </ExpandableCard>
                    </div>
                ))}
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="w-fit"
                    onClick={addSideTile}
                    disabled={sideTiles.length >= 2}
                >
                    <PlusIcon />
                    {__('Add side tile')}
                </Button>
            </FieldSet>

            <Field orientation="horizontal">
                <Checkbox
                    id="settings-autoplay"
                    name="settings.autoplay"
                    checked={autoplay}
                    onCheckedChange={(checked) => setAutoplay(checked === true)}
                />
                <FieldLabel htmlFor="settings-autoplay">{__('Autoplay slides')}</FieldLabel>
            </Field>

            {autoplay && (
                <Field>
                    <FieldLabel htmlFor="settings-autoplay-speed">{__('Autoplay speed (ms)')}</FieldLabel>
                    <Input
                        id="settings-autoplay-speed"
                        name="settings.autoplay_speed"
                        type="number"
                        min={1000}
                        max={15000}
                        step={500}
                        defaultValue={settings?.autoplay_speed ?? 6000}
                    />
                    <FieldError>{errors['settings.autoplay_speed']}</FieldError>
                </Field>
            )}

            <Field>
                <FieldLabel htmlFor="settings-transition">{__('Slide animation')}</FieldLabel>
                <AdaptiveSelect
                    id="settings-transition"
                    name="settings.transition"
                    defaultValue={settings?.transition ?? 'fade'}
                    options={[
                        { value: 'fade', label: __('Fade') },
                        { value: 'slide', label: __('Slide') },
                    ]}
                    className="w-52"
                />
                <FieldError>{errors['settings.transition']}</FieldError>
            </Field>

            <Field orientation="horizontal">
                <Checkbox
                    id="settings-show-dots"
                    name="settings.show_dots"
                    defaultChecked={settings?.show_dots ?? true}
                />
                <FieldLabel htmlFor="settings-show-dots">{__('Show slide navigation dots')}</FieldLabel>
            </Field>
        </>
    );
}
