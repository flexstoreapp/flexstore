import type { Editor } from '@tiptap/react';
import { useEditorState } from '@tiptap/react';
import { AlignCenterIcon, AlignLeftIcon, AlignRightIcon } from 'lucide-react';

import { ToolbarToggleGroup, ToolbarToggleItem } from '@/components/ui/tiptap/toolbar/toolbar-primitives';
import { __ } from '@/lib/i18n';

const alignments = ['left', 'center', 'right'] as const;

export function AlignButtons({ editor }: { editor: Editor }) {
    const active = useEditorState({
        editor,
        selector: ({ editor }) => alignments.find((alignment) => editor.isActive({ textAlign: alignment })) ?? '',
    });

    const align = (next: string) => {
        if (next) {
            editor.chain().focus().setTextAlign(next).run();

            return;
        }

        editor.chain().focus().unsetTextAlign().run();
    };

    return (
        <ToolbarToggleGroup type="single" value={active} onValueChange={align}>
            <ToolbarToggleItem value="left" aria-label={__('Align left')} title={__('Align left')}>
                <AlignLeftIcon />
            </ToolbarToggleItem>
            <ToolbarToggleItem value="center" aria-label={__('Align center')} title={__('Align center')}>
                <AlignCenterIcon />
            </ToolbarToggleItem>
            <ToolbarToggleItem value="right" aria-label={__('Align right')} title={__('Align right')}>
                <AlignRightIcon />
            </ToolbarToggleItem>
        </ToolbarToggleGroup>
    );
}
