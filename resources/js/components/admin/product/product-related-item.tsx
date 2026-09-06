import { GripVerticalIcon, XIcon } from 'lucide-react';
import { useRef } from 'react';

import { DropIndicator } from '@/components/admin/drop-indicator';
import { HoverActions } from '@/components/admin/hover-actions';
import type { SelectableItem } from '@/components/admin/product-picker';
import { Thumbnail } from '@/components/admin/thumbnail';
import { Button } from '@/components/ui/button';
import { useListReorder } from '@/hooks/admin/use-list-reorder';
import { useFormatMoney } from '@/hooks/use-format-money';
import { __ } from '@/lib/i18n';
import { mediaAlt, mediaSmallThumb } from '@/lib/media';
import { cn, getTranslation } from '@/lib/utils';

interface ProductRelatedItemProps {
    name: string;
    item: SelectableItem;
    index: number;
    isLast: boolean;
    onRemove: (id: number) => void;
    onReorder: (startIndex: number, endIndex: number) => void;
}

function formatItemPrice(item: SelectableItem, formatMoney: (value: string) => string): string | null {
    if (item.price != null) {
        return formatMoney(item.price);
    }

    if (item.price_range) {
        return `${formatMoney(item.price_range[0])} – ${formatMoney(item.price_range[1])}`;
    }

    return null;
}

function ProductRelatedDragPreview({ item }: { item: SelectableItem }) {
    const title = getTranslation(item.title);

    return (
        <div className="flex items-center gap-3 rounded-md border bg-popover p-3">
            <Thumbnail src={mediaSmallThumb(item.featured_media)} alt={mediaAlt(item.featured_media, title)} />
            <p className="max-w-40 truncate text-sm font-medium">{title}</p>
        </div>
    );
}

export function ProductRelatedItem({ name, item, index, isLast, onRemove, onReorder }: ProductRelatedItemProps) {
    const ref = useRef<HTMLDivElement>(null);
    const dragHandleRef = useRef<HTMLButtonElement>(null);
    const { formatMoney } = useFormatMoney();
    const { isDragging, instruction } = useListReorder({
        ref,
        dragHandleRef,
        index,
        isLast,
        axis: 'vertical',
        onReorder,
        renderPreview: () => <ProductRelatedDragPreview item={item} />,
    });

    const title = getTranslation(item.title);
    const price = formatItemPrice(item, formatMoney);

    return (
        <div
            ref={ref}
            className={cn(
                'group/item relative flex items-center gap-3 rounded-lg border bg-background p-3 transition-all',
                isDragging && 'opacity-50',
            )}
        >
            <input type="hidden" name={`${name}[]`} value={item.id} />

            {instruction && (
                <DropIndicator edge={instruction.operation === 'reorder-after' ? 'bottom' : 'top'} gap={3} />
            )}

            <button
                ref={dragHandleRef}
                type="button"
                className={cn(
                    'flex shrink-0 cursor-grab touch-none items-center justify-center text-muted-foreground',
                    'rounded-sm outline-none focus-visible:ring-2 focus-visible:ring-ring active:cursor-grabbing',
                )}
                aria-label={__('Reorder product')}
            >
                <GripVerticalIcon className="size-4" />
            </button>

            <Thumbnail src={mediaSmallThumb(item.featured_media)} alt={mediaAlt(item.featured_media, title)} />

            <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium">{title}</p>
                {price && <p className="text-xs text-muted-foreground tabular-nums">{price}</p>}
            </div>

            <HoverActions className="shrink-0">
                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    onClick={() => onRemove(item.id)}
                    aria-label={__('Remove product')}
                >
                    <XIcon />
                </Button>
            </HoverActions>
        </div>
    );
}
