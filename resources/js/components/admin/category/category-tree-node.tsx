import {
    attachInstruction,
    extractInstruction,
    type Instruction,
} from '@atlaskit/pragmatic-drag-and-drop-hitbox/list-item';
import { GripVerticalIcon, PencilIcon, PlusIcon, Trash2Icon } from 'lucide-react';
import { m, type Transition } from 'motion/react';
import { useRef } from 'react';

import { DropIndicator } from '@/components/admin/drop-indicator';
import { HoverActions } from '@/components/admin/hover-actions';
import { StatusBadge } from '@/components/admin/status-badge';
import { Button } from '@/components/ui/button';
import { Can } from '@/components/ui/can';
import { Item, ItemActions, ItemContent, ItemMedia, ItemTitle } from '@/components/ui/item';
import { useDragReorderItem } from '@/hooks/admin/use-drag-reorder-item';
import { usePermissions } from '@/hooks/admin/use-permissions';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import { cn, getTranslation } from '@/lib/utils';
import type { Category } from '@/types';

const animationTransition: Transition = {
    type: 'tween',
    ease: 'easeInOut',
    duration: 0.24,
};

const noAnimationTransition: Transition = {
    type: 'tween',
    ease: 'linear',
    duration: 0,
};

interface CategoryTreeNodeProps {
    category: Category;
    depth?: number;
    shouldAnimate: boolean;
    onAddChild: (parentCategory: Category) => void;
    onEdit: (category: Category) => void;
    onDelete: (category: Category) => void;
}

function DragPreview({ category }: { category: Category }) {
    return (
        <div className={cn('flex items-center gap-2 rounded-md border bg-popover p-3')}>
            <GripVerticalIcon className="size-4 shrink-0 text-muted-foreground" />
            <div className="flex items-center gap-3">
                <p className="truncate text-sm font-medium text-popover-foreground">{getTranslation(category.name)}</p>
                {!category.is_active && <StatusBadge status="inactive">{__('Inactive')}</StatusBadge>}
            </div>
        </div>
    );
}

export function CategoryTreeNode({
    category,
    depth = 0,
    shouldAnimate,
    onAddChild,
    onEdit,
    onDelete,
}: CategoryTreeNodeProps) {
    const { hasPermission } = usePermissions();
    const canUpdate = hasPermission(Permission.CategoriesManage);
    const buttonRef = useRef<HTMLDivElement>(null);
    const { isDragging, instruction } = useDragReorderItem<HTMLDivElement, Instruction>({
        ref: buttonRef,
        enabled: canUpdate,
        getInitialData: () => ({ id: category.id }),
        getData: ({ input, element }) =>
            attachInstruction(
                { id: category.id },
                { input, element, operations: { 'reorder-before': 'available', combine: 'available' } },
            ),
        extractInstruction,
        canDrop: ({ source }) => source.data.id !== category.id,
        renderPreview: () => <DragPreview category={category} />,
    });

    return (
        <m.div
            layout={shouldAnimate}
            className="relative"
            transition={shouldAnimate ? animationTransition : noAnimationTransition}
        >
            <m.div
                layout={shouldAnimate}
                ref={buttonRef}
                className={cn('group/item relative', isDragging && 'opacity-40')}
                transition={shouldAnimate ? animationTransition : noAnimationTransition}
            >
                {depth > 0 && <div className="absolute -start-6 top-1/2 h-px w-6 -translate-y-1/2 bg-border" />}

                <m.div
                    layout={shouldAnimate}
                    className={cn(instruction?.operation === 'combine' && 'relative z-10 rounded-md bg-muted ring-2')}
                    transition={shouldAnimate ? animationTransition : noAnimationTransition}
                >
                    <Item
                        variant="outline"
                        size="sm"
                        className={cn(
                            'transition-transform hover:bg-muted/50',
                            canUpdate && 'cursor-grab active:cursor-grabbing',
                        )}
                    >
                        <Can permission={Permission.CategoriesManage}>
                            <ItemMedia>
                                <GripVerticalIcon className="size-4 text-muted-foreground" />
                            </ItemMedia>
                        </Can>

                        <ItemContent className="min-w-32">
                            <ItemTitle className="flex items-center gap-3">
                                <p>{getTranslation(category.name)}</p>
                                {!category.is_active && <StatusBadge status="inactive">{__('Inactive')}</StatusBadge>}
                            </ItemTitle>
                        </ItemContent>

                        <ItemActions>
                            <HoverActions>
                                <Can permission={Permission.CategoriesManage}>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="h-7"
                                        onClick={() => onAddChild(category)}
                                    >
                                        <PlusIcon />
                                    </Button>
                                </Can>

                                <Can permission={Permission.CategoriesManage}>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="h-7"
                                        onClick={() => onEdit(category)}
                                    >
                                        <PencilIcon />
                                    </Button>
                                </Can>

                                <Can permission={Permission.CategoriesDelete}>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="h-7"
                                        onClick={() => onDelete(category)}
                                    >
                                        <Trash2Icon />
                                    </Button>
                                </Can>
                            </HoverActions>
                        </ItemActions>
                    </Item>
                </m.div>

                {instruction?.operation === 'reorder-before' && <DropIndicator edge="top" />}
            </m.div>

            {category.children && category.children.length > 0 && (
                <m.div
                    layout={shouldAnimate}
                    transition={shouldAnimate ? animationTransition : noAnimationTransition}
                    className="relative ms-6 border-s border-border ps-6"
                >
                    {category.children.map((child) => (
                        <m.div
                            key={child.id}
                            layout={shouldAnimate}
                            transition={shouldAnimate ? animationTransition : noAnimationTransition}
                            className="pt-2"
                        >
                            <CategoryTreeNode
                                category={child}
                                depth={depth + 1}
                                shouldAnimate={shouldAnimate}
                                onEdit={onEdit}
                                onDelete={onDelete}
                                onAddChild={onAddChild}
                            />
                        </m.div>
                    ))}
                </m.div>
            )}
        </m.div>
    );
}
