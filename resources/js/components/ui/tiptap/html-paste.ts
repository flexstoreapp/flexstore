import { Extension } from '@tiptap/core';
import { Plugin, PluginKey } from '@tiptap/pm/state';

const htmlPasteKey = new PluginKey('htmlPaste');

function looksLikeHtml(text: string): boolean {
    return /^\s*<[a-z!/]/i.test(text.trim()) && /<\/[a-z][a-z0-9-]*\s*>|<[a-z][^>]*\/>/i.test(text);
}

export const HtmlPaste = Extension.create({
    name: 'htmlPaste',

    addProseMirrorPlugins() {
        const editor = this.editor;

        return [
            new Plugin({
                key: htmlPasteKey,
                props: {
                    handlePaste: (_view, event) => {
                        const clipboard = event.clipboardData;

                        if (!clipboard || clipboard.types.includes('text/html')) {
                            return false;
                        }

                        const text = clipboard.getData('text/plain');

                        if (!looksLikeHtml(text)) {
                            return false;
                        }

                        editor.commands.insertContent(text, {
                            parseOptions: { preserveWhitespace: false },
                        });

                        return true;
                    },
                },
            }),
        ];
    },
});
