import { Table } from '@tiptap/extension-table';
import { TableCell } from '@tiptap/extension-table-cell';
import { TableHeader } from '@tiptap/extension-table-header';
import { TableRow } from '@tiptap/extension-table-row';
import { TextAlign } from '@tiptap/extension-text-align';
import { StarterKit } from '@tiptap/starter-kit';

import { HtmlPaste } from '@/components/ui/tiptap/html-paste';
import { ImageResize } from '@/components/ui/tiptap/image-resize';

export const richTextExtensions = [
    StarterKit.configure({
        link: {
            openOnClick: false,
            autolink: true,
        },
    }),
    TextAlign.configure({
        types: ['heading', 'paragraph'],
    }),
    ImageResize.configure({
        allowBase64: true,
    }),
    Table.configure({
        resizable: true,
    }),
    TableRow,
    TableHeader,
    TableCell,
    HtmlPaste,
];
