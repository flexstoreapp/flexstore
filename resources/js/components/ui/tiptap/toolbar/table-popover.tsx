import type { Editor } from '@tiptap/react';
import { useEditorState } from '@tiptap/react';
import { TableIcon } from 'lucide-react';
import { Toolbar } from 'radix-ui';

import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { __ } from '@/lib/i18n';

function tableActions() {
    return [
        { key: 'addColumnBefore', label: __('Add column before') },
        { key: 'addColumnAfter', label: __('Add column after') },
        { key: 'addRowBefore', label: __('Add row before') },
        { key: 'addRowAfter', label: __('Add row after') },
        { key: 'deleteColumn', label: __('Delete column') },
        { key: 'deleteRow', label: __('Delete row') },
    ] as const;
}

export function TablePopover({ editor }: { editor: Editor }) {
    const canRun = useEditorState({
        editor,
        selector: ({ editor }) => ({
            addColumnBefore: editor.can().addColumnBefore(),
            addColumnAfter: editor.can().addColumnAfter(),
            addRowBefore: editor.can().addRowBefore(),
            addRowAfter: editor.can().addRowAfter(),
            deleteColumn: editor.can().deleteColumn(),
            deleteRow: editor.can().deleteRow(),
            deleteTable: editor.can().deleteTable(),
        }),
    });

    return (
        <Popover>
            <Toolbar.Button asChild>
                <PopoverTrigger asChild>
                    <Button type="button" variant="ghost" size="icon-md" aria-label={__('Add table')} title={__('Add table')}>
                        <TableIcon />
                    </Button>
                </PopoverTrigger>
            </Toolbar.Button>
            <PopoverContent className="w-70">
                <div className="flex flex-col gap-3">
                    <Button
                        type="button"
                        onClick={() => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()}
                        className="w-full justify-start"
                    >
                        {__('Insert table (3×3)')}
                    </Button>

                    <div className="grid gap-1">
                        <Label className="text-xs font-medium">{__('Table controls')}</Label>
                        <div className="grid grid-cols-1 gap-1.5">
                            {tableActions().map(({ key, label }) => (
                                <Button
                                    key={key}
                                    type="button"
                                    variant="outline"
                                    onClick={() => editor.chain().focus()[key]().run()}
                                    disabled={!canRun[key]}
                                    className="justify-start"
                                >
                                    {label}
                                </Button>
                            ))}
                            <Button
                                type="button"
                                variant="destructive"
                                onClick={() => editor.chain().focus().deleteTable().run()}
                                disabled={!canRun.deleteTable}
                                className="mt-1"
                            >
                                {__('Delete table')}
                            </Button>
                        </div>
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    );
}
