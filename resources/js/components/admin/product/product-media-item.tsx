import { XIcon } from 'lucide-react';
import { useRef } from 'react';

import { DropIndicator } from '@/components/admin/drop-indicator';
import { Button } from '@/components/ui/button';
import { FieldError } from '@/components/ui/field';
import { HelpBlock } from '@/components/ui/help-block';
import type { FileWithPreview } from '@/hooks/admin/use-file-upload';
import { useListReorder } from '@/hooks/admin/use-list-reorder';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { UploadProgress } from '@/types';

interface ProductMediaItemProps {
    file: FileWithPreview;
    index: number;
    isLast: boolean;
    isFeatured: boolean;
    ratio: number;
    removeFile: (id: string) => void;
    uploadProgress: UploadProgress[];
    onReorder: (startIndex: number, endIndex: number) => void;
}

export function ProductMediaItem({
    file,
    index,
    isLast,
    isFeatured,
    ratio,
    removeFile,
    uploadProgress,
    onReorder,
}: ProductMediaItemProps) {
    const ref = useRef<HTMLDivElement>(null);
    const dragHandleRef = useRef<HTMLDivElement>(null);
    const { isDragging, instruction } = useListReorder({
        ref,
        dragHandleRef,
        index,
        isLast,
        axis: 'horizontal',
        onReorder,
    });

    const fileProgress = uploadProgress?.find((p) => p.fileId === file.id);

    return (
        <div
            ref={ref}
            style={{ aspectRatio: ratio }}
            className={cn(
                'group relative overflow-visible rounded-md bg-muted transition-all',
                isDragging && 'opacity-50',
            )}
        >
            <div
                ref={dragHandleRef}
                className={cn(
                    'flex h-full w-full cursor-grab items-center justify-center overflow-hidden rounded-[inherit] border',
                    'transition-transform focus-visible:ring-1 focus-visible:ring-ring',
                    'focus-visible:ring-offset-1 focus-visible:outline-hidden active:scale-95 active:cursor-grabbing',
                )}
            >
                <img
                    src={file.preview}
                    alt={file.file.name}
                    className="pointer-events-none size-full object-contain"
                    loading="lazy"
                    draggable={false}
                />

                {((fileProgress?.completed && !fileProgress?.error) || !fileProgress) && isFeatured && (
                    <div className="absolute start-1 bottom-1 rounded-md bg-secondary px-1.5 py-0.5 text-xs font-medium">
                        {__('Featured')}
                    </div>
                )}
            </div>

            {instruction && (
                <DropIndicator edge={instruction.operation === 'reorder-after' ? 'end' : 'start'} gap={4} />
            )}

            <Button
                onClick={() => removeFile(file.id)}
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

            {fileProgress?.error && <FieldError className="p-2 text-xs">{fileProgress.error}</FieldError>}

            {fileProgress && !fileProgress.error && !fileProgress.completed && (
                <div className="absolute start-0 end-0 bottom-0 flex items-center gap-1 bg-black/50 px-2 py-1.5">
                    <div className="h-2 w-full overflow-hidden rounded-full bg-secondary">
                        <div
                            className="h-full bg-primary transition-all duration-300 ease-out"
                            style={{ width: `${fileProgress.progress}%` }}
                        />
                    </div>
                    <HelpBlock className="text-xs font-medium text-white tabular-nums">{`${fileProgress.progress}%`}</HelpBlock>
                </div>
            )}
        </div>
    );
}
