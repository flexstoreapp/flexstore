import type { Editor } from '@tiptap/react';
import { useEditorState } from '@tiptap/react';
import { ListIcon, ListOrderedIcon } from 'lucide-react';

import { ToolbarToggleGroup, ToolbarToggleItem } from '@/components/ui/tiptap/toolbar/toolbar-primitives';
import { __ } from '@/lib/i18n';

export function ListButtons({ editor }: { editor: Editor }) {
    const active = useEditorState({
        editor,
        selector: ({ editor }) => (editor.isActive('bulletList') ? 'bulletList' : editor.isActive('orderedList') ? 'orderedList' : ''),
    });

    const toggle = (next: string) => {
        const target = next || active;

        if (target === 'bulletList') {
            editor.chain().focus().toggleBulletList().run();
        } else if (target === 'orderedList') {
            editor.chain().focus().toggleOrderedList().run();
        }
    };

    return (
        <ToolbarToggleGroup type="single" value={active} onValueChange={toggle}>
            <ToolbarToggleItem value="bulletList" aria-label={__('Bullet list')} title={__('Bullet list')}>
                <ListIcon />
            </ToolbarToggleItem>
            <ToolbarToggleItem value="orderedList" aria-label={__('Ordered list')} title={__('Ordered list')}>
                <ListOrderedIcon className="rtl:-scale-x-100" />
            </ToolbarToggleItem>
        </ToolbarToggleGroup>
    );
}
