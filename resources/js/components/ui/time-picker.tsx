import { ClockIcon } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

import { Button } from '@/components/ui/button';
import { ReactiveHiddenInput } from '@/components/admin/reactive-hidden-input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

interface TimePickerProps {
    placeholder?: string;
    defaultValue?: string;
    onChange?: (value: string) => void;
    name?: string;
    id?: string;
    className?: string;
    disabled?: boolean;
}

const ITEM_HEIGHT = 36;
const VISIBLE_COUNT = 5;
const PICKER_HEIGHT = ITEM_HEIGHT * VISIBLE_COUNT;
const SCROLL_PADDING = ITEM_HEIGHT * Math.floor(VISIBLE_COUNT / 2);

const FADE_MASK = `linear-gradient(to bottom, transparent, black ${ITEM_HEIGHT}px, black ${PICKER_HEIGHT - ITEM_HEIGHT}px, transparent)`;

const hourItems = Array.from({ length: 12 }, (_, i) => String(i + 1));
const minuteItems = Array.from({ length: 60 }, (_, i) => String(i).padStart(2, '0'));
const periodItems = ['AM', 'PM'];

function to12Hour(h24: number): { hour12: string; period: string } {
    if (isNaN(h24)) return { hour12: '12', period: 'AM' };
    const period = h24 >= 12 ? 'PM' : 'AM';
    const hour12 = String(h24 === 0 ? 12 : h24 > 12 ? h24 - 12 : h24);
    return { hour12, period };
}

function to24Hour(h12: string, period: string): string {
    let h = parseInt(h12, 10);
    if (isNaN(h)) h = 12;
    if (period === 'AM') {
        h = h === 12 ? 0 : h;
    } else {
        h = h === 12 ? 12 : h + 12;
    }
    return String(h).padStart(2, '0');
}

interface PickerColumnProps {
    items: string[];
    value: string;
    onValueChange: (value: string) => void;
    className?: string;
}

function PickerColumn({ items, value, onValueChange, className }: PickerColumnProps) {
    const scrollRef = useRef<HTMLDivElement>(null);
    const initialValueRef = useRef(value);
    const readyRef = useRef(false);
    const lastEmittedRef = useRef(value);
    const onValueChangeRef = useRef(onValueChange);
    const dragRef = useRef<{ startY: number; startScroll: number } | null>(null);

    useEffect(() => {
        onValueChangeRef.current = onValueChange;
    });

    useEffect(() => {
        const index = items.indexOf(initialValueRef.current);
        if (index >= 0 && scrollRef.current) {
            scrollRef.current.scrollTop = index * ITEM_HEIGHT;
        }
        const timeout = setTimeout(() => {
            readyRef.current = true;
        }, 150);
        return () => clearTimeout(timeout);
    }, [items]);

    const handleScroll = useCallback(() => {
        if (!readyRef.current) return;
        const el = scrollRef.current;
        if (!el) return;
        const index = Math.round(el.scrollTop / ITEM_HEIGHT);
        const clamped = Math.max(0, Math.min(index, items.length - 1));
        const newValue = items[clamped];
        if (newValue !== lastEmittedRef.current) {
            lastEmittedRef.current = newValue;
            onValueChangeRef.current(newValue);
        }
    }, [items]);

    const scrollToIndex = (index: number) => {
        scrollRef.current?.scrollTo({ top: index * ITEM_HEIGHT, behavior: 'smooth' });
    };

    const handlePointerDown = (e: React.PointerEvent) => {
        dragRef.current = { startY: e.clientY, startScroll: scrollRef.current?.scrollTop ?? 0 };
    };

    const handlePointerMove = (e: React.PointerEvent) => {
        const el = scrollRef.current;
        if (!dragRef.current || !el) return;
        const delta = Math.abs(e.clientY - dragRef.current.startY);
        if (delta > 3 && !el.hasPointerCapture(e.pointerId)) {
            el.setPointerCapture(e.pointerId);
            el.style.scrollSnapType = 'none';
        }
        if (el.hasPointerCapture(e.pointerId)) {
            el.scrollTop = dragRef.current.startScroll + (dragRef.current.startY - e.clientY);
        }
    };

    const handlePointerUp = (e: React.PointerEvent) => {
        const el = scrollRef.current;
        if (!dragRef.current || !el) return;
        const wasDragging = el.hasPointerCapture(e.pointerId);
        dragRef.current = null;
        if (wasDragging) {
            el.releasePointerCapture(e.pointerId);
            const index = Math.round(el.scrollTop / ITEM_HEIGHT);
            el.scrollTo({ top: index * ITEM_HEIGHT, behavior: 'smooth' });
            setTimeout(() => {
                el.style.scrollSnapType = '';
            }, 300);
        }
    };

    return (
        <div
            ref={scrollRef}
            className={cn(
                "w-12 snap-y snap-mandatory overflow-y-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden",
                className,
            )}
            style={{
                height: PICKER_HEIGHT,
                maskImage: FADE_MASK,
                WebkitMaskImage: FADE_MASK,
            }}
            onScroll={handleScroll}
            onPointerDown={handlePointerDown}
            onPointerMove={handlePointerMove}
            onPointerUp={handlePointerUp}
            onPointerCancel={handlePointerUp}
        >
            <div style={{ height: SCROLL_PADDING }} />
            {items.map((item, index) => (
                <button
                    key={item}
                    type="button"
                    className={cn(
                        "flex w-full snap-center items-center justify-center text-sm",
                        item === value ? "font-semibold text-foreground" : "text-muted-foreground/70",
                    )}
                    style={{ height: ITEM_HEIGHT }}
                    onClick={() => scrollToIndex(index)}
                >
                    {item}
                </button>
            ))}
            <div style={{ height: SCROLL_PADDING }} />
        </div>
    );
}

