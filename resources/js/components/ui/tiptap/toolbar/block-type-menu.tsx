import type { Editor } from '@tiptap/react';
import { useEditorState } from '@tiptap/react';
import {
    ChevronDownIcon,
    Heading1Icon,
    Heading2Icon,
    Heading3Icon,
    Heading4Icon,
    Heading5Icon,
    Heading6Icon,
    PilcrowIcon,
    QuoteIcon,
} from 'lucide-react';
import { Toolbar } from 'radix-ui';

import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { __ } from '@/lib/i18n';

type Level = 1 | 2 | 3 | 4 | 5 | 6;

const levels: Level[] = [1, 2, 3, 4, 5, 6];

function headingOptions() {
    return [
        { level: 1 as Level, Icon: Heading1Icon, label: __('Heading 1') },
        { level: 2 as Level, Icon: Heading2Icon, label: __('Heading 2') },
        { level: 3 as Level, Icon: Heading3Icon, label: __('Heading 3') },
        { level: 4 as Level, Icon: Heading4Icon, label: __('Heading 4') },
        { level: 5 as Level, Icon: Heading5Icon, label: __('Heading 5') },
        { level: 6 as Level, Icon: Heading6Icon, label: __('Heading 6') },
    ];
}

export function BlockTypeMenu({ editor }: { editor: Editor }) {
    const state = useEditorState({
        editor,
        selector: (ctx) => ({
            headingLevel: levels.find((level) => ctx.editor.isActive('heading', { level })),
            isBlockquote: ctx.editor.isActive('blockquote'),
            isParagraph: ctx.editor.isActive('paragraph'),
        }),
    });

    const headings = headingOptions();
    const activeHeading = headings.find((heading) => heading.level === state.headingLevel);

    return (
        <DropdownMenu>
            <Toolbar.Button asChild>
                <DropdownMenuTrigger asChild>
                    <Button type="button" variant="outline" className="gap-1 border-none">
                        <span className="text-sm">{activeHeading?.label ?? (state.isBlockquote ? __('Blockquote') : __('Paragraph'))}</span>
                        <ChevronDownIcon />
                    </Button>
                </DropdownMenuTrigger>
            </Toolbar.Button>
            <DropdownMenuContent align="start" className="w-48">
                <DropdownMenuCheckboxItem
                    className="flex items-center gap-2"
                    checked={state.isParagraph}
                    onCheckedChange={() => editor.chain().focus().setParagraph().run()}
                >
                    <PilcrowIcon />
                    {__('Paragraph')}
                </DropdownMenuCheckboxItem>

                {headings.map(({ level, Icon, label }) => (
                    <DropdownMenuCheckboxItem
                        key={level}
                        className="flex items-center gap-2"
                        checked={state.headingLevel === level}
                        onCheckedChange={() => editor.chain().focus().toggleHeading({ level }).run()}
                    >
                        <Icon />
                        {label}
                    </DropdownMenuCheckboxItem>
                ))}

                <DropdownMenuCheckboxItem
                    className="flex items-center gap-2"
                    checked={state.isBlockquote}
                    onCheckedChange={() => editor.chain().focus().toggleBlockquote().run()}
                >
                    <QuoteIcon className="rtl:-scale-x-100" />
                    {__('Blockquote')}
                </DropdownMenuCheckboxItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
