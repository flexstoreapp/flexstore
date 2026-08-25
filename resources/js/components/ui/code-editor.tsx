import Prism from 'prismjs';
import type { Ref } from 'react';
import Editor from 'react-simple-code-editor';
import 'prismjs/components/prism-css';
import 'prismjs/components/prism-javascript';
import 'prismjs/themes/prism.css';

import { ReactiveHiddenInput } from '@/components/admin/reactive-hidden-input';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { cn } from '@/lib/utils';

const grammars = {
    css: 'css',
    js: 'javascript',
    html: 'markup',
} as const;

interface CodeEditorProps {
    value: string;
    onChange?: (value: string) => void;
    onBlur?: () => void;
    language?: keyof typeof grammars;
    placeholder?: string;
    disabled?: boolean;
    readOnly?: boolean;
    className?: string;
    minHeight?: string;
    name?: string;
    ref?: Ref<HTMLDivElement>;
}

export function CodeEditor({
    value,
    onChange,
    onBlur,
    language = 'js',
    placeholder,
    disabled,
    readOnly,
    className,
    minHeight = '24rem',
    name,
    ref,
}: CodeEditorProps) {
    const grammar = grammars[language];

    return (
        <ScrollArea
            ref={ref}
            className={cn(
                "code-editor h-100 w-full rounded-md border border-input bg-transparent shadow-xs transition-[color,box-shadow]",
                "focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50",
                readOnly && "bg-muted",
                disabled && "cursor-not-allowed opacity-50",
                className,
            )}
        >
            {name && <ReactiveHiddenInput name={name} value={value} />}
            <Editor
                value={value}
                onValueChange={onChange || (() => {})}
                onBlur={onBlur}
                highlight={(code) => Prism.highlight(code, Prism.languages[grammar], grammar)}
                disabled={disabled || readOnly}
                placeholder={placeholder}
                padding={12}
                style={{
                    fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
                    fontSize: 'var(--code-editor-font-size)',
                    minHeight,
                }}
                textareaClassName="focus:outline-none"
            />
            <ScrollBar orientation="vertical" />
        </ScrollArea>
    );
}

