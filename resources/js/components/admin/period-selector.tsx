import { router, usePage } from '@inertiajs/react';
import { CalendarIcon } from 'lucide-react';
import { useCallback, useState } from 'react';
import type { DateRange } from 'react-day-picker';

import { ScrollFade } from '@/components/scroll-fade';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { useFormatDate } from '@/hooks/use-format-date';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { AdminSharedData, PeriodRanges } from '@/types';

const PRESETS = [
    'today',
    'yesterday',
    '7d',
    '30d',
    'this-month',
    'last-month',
    'this-quarter',
    'last-quarter',
    'this-year',
    'lifetime',
] as const;

type Preset = (typeof PRESETS)[number];

function presetLabel(preset: Preset): string {
    switch (preset) {
        case 'today':
            return __('Today');
        case 'yesterday':
            return __('Yesterday');
        case '7d':
            return __('Last 7 days');
        case '30d':
            return __('Last 30 days');
        case 'this-month':
            return __('This month');
        case 'last-month':
            return __('Last month');
        case 'this-quarter':
            return __('This quarter');
        case 'last-quarter':
            return __('Last quarter');
        case 'this-year':
            return __('This year');
        case 'lifetime':
            return __('Lifetime');
    }
}

function toLocalDate(value: string): Date {
    const [year, month, day] = value.split('-').map(Number);

    return new Date(year, month - 1, day);
}

function toIsoDate(date: Date): string {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

/**
 * Today as the store sees it. The server resolves every period in the app timezone, so the
 * calendar has to agree with it rather than with whatever zone the browser happens to be in.
 */
function todayIn(timezone: string): Date {
    const parts = new Intl.DateTimeFormat('en-US', {
        timeZone: timezone,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).formatToParts(new Date());

    const part = (type: string) => Number(parts.find((entry) => entry.type === type)?.value);

    return new Date(part('year'), part('month') - 1, part('day'));
}

function timezoneLabel(timezone: string, locale: string): string {
    const parts = new Intl.DateTimeFormat(locale, { timeZone: timezone, timeZoneName: 'long' }).formatToParts(
        new Date(),
    );

    return parts.find((part) => part.type === 'timeZoneName')?.value ?? timezone;
}

interface PeriodSelectorProps {
    period: string;
    from: string;
    to: string;
    periods: PeriodRanges;
    buildUrl: (params: Record<string, string>) => Parameters<typeof router.get>[0];
}

export function PeriodSelector({ period, from, to, periods, buildUrl }: PeriodSelectorProps) {
    const formatDate = useFormatDate();
    const { timezone, activeLocale } = usePage<AdminSharedData>().props;

    const [open, setOpen] = useState(false);
    const [draft, setDraft] = useState<DateRange | undefined>(undefined);
    const [draftPeriod, setDraftPeriod] = useState<string | undefined>(undefined);
    const [browsedMonth, setBrowsedMonth] = useState<Date | undefined>(undefined);

    const today = todayIn(timezone);
    const selectedPeriod = draftPeriod ?? period;
    const range = draft ?? { from: toLocalDate(from), to: toLocalDate(to) };
    const month = browsedMonth ?? range.to ?? today;

    const centerActivePreset = useCallback((tab: HTMLButtonElement | null) => {
        const scroller = tab?.closest<HTMLElement>('.scroll-fade-scroller');

        if (!tab || !scroller) {
            return;
        }

        const tabRect = tab.getBoundingClientRect();
        const scrollerRect = scroller.getBoundingClientRect();

        if (tabRect.left >= scrollerRect.left && tabRect.right <= scrollerRect.right) {
            return;
        }

        scroller.scrollLeft += tabRect.left - scrollerRect.left - (scroller.clientWidth - tab.offsetWidth) / 2;
    }, []);

    const handleOpenChange = (next: boolean) => {
        setOpen(next);
        setDraft(undefined);
        setDraftPeriod(undefined);
        setBrowsedMonth(undefined);
    };

    const navigate = (params: Record<string, string>) => {
        router.get(buildUrl(params), {}, { preserveState: true, preserveScroll: true, replace: true });
        handleOpenChange(false);
    };

    const handlePresetClick = (preset: Preset) => {
        const preview = periods[preset];

        setDraftPeriod(preset);
        setBrowsedMonth(undefined);

        if (preview) {
            setDraft({ from: toLocalDate(preview.from), to: toLocalDate(preview.to) });
        }
    };

    const handleRangeSelect = (selected: DateRange | undefined) => {
        setDraft(selected);
        setDraftPeriod('custom');
    };

    const handleApply = () => {
        if (selectedPeriod !== 'custom') {
            navigate({ period: selectedPeriod });

            return;
        }

        if (!range.from || !range.to) {
            return;
        }

        navigate({ period: 'custom', from: toIsoDate(range.from), to: toIsoDate(range.to) });
    };

    return (
        <Popover open={open} onOpenChange={handleOpenChange}>
            <PopoverTrigger asChild>
                <Button variant="outline" size="sm" className="w-fit gap-1.5">
                    <CalendarIcon className="size-3.5" />
                    {formatDate(toLocalDate(from))} – {formatDate(toLocalDate(to))}
                </Button>
            </PopoverTrigger>

            <PopoverContent className="w-auto max-w-[calc(100vw-2rem)] overflow-hidden p-0 sm:max-w-none" align="end">
                <div className="flex flex-col sm:flex-row">
                    <ScrollFade
                        axis="x"
                        className="w-0 min-w-full shrink-0 border-b [--scroll-fade-color:var(--popover)] sm:w-44 sm:min-w-0 sm:border-e sm:border-b-0"
                        scrollerClassName="overflow-x-auto p-2 sm:overflow-visible"
                    >
                        <ul className="flex w-max gap-1 sm:w-full sm:flex-col">
                            {PRESETS.map((preset) => (
                                <li key={preset}>
                                    <Button
                                        ref={selectedPeriod === preset ? centerActivePreset : undefined}
                                        variant="ghost"
                                        size="sm"
                                        className={cn(
                                            'w-full justify-start font-normal whitespace-nowrap',
                                            selectedPeriod === preset && 'bg-accent font-medium',
                                        )}
                                        onClick={() => handlePresetClick(preset)}
                                    >
                                        {presetLabel(preset)}
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    </ScrollFade>

                    <Calendar
                        mode="range"
                        selected={range}
                        onSelect={handleRangeSelect}
                        month={month}
                        onMonthChange={setBrowsedMonth}
                        captionLayout="dropdown"
                        today={today}
                        disabled={{ after: today }}
                    />
                </div>

                <div className="flex w-0 min-w-full items-center justify-end gap-4 border-t p-3 sm:justify-between">
                    <p className="hidden min-w-0 text-xs text-balance text-muted-foreground sm:block">
                        {__('Showing :timezone', { timezone: timezoneLabel(timezone, activeLocale) })}
                    </p>
                    <Button size="sm" onClick={handleApply} disabled={!range.from || !range.to}>
                        {__('Apply')}
                    </Button>
                </div>
            </PopoverContent>
        </Popover>
    );
}
