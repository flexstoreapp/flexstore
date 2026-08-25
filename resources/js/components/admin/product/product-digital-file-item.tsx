import { GripVerticalIcon, XIcon } from 'lucide-react';
import { useRef } from 'react';

import { DropIndicator } from '@/components/admin/drop-indicator';
import { FileTypeIcon } from '@/components/admin/file-type-icon';
import { HoverActions } from '@/components/admin/hover-actions';
import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import type { AdaptiveSelectOption } from '@/components/ui/adaptive-select';
import { Button } from '@/components/ui/button';
import { FieldError } from '@/components/ui/field';
import { HelpBlock } from '@/components/ui/help-block';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useListReorder } from '@/hooks/admin/use-list-reorder';
import { __ } from '@/lib/i18n';
import { cn, humanFileSize } from '@/lib/utils';

export interface DigitalFileRow {
    key: string;
    id: string | null;
    variant_id: string | null;
    name: string;
    media_id: number | null;
    original_filename: string;
    file_size: number;
    mime_type: string | null;
    uploading: boolean;
    error: string | null;
}

interface ProductDigitalFileItemProps {
    file: DigitalFileRow;
    index: number;
    isLast: boolean;
    variantOptions: AdaptiveSelectOption[];
    onNameChange: (key: string, name: string) => void;
    onVariantChange: (key: string, variantId: string) => void;
    onRemove: (key: string) => void;
    onReorder: (startIndex: number, endIndex: number) => void;
}

function DigitalFileDragPreview({ file }: { file: DigitalFileRow }) {
    return (
        <div className="flex items-center gap-3 rounded-md border bg-popover p-3">
            <div className="flex size-8 items-center justify-center rounded-md border bg-muted text-muted-foreground">
                <FileTypeIcon
                    mimeType={file.mime_type}
                    filename={file.original_filename}
                    className="size-4"
                    strokeWidth={1.5}
                />
            </div>
            <p className="max-w-40 truncate text-sm font-medium">{file.name}</p>
        </div>
    );
}

export function ProductDigitalFileItem({
    file,
    index,
    isLast,
    variantOptions,
    onNameChange,
    onVariantChange,
    onRemove,
    onReorder,
}: ProductDigitalFileItemProps) {
    const ref = useRef<HTMLDivElement>(null);
    const dragHandleRef = useRef<HTMLButtonElement>(null);
    const { isDragging, instruction } = useListReorder({
        ref,
        dragHandleRef,
        index,
        isLast,
        axis: 'vertical',
        onReorder,
        renderPreview: () => <DigitalFileDragPreview file={file} />,
    });

    return (
        <div
            ref={ref}
            className={cn(
                'group/item relative rounded-lg border bg-background p-3 transition-all',
                isDragging && 'opacity-50',
                file.error && 'border-destructive',
            )}
        >
            {instruction && (
                <DropIndicator edge={instruction.operation === 'reorder-after' ? 'bottom' : 'top'} gap={3} />
            )}

            <div className="flex items-start gap-3">
                <button
                    ref={dragHandleRef}
                    type="button"
                    className={cn(
                        'mt-1.5 flex shrink-0 cursor-grab touch-none items-center justify-center text-muted-foreground',
                        'rounded-sm outline-none focus-visible:ring-2 focus-visible:ring-ring active:cursor-grabbing',
                    )}
                    aria-label={__('Reorder file')}
                >
                    <GripVerticalIcon className="size-4" />
                </button>

                <div className="flex size-10 shrink-0 items-center justify-center rounded-md border bg-muted text-muted-foreground">
                    {file.uploading ? (
                        <Spinner />
                    ) : (
                        <FileTypeIcon
                            mimeType={file.mime_type}
                            filename={file.original_filename}
                            className="size-5"
                            strokeWidth={1.5}
                        />
                    )}
                </div>

                <div className="min-w-0 flex-1 space-y-2">
                    <div className="space-y-0.5">
                        <Input
                            value={file.name}
                            onChange={(e) => onNameChange(file.key, e.target.value)}
                            placeholder={__('File name')}
                            aria-label={__('File name')}
                            className="max-w-xs"
                        />

                        <div className="flex flex-wrap items-center gap-x-2 gap-y-1 text-muted-foreground">
                            <HelpBlock className="truncate text-xs">{file.original_filename}</HelpBlock>
                            <span aria-hidden>·</span>
                            <HelpBlock className="shrink-0 text-xs tabular-nums">
                                {humanFileSize(file.file_size)}
                            </HelpBlock>
                        </div>
                    </div>

                    {variantOptions.length > 0 && (
                        <AdaptiveSelect
                            value={file.variant_id ?? ''}
                            onValueChange={(value) => onVariantChange(file.key, value)}
                            options={variantOptions}
                            aria-label={__('Assign to variant')}
                            className="max-w-xs"
                        />
                    )}

                    {file.error && <FieldError>{file.error}</FieldError>}
                </div>

                <HoverActions className="shrink-0 self-center">
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        onClick={() => onRemove(file.key)}
                        aria-label={__('Remove file')}
                    >
                        <XIcon />
                    </Button>
                </HoverActions>
            </div>
        </div>
    );
}
