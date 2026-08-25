import { useFormatTime } from '@/hooks/admin/use-format-time';
import { useFormatDate } from '@/hooks/use-format-date';

export function ActivityTimestamp({ date }: { date: string }) {
    const formatDate = useFormatDate();
    const formatTime = useFormatTime();

    return (
        <time dateTime={date} className="shrink-0 pt-1 text-xs text-muted-foreground">
            {formatDate(date)}
            <span className="mx-1">&middot;</span>
            {formatTime(date)}
        </time>
    );
}
