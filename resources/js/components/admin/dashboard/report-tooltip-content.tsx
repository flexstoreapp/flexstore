import { ChartTooltipContent } from '@/components/ui/chart';
import { useFormatMoney } from '@/hooks/use-format-money';

interface SeriesItem {
    key: string;
    label: string;
    format: string;
}

function formatValue(value: unknown, format: string, formatMoney: (v: string | number) => string): string {
    if (format === 'money') return formatMoney(value as string);
    if (format === 'percentage') return `${value}%`;
    return String(value);
}

export function ReportTooltipContent({
    series,
    ...props
}: React.ComponentProps<typeof ChartTooltipContent> & { series: SeriesItem[] }) {
    const { formatMoney } = useFormatMoney();

    return (
        <ChartTooltipContent
            {...props}
            formatter={(value, name) => {
                const s = series.find((item) => item.key === name);
                if (!s) return String(value);

                return (
                    <div className="flex w-full flex-wrap items-stretch gap-2">
                        <div className="flex flex-1 items-center justify-between gap-2 leading-none">
                            <span className="text-muted-foreground">{s.label}</span>
                            <span className="font-medium text-foreground">
                                {formatValue(value, s.format, formatMoney)}
                            </span>
                        </div>
                    </div>
                );
            }}
        />
    );
}
