import type { Editor } from '@tiptap/react';
import { useEditorState } from '@tiptap/react';
import { LinkIcon } from 'lucide-react';
import { Toolbar } from 'radix-ui';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export function LinkPopover({ editor }: { editor: Editor }) {
    const [linkUrl, setLinkUrl] = useState('');

    const isLink = useEditorState({
        editor,
        selector: (ctx) => ctx.editor.isActive('link'),
    });

    const applyLink = () => {
        if (linkUrl) {
            editor.chain().focus().extendMarkRange('link').setLink({ href: linkUrl }).run();

            return;
        }

        editor.chain().focus().extendMarkRange('link').unsetLink().run();
    };

    const removeLink = () => {
        editor.chain().focus().extendMarkRange('link').unsetLink().run();
        setLinkUrl('');
    };

    return (
        <Popover>
            <Toolbar.Button asChild>
                <PopoverTrigger asChild>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-md"
                        aria-label={__('Add link')}
                        aria-pressed={isLink}
                        title={__('Add link')}
                        className={cn(isLink && "bg-accent text-accent-foreground")}
                        onClick={() => setLinkUrl(isLink ? (editor.getAttributes('link').href ?? '') : '')}
                    >
                        <LinkIcon />
                    </Button>
                </PopoverTrigger>
            </Toolbar.Button>
            <PopoverContent className="w-80">
                <div className="flex flex-col gap-4">
                    <FieldLabel htmlFor="link-url">{__('Link URL')}</FieldLabel>
                    <Input
                        id="link-url"
                        value={linkUrl}
                        onChange={(e) => setLinkUrl(e.target.value)}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                applyLink();
                            }
                        }}
                    />
                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="outline" disabled={!isLink} onClick={removeLink}>
                            {__('Remove')}
                        </Button>
                        <Button type="button" onClick={applyLink}>
                            {__('Save')}
                        </Button>
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    );
}
