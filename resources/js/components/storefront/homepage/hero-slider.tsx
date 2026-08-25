import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import { buttonVariants } from '@/components/storefront/button';
import { MediaImage } from '@/components/storefront/media-image';
import { Section } from '@/components/storefront/section';
import { overlayPosition } from '@/components/storefront/section-text-align';
import { overlayTextClass } from '@/components/storefront/section-text-color';
import { __ } from '@/lib/i18n';
import { cn, getTranslation } from '@/lib/utils';
import type { HeroSliderSectionData } from '@/types';

export function HeroSliderSection({ section }: { section: HeroSliderSectionData }) {
    const slides = section.settings.slides ?? [];
    const sideTiles = section.settings.side_tiles ?? [];
    const autoplay = section.settings.autoplay ?? true;
    const autoplaySpeed = section.settings.autoplay_speed ?? 6000;
    const isSlideTransition = section.settings.transition === 'slide';
    const showDots = section.settings.show_dots ?? true;

    const [currentIndex, setCurrentIndex] = useState(0);

    useEffect(() => {
        if (!autoplay || slides.length <= 1) return;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        const timer = window.setInterval(() => {
            setCurrentIndex((index) => (index + 1) % slides.length);
        }, autoplaySpeed);

        return () => window.clearInterval(timer);
    }, [autoplay, autoplaySpeed, slides.length]);

    if (slides.length === 0 && sideTiles.length === 0) {
        return null;
    }

    const activeIndex = Math.min(currentIndex, Math.max(slides.length - 1, 0));
    const hasDots = showDots && slides.length > 1;

    return (
        <Section className="pt-6">
            <div className="grid grid-cols-1 gap-4 sm:gap-5 lg:grid-cols-[1fr_300px]">
                {slides.length > 0 && (
                    <div className="relative aspect-[16/9] overflow-hidden rounded-md sm:aspect-auto sm:h-[400px] lg:h-[460px]">
                        {slides.map((slide, index) => {
                            const buttonText = getTranslation(slide.button_text);
                            const position = overlayPosition(slide.text_align);

                            return (
                                <div
                                    key={index}
                                    className={cn(
                                        'absolute inset-0 duration-500 ease-out-quart',
                                        isSlideTransition ? 'transition-transform' : 'transition-opacity',
                                        index !== activeIndex && 'pointer-events-none',
                                        !isSlideTransition && (index === activeIndex ? 'opacity-100' : 'opacity-0'),
                                    )}
                                    style={
                                        isSlideTransition
                                            ? { transform: `translateX(${(index - activeIndex) * 100}%)` }
                                            : undefined
                                    }
                                >
                                    <MediaImage
                                        media={slide.image}
                                        source="full"
                                        loading={index === 0 ? 'eager' : 'lazy'}
                                        className="absolute inset-0"
                                        placeholderClassName="absolute inset-0 h-full w-full"
                                    />
                                    <div
                                        className={cn(
                                            'absolute inset-0 flex flex-col px-4 sm:px-16',
                                            position.justify,
                                            position.items,
                                            hasDots && position.justify === 'justify-end' && 'pb-10 sm:pb-14',
                                        )}
                                    >
                                        <div className={cn('max-w-[62%] sm:max-w-[440px]', position.text)}>
                                            <h2
                                                className={cn(
                                                    'm-0 font-head text-xl leading-[1.08] font-bold sm:text-4xl lg:text-[46px]',
                                                    overlayTextClass(slide.text_color, 'heading'),
                                                )}
                                            >
                                                {getTranslation(slide.headline)}
                                            </h2>
                                            <p
                                                className={cn(
                                                    'mt-2 mb-4 text-xs sm:mt-3 sm:mb-6 sm:text-base',
                                                    overlayTextClass(slide.text_color, 'body'),
                                                )}
                                            >
                                                {getTranslation(slide.subtext)}
                                            </p>
                                            {buttonText && slide.button_url && (
                                                <Link
                                                    href={slide.button_url}
                                                    className={cn(
                                                        buttonVariants({ variant: 'primary', size: 'sm' }),
                                                        'sm:h-11 sm:px-5 sm:text-base',
                                                    )}
                                                >
                                                    {buttonText || __('Shop now')}
                                                </Link>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}

                        {hasDots && (
                            <div className="absolute start-4 bottom-3 z-20 -ms-1.5 flex sm:start-16 sm:bottom-5">
                                {slides.map((_, index) => (
                                    <button
                                        key={index}
                                        type="button"
                                        aria-label={__('Go to slide :number', { number: index + 1 })}
                                        aria-current={index === activeIndex ? 'true' : undefined}
                                        className="flex size-6 items-center justify-center rounded-full"
                                        onClick={() => setCurrentIndex(index)}
                                    >
                                        <span
                                            aria-hidden="true"
                                            className={cn(
                                                'size-2 rounded-full transition-all sm:size-2.5',
                                                slides[activeIndex]?.text_color === 'light'
                                                    ? index === activeIndex
                                                        ? 'scale-110 bg-white'
                                                        : 'bg-white/50 transition-colors hover:bg-white/80'
                                                    : index === activeIndex
                                                      ? 'scale-110 bg-ink'
                                                      : 'bg-ink/40 transition-colors hover:bg-ink/70',
                                            )}
                                        />
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>
                )}

                {sideTiles.length > 0 && (
                    <div className="flex flex-row gap-4 lg:flex-col lg:gap-5">
                        {sideTiles.map((tile, index) => {
                            const position = overlayPosition(tile.text_align ?? 'top-left');

                            return (
                                <Link
                                    key={index}
                                    href={tile.url ?? '#'}
                                    className="group relative h-[150px] overflow-hidden rounded-md max-lg:flex-1 max-lg:basis-0 sm:h-[200px] lg:h-[220px]"
                                >
                                    <MediaImage
                                        media={tile.image}
                                        source="full"
                                        className="absolute inset-0 transition-transform duration-(--duration-slow) ease-out-quart can-hover:group-hover:scale-105"
                                        placeholderClassName="absolute inset-0 h-full w-full"
                                    />
                                    <span
                                        className={cn(
                                            'absolute inset-0 flex flex-col p-3 sm:p-5',
                                            position.justify,
                                            position.items,
                                        )}
                                    >
                                        <span className={cn('block', position.text)}>
                                            <span
                                                className={cn(
                                                    'block font-head text-xl leading-[1.08] font-bold sm:text-2xl',
                                                    overlayTextClass(tile.text_color, 'heading'),
                                                )}
                                            >
                                                {getTranslation(tile.title)}
                                            </span>
                                            <span
                                                className={cn(
                                                    'mt-2 block text-xs sm:mt-1 sm:text-base',
                                                    overlayTextClass(tile.text_color, 'body'),
                                                )}
                                            >
                                                {getTranslation(tile.subtitle)}
                                            </span>
                                        </span>
                                    </span>
                                </Link>
                            );
                        })}
                    </div>
                )}
            </div>
        </Section>
    );
}
