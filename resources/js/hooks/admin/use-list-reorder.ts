import { combine } from '@atlaskit/pragmatic-drag-and-drop/combine';
import { draggable, dropTargetForElements } from '@atlaskit/pragmatic-drag-and-drop/element/adapter';
import { pointerOutsideOfPreview } from '@atlaskit/pragmatic-drag-and-drop/element/pointer-outside-of-preview';
import { setCustomNativeDragPreview } from '@atlaskit/pragmatic-drag-and-drop/element/set-custom-native-drag-preview';
import {
    attachInstruction,
    extractInstruction,
    type Instruction,
} from '@atlaskit/pragmatic-drag-and-drop-hitbox/list-item';
import { useEffect, useRef, useState, type ReactNode, type RefObject } from 'react';
import { createRoot } from 'react-dom/client';

interface UseListReorderOptions<TElement extends HTMLElement, THandle extends HTMLElement> {
    ref: RefObject<TElement | null>;
    dragHandleRef: RefObject<THandle | null>;
    index: number;
    isLast: boolean;
    axis: 'vertical' | 'horizontal';
    onReorder: (startIndex: number, finishIndex: number) => void;
    renderPreview?: () => ReactNode;
}

export function useListReorder<TElement extends HTMLElement, THandle extends HTMLElement>({
    ref,
    dragHandleRef,
    index,
    isLast,
    axis,
    onReorder,
    renderPreview,
}: UseListReorderOptions<TElement, THandle>): { isDragging: boolean; instruction: Instruction | null } {
    const [isDragging, setIsDragging] = useState(false);
    const [instruction, setInstruction] = useState<Instruction | null>(null);
    const renderPreviewRef = useRef(renderPreview);

    useEffect(() => {
        renderPreviewRef.current = renderPreview;
    });

    useEffect(() => {
        const element = ref.current;
        const dragHandle = dragHandleRef.current;

        if (!element || !dragHandle) return;

        return combine(
            draggable({
                element: dragHandle,
                getInitialData: () => ({ index }),
                onGenerateDragPreview: ({ nativeSetDragImage }) => {
                    const render = renderPreviewRef.current;
                    if (!render) return;

                    setCustomNativeDragPreview({
                        getOffset: pointerOutsideOfPreview({ x: '16px', y: '8px' }),
                        render: ({ container }) => {
                            const root = createRoot(container);
                            root.render(render());
                            return () => root.unmount();
                        },
                        nativeSetDragImage,
                    });
                },
                onDragStart: () => setIsDragging(true),
                onDrop: () => setIsDragging(false),
            }),
            dropTargetForElements({
                element,
                canDrop: ({ source }) => source.data.index !== index,
                getData: ({ input, element }) =>
                    attachInstruction(
                        { index },
                        {
                            input,
                            element,
                            axis,
                            operations: {
                                'reorder-before': 'available',
                                'reorder-after': isLast ? 'available' : 'not-available',
                            },
                        },
                    ),
                onDragEnter: ({ self }) => setInstruction(extractInstruction(self.data)),
                onDrag: ({ self }) => setInstruction(extractInstruction(self.data)),
                onDragLeave: () => setInstruction(null),
                onDrop: ({ self, source }) => {
                    setInstruction(null);

                    const dropInstruction = extractInstruction(self.data);
                    const sourceIndex = source.data.index;
                    const targetIndex = self.data.index;

                    if (typeof sourceIndex !== 'number' || typeof targetIndex !== 'number' || !dropInstruction) {
                        return;
                    }

                    const draggingDown = sourceIndex < targetIndex;
                    let finishIndex: number;

                    if (dropInstruction.operation === 'reorder-after') {
                        finishIndex = draggingDown ? targetIndex : targetIndex + 1;
                    } else {
                        finishIndex = draggingDown ? targetIndex - 1 : targetIndex;
                    }

                    onReorder(sourceIndex, finishIndex);
                },
            }),
        );
    }, [ref, dragHandleRef, index, isLast, axis, onReorder]);

    return { isDragging, instruction };
}
