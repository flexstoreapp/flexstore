import { ImagePlusIcon, XIcon } from 'lucide-react';
import { useState } from 'react';

import { ReactiveHiddenInput } from '@/components/admin/reactive-hidden-input';
import { Button } from '@/components/ui/button';
import { FieldError } from '@/components/ui/field';
import { HelpBlock } from '@/components/ui/help-block';
import { Input } from '@/components/ui/input';
import { useImageUpload } from '@/hooks/admin/use-image-upload';
import { __ } from '@/lib/i18n';
import { mediaThumb } from '@/lib/media';
import { cn } from '@/lib/utils';
import type { MediaItem } from '@/types/media';

const aspectRatioClasses = {
    square: 'aspect-square',
    video: 'aspect-video',
    portrait: 'aspect-[3/4]',
    landscape: 'aspect-[3/2]',
    auto: 'aspect-auto',
};

const sizeClasses = {
    sm: {
        container: 'h-20 w-20',
        wrapper: 'h-20 w-20',
        icon: 'size-5',
        text: 'text-xs',
        padding: 'gap-1',
    },
    md: {
        container: 'h-32 w-32',
        wrapper: 'h-32 w-32',
        icon: 'size-6',
        text: 'text-xs',
        padding: 'gap-1.5 p-3',
    },
    lg: {
        container: 'w-full',
        wrapper: 'w-full',
        icon: 'size-8',
        text: 'text-sm',
        padding: 'gap-2 p-6',
    },
};

interface InlineImageUploaderProps {
    id: string;
    name?: string;
    value?: MediaItem | null;
    label?: string;
    size?: 'sm' | 'md' | 'lg';
    aspectRatio?: 'square' | 'video' | 'portrait' | 'landscape' | 'auto';
    ratio?: number;
    generateThumbnail?: boolean;
    preserveFormat?: boolean;
    defaultValue?: MediaItem | null;
    onChange?: (media: MediaItem | null) => void;
    error?: string;
    className?: string;
}

export function InlineImageUploader({
    id,
    name,
    value,
    defaultValue,
    error,
    className,
    size = 'md',
    aspectRatio = 'auto',
    ratio,
    label,
    onChange,
    generateThumbnail = false,
    preserveFormat = false,
}: InlineImageUploaderProps) {
    const isControlled = value !== undefined;
    const [internalValue, setInternalValue] = useState<MediaItem | null>(defaultValue ?? null);
    const media = isControlled ? (value ?? null) : internalValue;
    const previewUrl = mediaThumb(media) ?? '';

    const {
        uploading,
        uploadingError,
        isDragging,
        handleFileInputChange,
        handleDragEnter,
        handleDragLeave,
        handleDragOver,
        handleDrop,
        clearError,
    } = useImageUpload({
        generateThumbnail,
        preserveFormat,
        onSuccess: (_imageType: string, item: MediaItem) => {
            if (!isControlled) {
                setInternalValue(item);
            }
            onChange?.(item);
        },
    });

    const removeImage = () => {
        if (!isControlled) {
            setInternalValue(null);
        }
        onChange?.(null);
        clearError(id);
    };

    return (
        <div className={cn('space-y-1', className)}>
            {name && <ReactiveHiddenInput name={name} value={media ? String(media.id) : ''} />}

            {previewUrl ? (
                <div
                    className={cn('group relative', sizeClasses[size].wrapper, ratio !== undefined && 'h-auto')}
                    style={ratio === undefined ? undefined : { aspectRatio: ratio }}
                >
                    <div
                        className={cn(
                            'flex h-full w-full items-center justify-center overflow-hidden rounded-md border',
                            ratio === undefined && aspectRatioClasses[aspectRatio],
                        )}
                    >
                        <img
                            src={previewUrl}
                            className={cn(
                                'object-contain',
                                ratio === undefined ? 'h-auto max-h-full w-auto max-w-full' : 'size-full',
                            )}
                            alt={label}
                            loading="lazy"
                        />
                    </div>
                    <Button
                        onClick={removeImage}
                        size="icon-sm"
                        className={cn(
                            'absolute -end-2 -top-2 rounded-full border-2 border-background',
                            'transition-opacity duration-150 focus-visible:border-background',
                            '[@media(hover:hover)]:pointer-events-none [@media(hover:hover)]:opacity-0',
                            '[@media(hover:hover)]:group-hover:pointer-events-auto [@media(hover:hover)]:group-hover:opacity-100',
                        )}
                        aria-label={__('Remove image')}
                        type="button"
                    >
                        <XIcon />
                    </Button>
                </div>
            ) : (
                <div
                    onDragEnter={(e) => handleDragEnter(e, id)}
                    onDragLeave={(e) => handleDragLeave(e, id)}
                    onDragOver={handleDragOver}
                    onDrop={(e) => handleDrop(e, id)}
                    className="relative"
                >
                    <Input
                        id={id}
                        type="file"
                        className="hidden"
                        accept="image/jpeg,image/jpg,image/png,image/gif,image/webp,image/svg+xml"
                        onChange={(e) => handleFileInputChange(e, id)}
                        disabled={uploading[id]}
                    />
                    <label htmlFor={id} className={cn('cursor-pointer', size === 'lg' ? 'block' : 'inline-block')}>
                        <div
                            className={cn(
                                'flex flex-col items-center justify-center rounded-md',
                                'border-2 border-dashed border-input bg-muted opacity-60 transition-colors hover:bg-muted/50',
                                sizeClasses[size].container,
                                sizeClasses[size].padding,
                                ratio === undefined ? aspectRatioClasses[aspectRatio] : 'h-auto',
                                isDragging[id] && 'bg-primary/5',
                            )}
                            style={ratio === undefined ? undefined : { aspectRatio: ratio }}
                        >
                            <ImagePlusIcon className={cn(sizeClasses[size].icon, 'text-muted-foreground')} />
                            <HelpBlock className={cn('text-center', sizeClasses[size].text)}>
                                {uploading[id]
                                    ? __('Uploading...')
                                    : isDragging[id]
                                      ? __('Drop image')
                                      : size === 'lg'
                                        ? __('Click to upload or drag and drop')
                                        : __('Add image')}
                            </HelpBlock>
                        </div>
                    </label>
                </div>
            )}

            <FieldError className={size !== 'lg' ? 'text-xs' : undefined}>{uploadingError[id] || error}</FieldError>
        </div>
    );
}
