import { PlusIcon } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Popover, PopoverAnchor, PopoverContent } from '@/components/ui/popover';
import { RemovableTag } from '@/components/ui/removable-tag';
import { ScrollArea } from '@/components/ui/scroll-area';
import { cn } from '@/lib/utils';

interface TagSuggestion {
    value: string;
    label: string;
}

interface TagsInputProps extends React.HTMLAttributes<HTMLDivElement> {
    tags: string[];
    onAdd: (value: string) => void;
    onRemove: (index: number) => void;
    placeholder?: string;
    id?: string;
    suggestions?: TagSuggestion[];
    getTagLabel?: (tag: string) => string;
    onDraftChange?: (value: string) => void;
}

export function TagsInput({
    tags,
    onAdd,
    onRemove,
    placeholder = '',
    id,
    suggestions,
    getTagLabel,
    onDraftChange,
    className,
    ...props
}: TagsInputProps) {
    const [inputValue, setInputValue] = useState('');
    const [open, setOpen] = useState(false);
    const [activeIndex, setActiveIndex] = useState(0);
    const listRef = useRef<HTMLUListElement>(null);
    const keyboardNavRef = useRef(false);

    const filteredSuggestions = useMemo(() => {
        if (!suggestions) return [];
        const query = inputValue.trim().toLowerCase();
        const selected = new Set(tags);
        return suggestions
            .filter((suggestion) => !selected.has(suggestion.value))
            .filter(
                (suggestion) =>
                    !query ||
                    suggestion.label.toLowerCase().includes(query) ||
                    suggestion.value.toLowerCase().includes(query),
            );
    }, [suggestions, inputValue, tags]);

    const showDropdown = open && suggestions !== undefined && filteredSuggestions.length > 0;
    const safeActiveIndex = Math.min(activeIndex, Math.max(0, filteredSuggestions.length - 1));

    useEffect(() => {
        if (!showDropdown || !keyboardNavRef.current) return;
        keyboardNavRef.current = false;
        listRef.current?.querySelector('[aria-selected="true"]')?.scrollIntoView({ block: 'nearest' });
    }, [safeActiveIndex, showDropdown]);

    const setDraft = (value: string) => {
        setInputValue(value);
        onDraftChange?.(value);
    };

    const addValue = (value: string) => {
        onAdd(value);
        setDraft('');
        setActiveIndex(0);
    };

    const commitInput = () => {
        const value = inputValue.trim();
        if (!value) return;

        const match = suggestions?.find(
            (suggestion) =>
                suggestion.label.toLowerCase() === value.toLowerCase() ||
                suggestion.value.toLowerCase() === value.toLowerCase(),
        );

        if (match) {
            addValue(match.value);
        } else {
            onAdd(value);
            setDraft('');
            setActiveIndex(0);
        }
    };

    const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (showDropdown && e.key === 'ArrowDown') {
            e.preventDefault();
            keyboardNavRef.current = true;
            setActiveIndex(Math.min(safeActiveIndex + 1, filteredSuggestions.length - 1));
            return;
        }

        if (showDropdown && e.key === 'ArrowUp') {
            e.preventDefault();
            keyboardNavRef.current = true;
            setActiveIndex(Math.max(safeActiveIndex - 1, 0));
            return;
        }

        if (e.key === 'Escape') {
            setOpen(false);
            return;
        }

        if (e.key === 'Enter') {
            e.preventDefault();
            const active = showDropdown ? filteredSuggestions[safeActiveIndex] : undefined;
            if (active) {
                addValue(active.value);
            } else {
                commitInput();
            }
        }
    };

    return (
        <div className={cn('space-y-3', className)} {...props}>
            <div className="flex items-center gap-2">
                <Popover open={showDropdown}>
                    <PopoverAnchor asChild>
                        <div className="flex-1">
                            <Input
                                id={id}
                                value={inputValue}
                                autoComplete="off"
                                role={suggestions ? 'combobox' : undefined}
                                aria-expanded={suggestions ? showDropdown : undefined}
                                aria-autocomplete={suggestions ? 'list' : undefined}
                                onChange={(e) => {
                                    setDraft(e.target.value);
                                    setActiveIndex(0);
                                    setOpen(true);
                                }}
                                onFocus={() => setOpen(true)}
                                onBlur={() => setOpen(false)}
                                onKeyDown={handleKeyDown}
                                placeholder={placeholder}
                            />
                        </div>
                    </PopoverAnchor>
                    <PopoverContent
                        align="start"
                        sideOffset={4}
                        onOpenAutoFocus={(e) => e.preventDefault()}
                        onCloseAutoFocus={(e) => e.preventDefault()}
                        onMouseDown={(e) => e.preventDefault()}
                        onWheel={(e) => e.stopPropagation()}
                        onTouchMove={(e) => e.stopPropagation()}
                        className="w-(--radix-popover-trigger-width) p-1"
                    >
                        <ScrollArea className="[&_[data-radix-scroll-area-viewport]>div]:!block">
                            <ul role="listbox" ref={listRef} className="max-h-60 pe-2.5">
                                {filteredSuggestions.map((suggestion, index) => (
                                    <li key={suggestion.value} role="option" aria-selected={index === safeActiveIndex}>
                                        <button
                                            type="button"
                                            tabIndex={-1}
                                            onMouseEnter={() => setActiveIndex(index)}
                                            onClick={() => addValue(suggestion.value)}
                                            className={cn(
                                                'flex w-full flex-col items-start gap-0.5 rounded-sm px-2 py-1.5 text-start text-sm',
                                                index === safeActiveIndex && 'bg-accent text-accent-foreground',
                                            )}
                                        >
                                            <span className="w-full">{suggestion.label}</span>
                                            {suggestion.label !== suggestion.value && (
                                                <span className="w-full break-all text-xs text-muted-foreground">
                                                    {suggestion.value}
                                                </span>
                                            )}
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        </ScrollArea>
                    </PopoverContent>
                </Popover>

                <Button type="button" size="icon-md" variant="outline" onClick={commitInput} disabled={!inputValue.trim()}>
                    <PlusIcon />
                </Button>
            </div>

            {tags.length > 0 && (
                <div className="flex flex-wrap gap-2">
                    {tags.map((tag, index) => (
                        <RemovableTag
                            key={tag}
                            label={getTagLabel ? getTagLabel(tag) : tag}
                            onRemove={() => onRemove(index)}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}
