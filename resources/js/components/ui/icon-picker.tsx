import { ChevronDownIcon } from 'lucide-react';
import { useEffect, useState } from 'react';


import { Button } from './button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from './command';
import { Popover, PopoverContent, PopoverTrigger } from './popover';
import { ReactiveHiddenInput } from '@/components/admin/reactive-hidden-input';
import { ScrollArea } from './scroll-area';
import { __ } from '@/lib/i18n';
import { getIconLabel, ICONS } from '@/lib/icons';
import { cn } from '@/lib/utils';

interface IconPickerProps {
    value?: string;
    onChange?: (iconName: string) => void;
    name?: string;
    id?: string;
    readOnly?: boolean;
}

const COLUMNS = 3;

export function IconPicker({ value, onChange, name, id, readOnly }: IconPickerProps) {
    const [open, setOpen] = useState(false);

    const [activeValue, setActiveValue] = useState(value ?? ICONS[0]?.name ?? '');

    useEffect(() => {
        if (open) setActiveValue(value ?? ICONS[0]?.name ?? '');
    }, [open, value]);

    const handleArrowNavigation = (event: React.KeyboardEvent<HTMLDivElement>) => {
        const step = { ArrowRight: 1, ArrowLeft: -1, ArrowDown: COLUMNS, ArrowUp: -COLUMNS }[event.key];
        if (step === undefined) return;

        const items = Array.from(event.currentTarget.querySelectorAll<HTMLElement>('[cmdk-item]')).filter(
            (item) => item.offsetParent !== null,
        );
        if (items.length === 0) return;

        event.preventDefault();

        const current = items.findIndex((item) => item.getAttribute('aria-selected') === 'true');
        const target = Math.max(0, Math.min(items.length - 1, (current === -1 ? 0 : current) + step));
        const next = items[target];
        if (next) {
            next.scrollIntoView({ block: 'nearest' });
            setActiveValue(next.getAttribute('data-value') ?? '');
        }
    };

    const selectedIcon = ICONS.find((icon) => icon.name === value);
    const SelectedIconComponent = selectedIcon?.component;

    const handleSelect = (iconName: string) => {
        onChange?.(iconName);
        setOpen(false);
    };

    return (
        <>
            <Popover open={readOnly ? false : open} onOpenChange={readOnly ? () => {} : setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        type="button"
                        variant="outline"
                        size="md"
                        role="combobox"
                        aria-expanded={open}
                        aria-readonly={readOnly}
                        className={cn(
                            'w-full min-w-0 justify-between border-input bg-transparent px-3 font-normal shadow-none hover:bg-transparent hover:text-foreground dark:bg-transparent dark:hover:bg-transparent',
                            readOnly && 'cursor-default bg-muted hover:bg-muted dark:bg-muted dark:hover:bg-muted',
                        )}
                        id={id}
                    >
                        {SelectedIconComponent ? (
                            <span className="flex min-w-0 items-center gap-2">
                                <SelectedIconComponent className="size-4 shrink-0" />
                                <span className="truncate text-sm">{getIconLabel(selectedIcon.name)}</span>
                            </span>
                        ) : (
                            <span className="truncate text-sm text-muted-foreground">{__('Select an icon')}</span>
                        )}
                        {!readOnly && <ChevronDownIcon className="shrink-0 text-muted-foreground/50" />}
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-80 p-0" align="start">
                    <Command value={activeValue} onValueChange={setActiveValue} onKeyDownCapture={handleArrowNavigation}>
                        <CommandInput placeholder={__('Search...')} />
                        <CommandEmpty>{__('No items found.')}</CommandEmpty>
                        <CommandList>
                            <ScrollArea className="[&_[data-radix-scroll-area-viewport]>div]:block!">
                                <CommandGroup className="max-h-72 overflow-visible p-0 [&_[cmdk-group-items]]:grid [&_[cmdk-group-items]]:grid-cols-3 [&_[cmdk-group-items]]:gap-2 [&_[cmdk-group-items]]:p-3">
                                    {ICONS.map((icon) => {
                                        const IconComponent = icon.component;
                                        const isSelected = value === icon.name;

                                        return (
                                            <CommandItem
                                                key={icon.name}
                                                value={icon.name}
                                                keywords={icon.name.split('-')}
                                                onSelect={handleSelect}
                                                className={cn(
                                                    'flex flex-col items-center gap-1.5 rounded-md border p-3',
                                                    isSelected && 'bg-muted',
                                                )}
                                                title={getIconLabel(icon.name)}
                                            >
                                                <IconComponent className="size-5" />
                                                <span className="max-w-full truncate text-xs leading-tight">{getIconLabel(icon.name)}</span>
                                            </CommandItem>
                                        );
                                    })}
                                </CommandGroup>
                            </ScrollArea>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>

            {name && <ReactiveHiddenInput name={name} value={value ?? ''} />}
        </>
    );
}
