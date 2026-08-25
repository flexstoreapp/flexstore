import { Image } from '@tiptap/extension-image';
import { NodeViewWrapper, ReactNodeViewRenderer } from '@tiptap/react';
import type { NodeViewProps } from '@tiptap/react';
import { AlignLeftIcon, AlignCenterIcon, AlignRightIcon } from 'lucide-react';
import * as React from 'react';

import { __ } from '@/lib/i18n';
import { cn, cssToJs } from '@/lib/utils';

const iconBtn = 'w-6 h-6 flex items-center justify-center rounded hover:bg-accent hover:text-accent-foreground transition';

type Corner = 'nw' | 'ne' | 'sw' | 'se';
type Align = 'left' | 'center' | 'right';

const handlePosition: Record<string, string> = {
    nw: 'top-0 left-0',
    ne: 'top-0 right-0',
    sw: 'bottom-0 left-0',
    se: 'bottom-0 right-0',
};

const handleCursor: Record<string, string> = {
    nw: 'cursor-nwse-resize',
    ne: 'cursor-nesw-resize',
    sw: 'cursor-nesw-resize',
    se: 'cursor-nwse-resize',
};

const handleStyle: Record<string, string> = {
    nw: 'translate(-50%,-50%)',
    ne: 'translate(50%,-50%)',
    sw: 'translate(-50%,50%)',
    se: 'translate(50%,50%)',
};

const handleAlignStyle: Record<string, string> = {
    left: 'margin: 0px auto 0px 0px',
    center: 'margin: 0px auto',
    right: 'margin: 0px 0px 0px auto',
};

const ResizeHandle = ({ onMouseDown, corner }: { onMouseDown: (e: React.MouseEvent<HTMLDivElement>) => void; corner: Corner }) => (
    <div
        className={cn(
            "absolute z-50 h-3 w-3 rounded-sm border-2 border-white bg-blue-500 shadow-md transition-colors dark:bg-blue-600",
            handlePosition[corner],
            handleCursor[corner],
        )}
        onMouseDown={onMouseDown}
        style={{ transform: handleStyle[corner] }}
    />
);

