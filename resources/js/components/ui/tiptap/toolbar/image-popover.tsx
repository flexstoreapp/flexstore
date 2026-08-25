import type { Editor } from '@tiptap/react';
import { ImageIcon } from 'lucide-react';
import { Toolbar } from 'radix-ui';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Field, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { __ } from '@/lib/i18n';

export function ImagePopover({ editor }: { editor: Editor }) {
    const [imageUrl, setImageUrl] = useState('');

    const addImageByUrl = () => {
        if (!imageUrl) {
            return;
        }

        editor.chain().focus().setImage({ src: imageUrl }).run();
        setImageUrl('');
    };

    const uploadImage = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];

        if (!file) {
            return;
        }

        const reader = new FileReader();

        reader.onload = (readerEvent) => {
            const result = readerEvent.target?.result;

            if (typeof result === 'string') {
                editor.chain().focus().setImage({ src: result }).run();
            }
        };

        reader.readAsDataURL(file);
    };

    return (
        <Popover>
            <Toolbar.Button asChild>
                <PopoverTrigger asChild>
                    <Button type="button" variant="ghost" size="icon-md" aria-label={__('Add image')} title={__('Add image')}>
                        <ImageIcon />
                    </Button>
                </PopoverTrigger>
            </Toolbar.Button>
            <PopoverContent className="w-80">
                <div className="flex flex-col space-y-6">
                    <Field>
                        <FieldLabel htmlFor="image-url">{__('Image URL')}</FieldLabel>
                        <div className="flex items-center gap-2">
                            <Input
                                id="image-url"
                                value={imageUrl}
                                onChange={(e) => setImageUrl(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        e.preventDefault();
                                        addImageByUrl();
                                    }
                                }}
                            />
                            <Button type="button" onClick={addImageByUrl}>
                                {__('Add')}
                            </Button>
                        </div>
                    </Field>
                    <Field>
                        <FieldLabel htmlFor="image-upload">{__('Or upload an image')}</FieldLabel>
                        <Input
                            id="image-upload"
                            type="file"
                            accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                            onChange={uploadImage}
                        />
                    </Field>
                </div>
            </PopoverContent>
        </Popover>
    );
}
