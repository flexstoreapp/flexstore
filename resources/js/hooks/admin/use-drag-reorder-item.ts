import { combine } from '@atlaskit/pragmatic-drag-and-drop/combine';
import {
    draggable,
    dropTargetForElements,
    type ElementDropTargetGetFeedbackArgs,
} from '@atlaskit/pragmatic-drag-and-drop/element/adapter';
import { pointerOutsideOfPreview } from '@atlaskit/pragmatic-drag-and-drop/element/pointer-outside-of-preview';
import { setCustomNativeDragPreview } from '@atlaskit/pragmatic-drag-and-drop/element/set-custom-native-drag-preview';
import { useEffect, useRef, useState, type ReactNode, type RefObject } from 'react';
import { createRoot } from 'react-dom/client';

type ItemData = Record<string | symbol, unknown>;

interface UseDragReorderItemOptions<TElement extends HTMLElement, TInstruction> {
    ref: RefObject<TElement | null>;
    enabled?: boolean;
    getInitialData: () => ItemData;
    getData: (args: ElementDropTargetGetFeedbackArgs) => ItemData;
    extractInstruction: (data: ItemData) => TInstruction | null;
    canDrop?: (args: ElementDropTargetGetFeedbackArgs) => boolean;
    renderPreview?: () => ReactNode;
}

export function useDragReorderItem<TElement extends HTMLElement, TInstruction>({
    ref,
    enabled = true,
    getInitialData,
    getData,
    extractInstruction,
    canDrop,
    renderPreview,
}: UseDragReorderItemOptions<TElement, TInstruction>): {
    isDragging: boolean;
    instruction: TInstruction | null;
} {
    const [isDragging, setIsDragging] = useState(false);
    const [instruction, setInstruction] = useState<TInstruction | null>(null);

    const getInitialDataRef = useRef(getInitialData);
    const getDataRef = useRef(getData);
    const extractInstructionRef = useRef(extractInstruction);
    const canDropRef = useRef(canDrop);
    const renderPreviewRef = useRef(renderPreview);

    useEffect(() => {
        getInitialDataRef.current = getInitialData;
        getDataRef.current = getData;
        extractInstructionRef.current = extractInstruction;
        canDropRef.current = canDrop;
        renderPreviewRef.current = renderPreview;
    });

    useEffect(() => {
        const element = ref.current;
        if (!element || !enabled) return;

        return combine(
            draggable({
                element,
                getInitialData: () => getInitialDataRef.current(),
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
                getData: (args) => getDataRef.current(args),
                canDrop: (args) => (canDropRef.current ? canDropRef.current(args) : true),
                onDragEnter: ({ self }) => setInstruction(extractInstructionRef.current(self.data)),
                onDrag: ({ self }) => setInstruction(extractInstructionRef.current(self.data)),
                onDragLeave: () => setInstruction(null),
                onDrop: () => setInstruction(null),
            }),
        );
    }, [ref, enabled]);

    return { isDragging, instruction };
}