const ImageNodeView = (props: NodeViewProps) => {
    const containerRef = React.useRef<HTMLDivElement>(null);
    const imgRef = React.useRef<HTMLImageElement>(null);
    const [showControls, setShowControls] = React.useState(false);
    const [width, setWidth] = React.useState<number | undefined>(undefined);
    const [align, setAlign] = React.useState<Align>('left');

    const styleObj = cssToJs(props.node.attrs.style);

    React.useEffect(() => {
        if (styleObj.width) {
            const parsedWidth = parseInt(styleObj.width.replace('px', ''));
            if (!isNaN(parsedWidth) && parsedWidth > 0) {
                setWidth(parsedWidth);
            }
        }

        const marginStyle = styleObj.margin;
        if (marginStyle) {
            const alignment = Object.keys(handleAlignStyle).find((key): key is Align => handleAlignStyle[key] === `margin: ${marginStyle}`);
            if (alignment) {
                setAlign(alignment);
            }
        }
    }, [styleObj]);

    const updateAttrs = (newWidth: number | undefined, align: Align) => {
        const style = cn(
            'display: block; height: auto; cursor: pointer;',
            newWidth ? `width: ${newWidth}px;` : 'width: 100%',
            handleAlignStyle[align],
        );

        props.updateAttributes({ style });
    };

    const startResize = (e: React.MouseEvent<HTMLDivElement>, corner: Corner) => {
        e.preventDefault();
        e.stopPropagation();

        const startX = e.clientX;
        const startWidth = width ?? imgRef.current?.offsetWidth ?? 200;

        const onMouseMove = (moveEvent: MouseEvent) => {
            const deltaX = moveEvent.clientX - startX;
            let newWidth = startWidth;

            if (corner === 'nw') {
                newWidth = Math.max(50, startWidth - deltaX);
            } else if (corner === 'ne') {
                newWidth = Math.max(50, startWidth + deltaX);
            } else if (corner === 'sw') {
                newWidth = Math.max(50, startWidth - deltaX);
            } else if (corner === 'se') {
                newWidth = Math.max(50, startWidth + deltaX);
            }

            setWidth(newWidth);
            updateAttrs(newWidth, align);
        };

        const onMouseUp = () => {
            window.removeEventListener('mousemove', onMouseMove);
            window.removeEventListener('mouseup', onMouseUp);
        };

        window.addEventListener('mousemove', onMouseMove);
        window.addEventListener('mouseup', onMouseUp);
    };

    const handleAlign = (align: Align) => {
        setAlign(align);
        updateAttrs(width, align);
    };

    return (
        <NodeViewWrapper
            ref={containerRef}
            className="group relative flex flex-col"
            onMouseEnter={() => setShowControls(true)}
            onMouseLeave={() => setShowControls(false)}
        >
            <div
                className={cn(
                    "flex w-full",
                    align === 'left' && "justify-start",
                    align === 'center' && "justify-center",
                    align === 'right' && "justify-end",
                )}
            >
                <div className="relative inline-block">
                    <img
                        ref={imgRef}
                        src={props.node.attrs.src}
                        alt={props.node.attrs.alt}
                        style={{
                            ...styleObj,
                            minWidth: '50px',
                            maxWidth: '100%',
                        }}
                        className="border-muted block rounded border"
                        loading="lazy"
                        draggable={false}
                    />
                    {showControls && (
                        <>
                            <ResizeHandle corner="nw" onMouseDown={(e) => startResize(e, 'nw')} />
                            <ResizeHandle corner="ne" onMouseDown={(e) => startResize(e, 'ne')} />
                            <ResizeHandle corner="sw" onMouseDown={(e) => startResize(e, 'sw')} />
                            <ResizeHandle corner="se" onMouseDown={(e) => startResize(e, 'se')} />

                            <div className="bg-background/90 border-muted absolute top-0 start-1/2 z-40 flex -translate-x-1/2 rtl:translate-x-1/2 -translate-y-1/2 gap-1 rounded-md border p-1 shadow-lg backdrop-blur">
                                <button
                                    type="button"
                                    onClick={() => handleAlign('left')}
                                    className={iconBtn + (align === 'left' ? ' bg-accent' : '')}
                                    title={__('Align left')}
                                >
                                    <AlignLeftIcon size={18} />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handleAlign('center')}
                                    className={iconBtn + (align === 'center' ? ' bg-accent' : '')}
                                    title={__('Align center')}
                                >
                                    <AlignCenterIcon size={18} />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handleAlign('right')}
                                    className={iconBtn + (align === 'right' ? ' bg-accent' : '')}
                                    title={__('Align right')}
                                >
                                    <AlignRightIcon size={18} />
                                </button>
                            </div>
                        </>
                    )}
                </div>
            </div>
        </NodeViewWrapper>
    );
};

export const ImageResize = Image.extend({
    inline: false,
    addNodeView() {
        return ReactNodeViewRenderer(ImageNodeView);
    },
    addAttributes() {
        return {
            src: {
                default: null,
                parseHTML: (element: HTMLImageElement) => element.getAttribute('src'),
                renderHTML: (element: HTMLImageElement) => {
                    if (!element.src) return {};

                    return { src: element.src };
                },
            },
            alt: {
                default: null,
                parseHTML: (element: HTMLImageElement) => element.getAttribute('alt'),
                renderHTML: (element: HTMLImageElement) => {
                    if (!element.alt) return {};

                    return { alt: element.alt };
                },
            },
            title: {
                default: null,
                parseHTML: (element: HTMLImageElement) => element.getAttribute('title'),
                renderHTML: (element: HTMLImageElement) => {
                    if (!element.title) return {};

                    return { title: element.title };
                },
            },
            style: {
                default: null,
                parseHTML: (element: HTMLImageElement) => element.style.cssText,
                renderHTML: (element: HTMLImageElement) => {
                    if (!element.style) return {};

                    return { style: element.style };
                },
            },
        };
    },
});

