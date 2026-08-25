import { ChevronLeftIcon, ChevronRightIcon, XIcon } from 'lucide-react';
import { type KeyboardEvent as ReactKeyboardEvent, useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';

import { ContainedMedia } from '@/components/storefront/contained-media';
import { MediaImage } from '@/components/storefront/media-image';
import { __ } from '@/lib/i18n';
import { mediaBoxRatio } from '@/lib/media';
import { cn, getTranslation } from '@/lib/utils';
import type { TranslatableField } from '@/types';
import type { MediaItem } from '@/types/media';

interface ProductLightboxProps {
    open: boolean;
    media: MediaItem[];
    index: number;
    title: TranslatableField;
    onClose: () => void;
    onIndexChange: (index: number) => void;
}

export function ProductLightbox({ open, media, index, title, onClose, onIndexChange }: ProductLightboxProps) {
    const count = media.length;
    const hasMultiple = count > 1;
    const ratio = mediaBoxRatio(media);
    const altText = getTranslation(title);
    const dialogRef = useRef<HTMLDivElement>(null);
    const thumbRefs = useRef<(HTMLButtonElement | null)[]>([]);

    useEffect(() => {
        if (!open) {
            return;
        }

        const previouslyFocused = document.activeElement as HTMLElement | null;
        document.body.style.overflow = 'hidden';
        dialogRef.current?.focus();

        return () => {
            document.body.style.overflow = '';
            previouslyFocused?.focus();
        };
    }, [open]);

    useEffect(() => {
        if (!open) {
            return;
        }

        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose();
                return;
            }
            if ((event.target as HTMLElement | null)?.closest('[role="tablist"]')) {
                return;
            }
            const rtl = document.documentElement.dir === 'rtl';
            if (event.key === 'ArrowRight') onIndexChange((index + (rtl ? -1 : 1) + count) % count);
            else if (event.key === 'ArrowLeft') onIndexChange((index - (rtl ? -1 : 1) + count) % count);
        };

        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, [open, index, count, onClose, onIndexChange]);

    if (count === 0 || typeof document === 'undefined') {
        return null;
    }

    const current = media[index];

    const onThumbsKeyDown = (event: ReactKeyboardEvent<HTMLDivElement>) => {
        const rtl = getComputedStyle(event.currentTarget).direction === 'rtl';
        const forward = rtl ? -1 : 1;

        let next = -1;
        if (event.key === 'ArrowRight') next = (index + forward + count) % count;
        else if (event.key === 'ArrowLeft') next = (index - forward + count) % count;
        else if (event.key === 'Home') next = 0;
        else if (event.key === 'End') next = count - 1;

        if (next < 0) {
            return;
        }

        event.preventDefault();
        onIndexChange(next);
        thumbRefs.current[next]?.focus();
    };

    return createPortal(
        <div
            ref={dialogRef}
            role="dialog"
            aria-modal="true"
            aria-label={altText}
            aria-hidden={open ? undefined : true}
            tabIndex={-1}
            onClick={onClose}
            className={cn(
                'fixed inset-0 z-9999 flex flex-col bg-black/95 transition-opacity duration-(--duration-slow) ease-out-quart focus:outline-none',
                open ? 'opacity-100' : 'pointer-events-none opacity-0',
            )}
        >
            <div className="flex items-center justify-between p-4 text-white/80">
                <span className="text-sm tabular-nums">
                    {index + 1} / {count}
                </span>
                <button
                    type="button"
                    tabIndex={open ? 0 : -1}
                    aria-label={__('Close')}
                    onClick={onClose}
                    className="flex size-11 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur transition hover:bg-white/20"
                >
                    <XIcon size={22} aria-hidden="true" />
                </button>
            </div>

            <div
                id="lb-image"
                role="tabpanel"
                aria-live="polite"
                className="relative flex min-h-0 flex-1 items-center justify-center p-4 md:p-10"
                onClick={onClose}
            >
                <div onClick={(event) => event.stopPropagation()} className="contents">
                    <MediaImage
                        media={current}
                        source="full"
                        fit="contain"
                        loading="eager"
                        alt={current?.alt ?? altText}
                        placeholderClassName="size-48"
                    />
                </div>

                {hasMultiple && (
                    <>
                        <button
                            type="button"
                            tabIndex={open ? 0 : -1}
                            aria-label={__('Previous image')}
                            onClick={(event) => {
                                event.stopPropagation();
                                onIndexChange((index - 1 + count) % count);
                            }}
                            className="absolute start-4 flex size-11 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur transition hover:bg-white/20 no-hover:hidden"
                        >
                            <ChevronLeftIcon size={22} aria-hidden="true" className="rtl:-scale-x-100" />
                        </button>
                        <button
                            type="button"
                            tabIndex={open ? 0 : -1}
                            aria-label={__('Next image')}
                            onClick={(event) => {
                                event.stopPropagation();
                                onIndexChange((index + 1) % count);
                            }}
                            className="absolute end-4 flex size-11 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur transition hover:bg-white/20 no-hover:hidden"
                        >
                            <ChevronRightIcon size={22} aria-hidden="true" className="rtl:-scale-x-100" />
                        </button>
                    </>
                )}
            </div>

            {hasMultiple && (
                <div
                    role="tablist"
                    aria-label={__('Product images')}
                    onKeyDown={onThumbsKeyDown}
                    onClick={(event) => event.stopPropagation()}
                    className="mx-auto no-scrollbar flex max-w-full gap-2 overflow-x-auto px-4 pt-2 pb-4"
                >
                    {media.map((item, thumbIndex) => {
                        const isActive = thumbIndex === index;
                        return (
                            <button
                                key={item.id}
                                ref={(el) => {
                                    thumbRefs.current[thumbIndex] = el;
                                }}
                                type="button"
                                role="tab"
                                aria-controls="lb-image"
                                aria-selected={isActive}
                                aria-label={__('Image :number', { number: thumbIndex + 1 })}
                                tabIndex={open && isActive ? 0 : -1}
                                onClick={() => onIndexChange(thumbIndex)}
                                style={{ aspectRatio: ratio }}
                                className={cn(
                                    'relative h-14 shrink-0 overflow-hidden rounded-sm transition focus-visible:outline-offset-2',
                                    isActive ? 'ring-2 ring-white' : 'opacity-60 hover:opacity-100',
                                )}
                            >
                                <ContainedMedia media={item} source="small" />
                            </button>
                        );
                    })}
                </div>
            )}
        </div>,
        document.body,
    );
}
