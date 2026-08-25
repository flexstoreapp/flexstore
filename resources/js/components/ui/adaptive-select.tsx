import { CommandList } from 'cmdk';
import { CheckIcon, ChevronDownIcon } from 'lucide-react';
import { type Ref, useEffect, useRef, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem } from '@/components/ui/command';
import {
    Drawer,
    DrawerContent,
    DrawerDescription,
    DrawerHeader,
    DrawerTitle,
    DrawerTrigger,
} from '@/components/ui/drawer';
import { useInDialogLayer } from '@/contexts/dialog-context';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { ReactiveHiddenInput } from '@/components/admin/reactive-hidden-input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useIsMobile } from '@/hooks/admin/use-mobile';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export interface AdaptiveSelectOption {
    value: string;
    label: string;
}

interface AdaptiveSelectProps {
    id?: string;
    name?: string;
    options: AdaptiveSelectOption[];
    value?: string;
    defaultValue?: string;
    onValueChange?: (value: string) => void;
    placeholder?: string;
    search?: boolean;
    searchPlaceholder?: string;
    emptyText?: string;
    disabled?: boolean;
    className?: string;
    contentClassName?: string;
    'aria-label'?: string;
    modal?: boolean;
    ref?: Ref<HTMLButtonElement>;
}

interface OptionsListProps {
    options: AdaptiveSelectOption[];
    value?: string;
    onSelect: (value: string) => void;
    search: boolean;
    searchPlaceholder: string;
    emptyText: string;
    mobile?: boolean;
}

function AdaptiveSelect({
    id,
    name,
    options,
    value,
    defaultValue,
    onValueChange,
    placeholder = __('Select an option'),
    search,
    searchPlaceholder = __('Search...'),
    emptyText = __('No items found.'),
    disabled = false,
    className,
    contentClassName,
    'aria-label': ariaLabel,
    modal,
    ref,
}: AdaptiveSelectProps) {
    const isMobile = useIsMobile();
    const inDialogLayer = useInDialogLayer();
    const [open, setOpen] = useState(false);
    const [internalValue, setInternalValue] = useState(value ?? defaultValue ?? '');
    const [prevValue, setPrevValue] = useState(value);
    if (value !== undefined && value !== prevValue) {
        setPrevValue(value);
        setInternalValue(value);
    }

    const handleSelect = (value: string) => {
        setInternalValue(value);
        onValueChange?.(value);
        setOpen(false);
    };

    const shouldShowSearch = search !== undefined ? search : options.length > 10;
    const selectedOption = options.find((option) => option.value === internalValue);
    const displayValue = selectedOption?.label ?? placeholder;

    if (isMobile) {
        return (
            <Drawer open={open} onOpenChange={setOpen} shouldScaleBackground={true} repositionInputs={false}>
                <DrawerTrigger id={id} asChild>
                    <Button
                        ref={ref}
                        variant="outline"
                        size="md"
                        role="combobox"
                        tabIndex={0}
                        aria-expanded={open}
                        aria-label={ariaLabel}
                        className={cn(
                            "w-full min-w-0 overflow-hidden justify-between border-input bg-transparent px-3 font-normal shadow-none hover:bg-transparent hover:text-foreground dark:bg-transparent dark:hover:bg-transparent",
                            !selectedOption && "text-muted-foreground",
                            className,
                        )}
                        disabled={disabled}
                    >
                        <span className={cn("truncate text-sm", !selectedOption && "text-muted-foreground")}>
                            {displayValue}
                        </span>
                        <ChevronDownIcon className="shrink-0 text-muted-foreground opacity-50" />
                    </Button>
                </DrawerTrigger>
                <DrawerContent>
                    <DrawerHeader>
                        <DrawerTitle>{placeholder}</DrawerTitle>
                        <DrawerDescription className="sr-only">{__('Options')}</DrawerDescription>
                    </DrawerHeader>
                    <div className="pb-4">
                        <OptionsList
                            options={options}
                            value={internalValue}
                            onSelect={handleSelect}
                            search={shouldShowSearch}
                            searchPlaceholder={searchPlaceholder}
                            emptyText={emptyText}
                            mobile
                        />
                    </div>
                    {name && <ReactiveHiddenInput name={name} value={internalValue} />}
                </DrawerContent>
            </Drawer>
        );
    }

    return (
        <>
            {name && <ReactiveHiddenInput name={name} value={internalValue} />}

            <Popover open={open} onOpenChange={setOpen} modal={modal ?? inDialogLayer}>
                <PopoverTrigger id={id} asChild>
                    <Button
                        ref={ref}
                        variant="outline"
                        size="md"
                        role="combobox"
                        tabIndex={0}
                        aria-expanded={open}
                        aria-label={ariaLabel}
                        className={cn(
                            "w-full min-w-0 overflow-hidden justify-between border-input bg-transparent px-3 font-normal shadow-none hover:bg-transparent hover:text-foreground dark:bg-transparent dark:hover:bg-transparent",
                            !selectedOption && "text-muted-foreground",
                            className,
                        )}
                        disabled={disabled}
                    >
                        <span className={cn("truncate text-sm", !selectedOption && "text-muted-foreground")}>
                            {displayValue}
                        </span>
                        <ChevronDownIcon className="shrink-0 text-muted-foreground opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent
                    className={cn('w-(--radix-popover-trigger-width) min-w-56 p-0', contentClassName)}
                    align="start"
                    sideOffset={5}
                >
                    <OptionsList
                        options={options}
                        value={internalValue}
                        onSelect={handleSelect}
                        search={shouldShowSearch}
                        searchPlaceholder={searchPlaceholder}
                        emptyText={emptyText}
                    />
                </PopoverContent>
            </Popover>
        </>
    );
}

const EMPTY_OPTION_VALUE = '__adaptive_select_empty__';

function toItemValue(value: string) {
    return value === '' ? EMPTY_OPTION_VALUE : value;
}

function OptionsList({ options, value, onSelect, search, searchPlaceholder, emptyText, mobile }: OptionsListProps) {
    const groupRef = useRef<HTMLDivElement>(null);
    const [scrollable, setScrollable] = useState(false);

    useEffect(() => {
        const element = groupRef.current;
        if (!element) return;

        const update = () => setScrollable(element.scrollHeight > element.clientHeight + 1);
        update();

        const observer = new ResizeObserver(update);
        observer.observe(element);

        return () => observer.disconnect();
    }, [options]);

    return (
        <Command defaultValue={value !== undefined ? toItemValue(value) : undefined}>
            <CommandInput visible={search} placeholder={searchPlaceholder} className={mobile ? 'mx-4' : undefined} />
            <CommandEmpty>{emptyText}</CommandEmpty>
            <CommandList>
                <ScrollArea>
                    <CommandGroup
                        ref={groupRef}
                        className={cn(
                            'max-h-75 overflow-visible',
                            scrollable && 'pe-2.5',
                            mobile && '**:[[cmdk-item]]:px-4',
                        )}
                    >
                        {options.map((option) => (
                            <CommandItem
                                key={option.value}
                                value={toItemValue(option.value)}
                                onSelect={() => onSelect(option.value)}
                                keywords={[option.label]}
                                className="flex items-center justify-between"
                            >
                                {option.label}
                                {value === option.value ? (
                                    <CheckIcon className="size-4 shrink-0" />
                                ) : (
                                    <span className="size-4 shrink-0" aria-hidden />
                                )}
                            </CommandItem>
                        ))}
                    </CommandGroup>
                </ScrollArea>
            </CommandList>
        </Command>
    );
}

export { AdaptiveSelect };
