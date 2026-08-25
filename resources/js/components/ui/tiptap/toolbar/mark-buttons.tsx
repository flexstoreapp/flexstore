import type { Editor } from '@tiptap/react';
import { useEditorState } from '@tiptap/react';
import { BoldIcon, ItalicIcon, UnderlineIcon } from 'lucide-react';

import { ToolbarToggleGroup, ToolbarToggleItem } from '@/components/ui/tiptap/toolbar/toolbar-primitives';
import { __ } from '@/lib/i18n';

const marks = ['bold', 'italic', 'underline'] as const;

type Mark = (typeof marks)[number];

export function MarkButtons({ editor }: { editor: Editor }) {
    const active = useEditorState({
        editor,
        selector: ({ editor }) => marks.filter((mark) => editor.isActive(mark)),
    });

    const toggle = (next: string[]) => {
        const changed = marks.find((mark) => active.includes(mark) !== next.includes(mark));

        if (!changed) {
            return;
        }

        const commands: Record<Mark, () => void> = {
            bold: () => editor.chain().focus().toggleBold().run(),
            italic: () => editor.chain().focus().toggleItalic().run(),
            underline: () => editor.chain().focus().toggleUnderline().run(),
        };

        commands[changed]();
    };

    return (
        <ToolbarToggleGroup type="multiple" value={[...active]} onValueChange={toggle}>
            <ToolbarToggleItem value="bold" aria-label={__('Toggle bold')} title={__('Toggle bold')}>
                <BoldIcon />
            </ToolbarToggleItem>
            <ToolbarToggleItem value="italic" aria-label={__('Toggle italic')} title={__('Toggle italic')}>
                <ItalicIcon />
            </ToolbarToggleItem>
            <ToolbarToggleItem value="underline" aria-label={__('Toggle underline')} title={__('Toggle underline')}>
                <UnderlineIcon />
            </ToolbarToggleItem>
        </ToolbarToggleGroup>
    );
}
