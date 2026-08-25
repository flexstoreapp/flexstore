import { EditorContent, useEditor } from '@tiptap/react';
import { lazy, Suspense, useCallback, useEffect, useRef, useState } from 'react';

import { Skeleton } from '@/components/ui/skeleton';
import { richTextExtensions } from '@/components/ui/tiptap/extensions';
import { EditorToolbar } from '@/components/ui/tiptap/toolbar/editor-toolbar';
import { useDebounce } from '@/hooks/use-debounce';
import { cn, formatHtml } from '@/lib/utils';

const CodeEditor = lazy(() => import('@/components/ui/code-editor').then((m) => ({ default: m.CodeEditor })));

interface EditorProps {
    content: string;
    onChange?: (content: string) => void;
}

export function RichTextEditor({ content, onChange }: EditorProps) {
    const [isFullscreen, setIsFullscreen] = useState(false);
    const [showHtml, setShowHtml] = useState(false);
    const [htmlDraft, setHtmlDraft] = useState('');

    const onChangeRef = useRef(onChange);
    const pendingHtml = useRef<string | null>(null);
    const lastEmitted = useRef(content);

    useEffect(() => {
        onChangeRef.current = onChange;
    }, [onChange]);

    const commit = useCallback(() => {
        if (pendingHtml.current === null) {
            return;
        }

        lastEmitted.current = pendingHtml.current;
        onChangeRef.current?.(pendingHtml.current);
        pendingHtml.current = null;
    }, []);

    const scheduleCommit = useDebounce(commit, 300);

    const editor = useEditor({
        extensions: richTextExtensions,
        content,
        onUpdate: ({ editor }) => {
            pendingHtml.current = editor.getHTML();
            scheduleCommit();
        },
    });

    useEffect(() => commit, [commit]);

    useEffect(() => {
        if (content === lastEmitted.current || content === editor.getHTML()) {
            return;
        }

        lastEmitted.current = content;
        editor.commands.setContent(content, { emitUpdate: false });
    }, [content, editor]);

    const applyHtmlDraft = () => {
        editor.commands.setContent(htmlDraft, { emitUpdate: false });
        pendingHtml.current = editor.getHTML();
        commit();
    };

    const toggleHtmlView = () => {
        if (showHtml) {
            applyHtmlDraft();
            setShowHtml(false);

            return;
        }

        commit();
        setHtmlDraft(formatHtml(editor.getHTML()));
        setShowHtml(true);
    };

    const paneClass = isFullscreen ? 'h-[calc(100vh-60px)]' : 'h-auto min-h-0 flex-1';

    return (
        <div
            onBlur={commit}
            className={cn("rich-text-editor flex w-full flex-col overflow-hidden", {
                'border-input focus-within:border-ring focus-within:ring-ring/50 has-focus:border-ring has-focus:ring-ring/50 h-99 rounded-md border shadow-xs transition-[color,box-shadow] focus-within:ring-[3px] has-focus:ring-[3px] md:h-89':
                    !isFullscreen,
                'bg-card is-fullscreen fixed inset-0 z-50': isFullscreen,
            })}
        >
            <EditorToolbar
                editor={editor}
                showHtml={showHtml}
                isFullscreen={isFullscreen}
                onToggleHtml={toggleHtmlView}
                onToggleFullscreen={() => setIsFullscreen(!isFullscreen)}
            />

            {showHtml ? (
                <Suspense fallback={<Skeleton className={paneClass} />}>
                    <CodeEditor
                        value={htmlDraft}
                        language="html"
                        minHeight="100%"
                        onChange={setHtmlDraft}
                        onBlur={applyHtmlDraft}
                        className={cn("rounded-none border-0 shadow-none focus-within:ring-0", paneClass)}
                    />
                </Suspense>
            ) : (
                <EditorContent
                    editor={editor}
                    className={cn(
                        "prose prose-img:m-0 prose-headings:m-0 prose-p:m-0 prose-a:text-blue-600 dark:prose-invert dark:prose-a:text-blue-400",
                        isFullscreen ? "w-screen max-w-none" : "min-h-0 w-full flex-1",
                    )}
                />
            )}
        </div>
    );
}
