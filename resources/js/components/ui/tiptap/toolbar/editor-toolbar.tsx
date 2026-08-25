import type { Editor } from '@tiptap/react';
import { CodeIcon, EllipsisIcon, EyeIcon, Maximize2Icon, Minimize2Icon } from 'lucide-react';
import { Toolbar } from 'radix-ui';

import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { AlignButtons } from '@/components/ui/tiptap/toolbar/align-buttons';
import { BlockTypeMenu } from '@/components/ui/tiptap/toolbar/block-type-menu';
import { ImagePopover } from '@/components/ui/tiptap/toolbar/image-popover';
import { LinkPopover } from '@/components/ui/tiptap/toolbar/link-popover';
import { ListButtons } from '@/components/ui/tiptap/toolbar/list-buttons';
import { MarkButtons } from '@/components/ui/tiptap/toolbar/mark-buttons';
import { TablePopover } from '@/components/ui/tiptap/toolbar/table-popover';
import { ToolbarSeparator } from '@/components/ui/tiptap/toolbar/toolbar-primitives';
import { useToolbarOverflow } from '@/hooks/admin/use-toolbar-overflow';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface EditorToolbarProps {
    editor: Editor;
    showHtml: boolean;
    isFullscreen: boolean;
    onToggleHtml: () => void;
    onToggleFullscreen: () => void;
}

export function EditorToolbar({ editor, showHtml, isFullscreen, onToggleHtml, onToggleFullscreen }: EditorToolbarProps) {
    const groups = [
        <BlockTypeMenu key="block" editor={editor} />,
        <MarkButtons key="marks" editor={editor} />,
        <ListButtons key="lists" editor={editor} />,
        <AlignButtons key="align" editor={editor} />,
        <div key="insert" className="flex items-center">
            <LinkPopover editor={editor} />
            <ImagePopover editor={editor} />
            <TablePopover editor={editor} />
        </div>,
    ];

    const { containerRef, controlsRef, setItemRef, visibleCount } = useToolbarOverflow(groups.length);

    const overflowing = groups.slice(visibleCount);

    return (
        <Toolbar.Root
            ref={containerRef}
            aria-label={__('Text formatting')}
            className={cn(
                "bg-background sticky top-0 z-10 flex shrink-0 items-center gap-1.5 overflow-hidden border-b px-2 py-1",
                isFullscreen ? "w-screen" : "w-full",
            )}
        >
            {groups.slice(0, visibleCount).map((group, index) => (
                <div key={group.key} ref={setItemRef(index)} className="flex shrink-0 items-center gap-1.5">
                    {index > 0 && <ToolbarSeparator orientation="vertical" />}
                    {group}
                </div>
            ))}

            {overflowing.length > 0 && (
                <Popover>
                    <Toolbar.Button asChild>
                        <PopoverTrigger asChild>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-md"
                                aria-label={__('More formatting options')}
                                title={__('More options')}
                            >
                                <EllipsisIcon />
                            </Button>
                        </PopoverTrigger>
                    </Toolbar.Button>
                    <PopoverContent align="start" className="w-auto p-1.5">
                        <Toolbar.Root aria-label={__('More text formatting')} className="flex flex-wrap items-center gap-1.5">
                            {overflowing}
                        </Toolbar.Root>
                    </PopoverContent>
                </Popover>
            )}

            <div ref={controlsRef} className="ms-auto flex shrink-0 items-center gap-1.5">
                <ToolbarSeparator orientation="vertical" />
                <Toolbar.Button asChild>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-md"
                        onClick={onToggleHtml}
                        aria-label={__('Toggle HTML view')}
                        aria-pressed={showHtml}
                        title={__('Toggle HTML view')}
                    >
                        {showHtml ? <EyeIcon /> : <CodeIcon />}
                    </Button>
                </Toolbar.Button>
                <Toolbar.Button asChild>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-md"
                        onClick={onToggleFullscreen}
                        aria-label={__('Toggle fullscreen')}
                        aria-pressed={isFullscreen}
                        title={__('Toggle fullscreen')}
                    >
                        {isFullscreen ? <Minimize2Icon /> : <Maximize2Icon />}
                    </Button>
                </Toolbar.Button>
            </div>
        </Toolbar.Root>
    );
}