export function TimePicker({
    placeholder = '12:00 AM',
    defaultValue,
    onChange,
    name,
    id,
    className,
    disabled = false,
}: TimePickerProps) {
    const [open, setOpen] = useState(false);

    const [hour24, setHour24] = useState(() => defaultValue?.split(':')[0] ?? '');
    const [minute, setMinute] = useState(() => defaultValue?.split(':')[1] ?? '');

    const stateRef = useRef({ hour24, minute, hour12: '', period: '' });
    const triggerRef = useRef<HTMLButtonElement>(null);

    useEffect(() => {
        const form = triggerRef.current?.closest('form');
        if (!form) return;

        const handleReset = () => {
            setHour24(defaultValue?.split(':')[0] ?? '');
            setMinute(defaultValue?.split(':')[1] ?? '');
        };

        form.addEventListener('reset', handleReset);
        return () => form.removeEventListener('reset', handleReset);
    }, [defaultValue]);

    const h24 = parseInt(hour24, 10);
    const { hour12, period } = hour24 !== '' ? to12Hour(h24) : { hour12: '', period: '' };
    const hasValue = hour24 !== '' && minute !== '';

    stateRef.current = { hour24, minute, hour12, period };

    const emit = (h24Val: string, m: string) => {
        onChange?.(`${h24Val}:${m}`);
    };

    const handleHour12Change = (h12: string) => {
        const p = stateRef.current.period || 'AM';
        const newH24 = to24Hour(h12, p);
        setHour24(newH24);
        const m = stateRef.current.minute || '00';
        if (stateRef.current.minute === '') setMinute(m);
        emit(newH24, m);
    };

    const handleMinuteChange = (m: string) => {
        setMinute(m);
        const h = stateRef.current.hour24 || '00';
        if (stateRef.current.hour24 === '') setHour24(h);
        emit(h, m);
    };

    const handlePeriodChange = (p: string) => {
        const h12Val = stateRef.current.hour12 || '12';
        const newH24 = to24Hour(h12Val, p);
        setHour24(newH24);
        const m = stateRef.current.minute || '00';
        if (stateRef.current.minute === '') setMinute(m);
        emit(newH24, m);
    };

    const displayValue = hasValue ? `${hour12}:${minute} ${period}` : '';

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
                        !hasValue && "text-muted-foreground",
                        className,
                    )}
                >
                    <ClockIcon className="size-4 text-muted-foreground" />
                    {hasValue ? displayValue : <span>{placeholder}</span>}
                    {name && <ReactiveHiddenInput name={name} value={hasValue ? `${hour24}:${minute}` : ''} />}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="start">
                <div className="relative p-1" style={{ height: PICKER_HEIGHT + 8 }}>
                    <div
                        className="absolute inset-x-1 rounded-lg bg-accent"
                        style={{ top: SCROLL_PADDING + 4, height: ITEM_HEIGHT }}
                    />

                    <div className="relative flex items-start">
                        <PickerColumn items={hourItems} value={hour12 || '12'} onValueChange={handleHour12Change} />
                        <div
                            className="flex shrink-0 items-center text-sm font-medium text-foreground"
                            style={{ height: PICKER_HEIGHT }}
                        >
                            :
                        </div>
                        <PickerColumn items={minuteItems} value={minute || '00'} onValueChange={handleMinuteChange} />
                        <PickerColumn
                            items={periodItems}
                            value={period || 'AM'}
                            onValueChange={handlePeriodChange}
                            className="w-10"
                        />
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    );
}
