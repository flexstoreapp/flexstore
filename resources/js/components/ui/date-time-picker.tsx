import { useState } from 'react';

import { DatePicker } from '@/components/ui/date-picker';
import { ReactiveHiddenInput } from '@/components/admin/reactive-hidden-input';
import { TimePicker } from '@/components/ui/time-picker';

interface DateTimePickerProps {
    name: string;
    defaultValue?: string | null;
    id?: string;
    disabled?: boolean;
}

function toLocalDatetime(iso: string): string {
    const d = new Date(iso);
    if (isNaN(d.getTime())) return '';
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function extractDate(datetime: string): string {
    return datetime ? datetime.split('T')[0] : '';
}

function extractTime(datetime: string): string {
    return datetime ? (datetime.split(/[T ]/)[1] ?? '') : '';
}

function combineDateTime(date: string, time: string): string {
    if (!date) return '';
    return `${date}T${time || '00:00'}`;
}

export function DateTimePicker({ name, defaultValue, id, disabled }: DateTimePickerProps) {
    const initial = defaultValue ? toLocalDatetime(defaultValue) : '';
    const [value, setValue] = useState(initial);

    const initialDate = extractDate(initial);
    const initialTime = extractTime(initial);

    const onDateChange = (date: Date | undefined) => {
        const dateStr = date ? date.toLocaleDateString('en-CA') : '';
        setValue((prev) => combineDateTime(dateStr, extractTime(prev)));
    };

    const onTimeChange = (time: string) => {
        setValue((prev) => combineDateTime(extractDate(prev), time));
    };

    return (
        <div className="grid grid-cols-2 gap-3">
            <ReactiveHiddenInput name={name} value={value} />
            <DatePicker id={id ? `${id}-date` : undefined} defaultValue={initialDate} onChange={onDateChange} disabled={disabled} />
            <TimePicker id={id ? `${id}-time` : undefined} defaultValue={initialTime} onChange={onTimeChange} disabled={disabled} />
        </div>
    );
}
