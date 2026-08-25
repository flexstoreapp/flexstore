import { CalendarIcon } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { ReactiveHiddenInput } from '@/components/admin/reactive-hidden-input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { useFormatDate } from '@/hooks/use-format-date';
import { cn } from '@/lib/utils';

function parseDate(value: Date | string | undefined): Date | undefined {
    if (!value) return undefined;
    if (value instanceof Date) return value;

    const parsed = new Date(value);
    return isNaN(parsed.getTime()) ? undefined : parsed;
}

interface DatePickerProps {
    placeholder?: string;
    defaultValue?: string;
    value?: Date | string;
    onChange?: (value: Date | undefined) => void;
    name?: string;
    id?: string;
    className?: string;
    disabled?: boolean;
}

export function DatePicker({
    placeholder,
    defaultValue,
    value,
    onChange,
    name,
    id,
    className,
    disabled = false,
}: DatePickerProps) {
    const [open, setOpen] = useState(false);
    const [internalValue, setInternalValue] = useState<Date | undefined>(
        defaultValue ? new Date(defaultValue) : undefined,
    );
    const formatDate = useFormatDate();
    const triggerRef = useRef<HTMLButtonElement>(null);

    useEffect(() => {
        const form = triggerRef.current?.closest('form');
        if (!form) return;

        const handleReset = () => {
            setInternalValue(defaultValue ? new Date(defaultValue) : undefined);
        };

        form.addEventListener('reset', handleReset);
        return () => form.removeEventListener('reset', handleReset);
    }, [defaultValue]);

    const updateValue = (newValue: Date | string) => {
        const parsedValue = parseDate(newValue);
        if (parsedValue !== internalValue) {
            setInternalValue(parsedValue);
        }
    };

    if (value) {
        updateValue(value);
    }

    const handleDateSelect = (date: Date | undefined) => {
        setInternalValue(date);
        onChange?.(date);
        setOpen(false);
    };

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    ref={triggerRef}
                    id={id}
                    variant="outline"
                    size="md"
                    disabled={disabled}
                    className={cn(
                        "w-full justify-start gap-2 bg-transparent! font-normal",
                        !internalValue && "text-muted-foreground",
                        className,
                    )}
                >
                    <CalendarIcon className="size-4 text-muted-foreground" />
                    {internalValue ? formatDate(internalValue) : <span>{placeholder || formatDate(new Date())}</span>}
                    {name && (
                        <ReactiveHiddenInput name={name} value={internalValue ? internalValue.toISOString() : ''} />
                    )}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto overflow-hidden p-0" align="start">
                <Calendar
                    mode="single"
                    selected={internalValue}
                    captionLayout="dropdown"
                    endMonth={new Date(2099, 12)}
                    onSelect={handleDateSelect}
                    disabled={disabled}
                />
            </PopoverContent>
        </Popover>
    );
}
