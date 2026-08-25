import { monitorForElements } from '@atlaskit/pragmatic-drag-and-drop/element/adapter';
import { extractInstruction, type Instruction } from '@atlaskit/pragmatic-drag-and-drop-hitbox/list-item';
import { AnimatePresence, LayoutGroup, m, type Variants, type Transition } from 'motion/react';
import { useCallback, useEffect } from 'react';

import type { Category } from '@/types';

import { CategoryTreeNode } from './category-tree-node';

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

const enterAnimation: Variants = {
    initial: { opacity: 0, y: -4 },
    animate: { opacity: 1, y: 0 },
    exit: { opacity: 0, y: 4 },
};

interface CategoryTreeProps {
    categories: Category[];
    shouldAnimate: boolean;
    onEdit: (category: Category) => void;
    onAddChild: (parentCategory: Category) => void;
    onReorder: (categoryId: number, newParentId: number | null, newPosition: number) => void;
    onDelete: (category: Category) => void;
}

export function CategoryTree({
    categories,
    shouldAnimate,
    onEdit,
    onAddChild,
    onReorder,
    onDelete,
}: CategoryTreeProps) {
    const flattenTree = useCallback(function flatten(nodes: Category[]): Category[] {
        return nodes.reduce<Category[]>((acc, node) => {
            acc.push(node);
            if (node.children && node.children.length > 0) {
                acc.push(...flatten(node.children));
            }
            return acc;
        }, []);
    }, []);

    const findCategory = useCallback(
        (id: number): Category | null => {
            return flattenTree(categories).find((c) => c.id === id) || null;
        },
        [categories, flattenTree],
    );

    const getDescendantIds = useCallback(
        (categoryId: number): number[] => {
            const category = findCategory(categoryId);
            if (!category || !category.children || category.children.length === 0) {
                return [];
            }

            const descendants: number[] = [];
            const collectDescendants = (children: Category[]) => {
                children.forEach((child) => {
                    descendants.push(child.id);
                    if (child.children && child.children.length > 0) {
                        collectDescendants(child.children);
                    }
                });
            };

            collectDescendants(category.children);
            return descendants;
        },
        [findCategory],
    );

    const isValidDrop = useCallback(
        (draggedId: number, targetId: number): boolean => {
            if (draggedId === targetId) return false;

            const descendantIds = getDescendantIds(draggedId);
            return !descendantIds.includes(targetId);
        },
        [getDescendantIds],
    );

    const getSiblings = useCallback(
        (parentId: number | null): Category[] => {
            if (parentId === null) {
                return categories.filter((c) => c.parent_id === null);
            }
            const parent = findCategory(parentId);
            return parent?.children || [];
        },
        [categories, findCategory],
    );

    useEffect(() => {
        return monitorForElements({
            onDrop({ location, source }) {
                if (!location.current.dropTargets.length) return;

                const draggedId = source.data.id as number;
                const target = location.current.dropTargets[0];
                const targetId = target.data.id as number;

                const draggedCategory = findCategory(draggedId);
                const targetCategory = findCategory(targetId);

                if (!draggedCategory || !targetCategory) return;
                if (!isValidDrop(draggedId, targetId)) return;

                const instruction: Instruction | null = extractInstruction(target.data);

                if (instruction === null) return;

                let newParentId: number | null;
                let newPosition: number;

                if (instruction.operation === 'combine') {
                    newParentId = targetId;
                    newPosition = 0;
                } else if (instruction.operation === 'reorder-before') {
                    newParentId = targetCategory.parent_id;
                    const siblings = getSiblings(newParentId);
                    const targetIndex = siblings.findIndex((c) => c.id === targetId);

                    if (draggedCategory.parent_id === newParentId) {
                        const draggedIndex = siblings.findIndex((c) => c.id === draggedId);
                        newPosition = draggedIndex < targetIndex ? targetIndex - 1 : targetIndex;
                    } else {
                        newPosition = targetIndex;
                    }
                } else {
                    newParentId = targetCategory.parent_id;
                    const siblings = getSiblings(newParentId);
                    const targetIndex = siblings.findIndex((c) => c.id === targetId);

                    if (draggedCategory.parent_id === newParentId) {
                        const draggedIndex = siblings.findIndex((c) => c.id === draggedId);
                        newPosition = draggedIndex < targetIndex ? targetIndex : targetIndex + 1;
                    } else {
                        newPosition = targetIndex + 1;
                    }
                }

                onReorder(draggedId, newParentId, newPosition);
            },
        });
    }, [categories, findCategory, getSiblings, isValidDrop, onReorder]);

    const categoryList = categories.map((category) => (
        <m.div
            key={category.id}
            layout={shouldAnimate}
            transition={shouldAnimate ? animationTransition : noAnimationTransition}
            {...(shouldAnimate && enterAnimation)}
        >
            <CategoryTreeNode
                category={category}
                shouldAnimate={shouldAnimate}
                onAddChild={onAddChild}
                onEdit={onEdit}
                onDelete={onDelete}
            />
        </m.div>
    ));

    return (
        <LayoutGroup>
            <m.div
                layout={shouldAnimate}
                transition={shouldAnimate ? animationTransition : noAnimationTransition}
                className="space-y-2"
            >
                {shouldAnimate ? <AnimatePresence initial={false}>{categoryList}</AnimatePresence> : categoryList}
            </m.div>
        </LayoutGroup>
    );
}
